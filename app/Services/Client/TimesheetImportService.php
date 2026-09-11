<?php
declare(strict_types=1);
namespace App\Services\Client;

use App\Libraries\AttendanceWindow;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use RuntimeException;
use Throwable;

/** Validate and preview first; commit only the same plan against unchanged records. */
final class TimesheetImportService
{
    private BaseConnection $db;
    private \Closure $audit;
    public function __construct(?BaseConnection $db = null, ?callable $audit = null)
    {
        $this->db = $db ?? db_connect();
        $this->audit = \Closure::fromCallable($audit ?? static fn($description, $id, $data) => service('activityLogger')->log('created', 'attendance', $description, $id, null, $data));
    }

    public static function fields(): array
    {
        return [
            'employee_number' => ['label'=>'Employee number','required'=>true,'aliases'=>['employeenumber','employeeno','empno','employeeid']],
            'date' => ['label'=>'Date (when times have no date)','required'=>false,'aliases'=>['date','attendancedate','logdate','workdate']],
            'time_in' => ['label'=>'Time in','required'=>true,'aliases'=>['timein','clockin','in','timeindatetime']],
            'time_out' => ['label'=>'Time out','required'=>false,'aliases'=>['timeout','clockout','out','timeoutdatetime']],
            'break_duration' => ['label'=>'Break duration','required'=>false,'aliases'=>['breakduration','break','breakminutes','breakmins','breakhours']],
        ];
    }

    private static function header(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i','',$value));
    }

    /** Bounded first-sheet reader; formulas are never evaluated. */
    private function read(string $path, string $extension): array
    {
        if (!is_file($path) || filesize($path) > 5*1024*1024) throw new DomainException('Choose a file no larger than 5 MB.');
        $extension = strtolower($extension);
        $rows = [];
        if ($extension === 'csv') {
            $handle = fopen($path,'rb');
            try {
                while (($row=fgetcsv($handle))!==false) {
                    if (count($rows)>=5001 || count($row)>50) throw new DomainException('Limit the file to 5,000 data rows and 50 columns.');
                    $rows[]=$row;
                }
            } finally { fclose($handle); }
        } elseif ($extension === 'xlsx') {
            $zip=new \ZipArchive();
            if($zip->open($path)!==true)throw new DomainException('The XLSX file is invalid.');
            $expanded=0;
            try{for($i=0;$i<$zip->numFiles;$i++){$expanded+=(int)$zip->statIndex($i)['size'];if($expanded>30*1024*1024)throw new DomainException('The workbook expands beyond the 30 MB limit.');}}finally{$zip->close();}
            $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $info=$reader->listWorksheetInfo($path);
            if(!$info || $info[0]['totalRows']>5001 || $info[0]['totalColumns']>50)throw new DomainException('The first worksheet must contain at most 5,000 data rows and 50 columns.');
            $reader->setLoadSheetsOnly($info[0]['worksheetName']);
            $book=$reader->load($path);
            try {
                $sheet=$book->getSheet(0);
                for($r=1;$r<=$info[0]['totalRows'];$r++){
                    $row=[];
                    for($c=1;$c<=$info[0]['totalColumns'];$c++){
                        $cell=$sheet->getCell([$c,$r]);$value=$cell->getValue();
                        if($cell->getDataType()===\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA)throw new DomainException('Replace workbook formulas with values before importing.');
                        if(is_numeric($value)&&\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)){
                            $date=\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                            $value=$date->format((float)$value<1?'H:i:s':'Y-m-d H:i:s');
                        }
                        $row[]=$value===null?'':(string)$value;
                    }
                    $rows[]=$row;
                }
            } finally { $book->disconnectWorksheets(); }
        } else throw new DomainException('Use a CSV or XLSX file. Export older XLS workbooks to XLSX first.');
        if(count($rows)<2)throw new DomainException('The file needs a header row and at least one attendance row.');
        $headers=array_map(fn($v)=>trim(str_replace("\xEF\xBB\xBF",'',(string)$v)),array_shift($rows));
        // Preserve every physical column so irregular headers can be mapped explicitly.
        $normalized=array_map([self::class,'header'],$headers);
        $counts=array_count_values($normalized);$labels=[];
        foreach($headers as $index=>$header){
            $column=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index+1);
            $key=$normalized[$index];
            $label=$key===''?'Column '.$column.' (unnamed)':($counts[$key]>1?$header.' [Column '.$column.']':$header);
            while(in_array($label,$labels,true))$label.=' [Column '.$column.']';
            $labels[]=$label;
        }
        $headers=$labels;
        return [$headers,$rows];
    }

    public static function parseDateTime(string $value, string $date, string $order): DateTimeImmutable
    {
        $value=trim($value);$date=trim($date);
        if(!in_array($order,['dmy','mdy'],true))throw new DomainException('Choose the date order.');
        if(!preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})[ T]/',$value)){
            if(preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/',$date))$date=substr($date,0,10);
            if(!preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})$/',$date))throw new DomainException('Provide a date with a four-digit year, or full timestamps.');
            $value=$date.' '.$value;
        }
        $value=str_replace('T',' ',$value);
        $dates=['Y-m-d',$order==='dmy'?'d/m/Y':'m/d/Y'];
        foreach($dates as $day)foreach(['H:i:s','H:i','g:i A','g:i:s A','g:i a','g:i:s a'] as $time){
            $dt=DateTimeImmutable::createFromFormat('!'.$day.' '.$time,$value,new DateTimeZone('Asia/Manila'));
            $error=DateTimeImmutable::getLastErrors();
            if($dt&&($error===false||(!$error['warning_count']&&!$error['error_count'])))return $dt;
        }
        throw new DomainException('Invalid date/time. Use YYYY-MM-DD HH:MM:SS or the selected slash-date order.');
    }

    public function preview(string $path,string $extension,array $columnMap=[],array $employeeMap=[],string $dateOrder='dmy',string $breakUnit='auto',?string $periodStart=null,?string $periodEnd=null): array
    {
        if(!in_array($breakUnit,['auto','hours','minutes'],true))throw new DomainException('Choose a valid break unit.');
        if(($periodStart===null)!==($periodEnd===null))throw new DomainException('Provide both a period start and end, or neither.');
        if($periodStart!==null&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$periodStart)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$periodEnd)||$periodStart>$periodEnd))throw new DomainException('Invalid import period.');
        [$headers,$rows]=$this->read($path,$extension);$mapping=[];$used=[];
        foreach(self::fields() as $key=>$field){
            $selected=array_key_exists($key,$columnMap)?$columnMap[$key]:null;
            if($selected!==null&&!is_string($selected))throw new DomainException('Invalid column mapping.');
            if($selected===null)foreach($headers as $header)if(in_array(self::header($header),$field['aliases'],true)&&!in_array($header,$used,true)){$selected=$header;break;}
            $selected??='';
            if($selected!==''&&(!in_array($selected,$headers,true)||in_array($selected,$used,true)))throw new DomainException('Each mapped column must exist and be used only once.');
            $mapping[$key]=$selected;if($selected!=='')$used[]=$selected;
        }
        $employees=$this->db->table('employees e')->select('e.id,e.user_id,e.employee_number,e.first_name,e.last_name,u.active,u.deleted_at')->join('users u','u.id=e.user_id')->get()->getResultArray();
        $byNumber=[];$byId=[];foreach($employees as $e){$byNumber[$e['employee_number']][]=$e;$byId[(int)$e['id']]=$e;}
        $unmatched=[];$unmatchedLabels=[];$display=[];$records=[];$seen=[];$intervals=[];$schedules=[];$summary=['ready'=>0,'duplicate'=>0,'error'=>0,'pending'=>0,'out_of_period'=>0];
        foreach($rows as $offset=>$values){
            if(!array_filter($values,fn($v)=>trim((string)$v)!==''))continue;
            $number=$offset+2;$view=['row'=>$number,'employee'=>'','time_in'=>'','time_out'=>'','break_hours'=>null,'total_hours'=>null,'status'=>'error','message'=>''];
            try{
                if(count($values)>count($headers)&&array_filter(array_slice($values,count($headers)),fn($v)=>trim((string)$v)!==''))throw new DomainException('This row has data beyond the header columns. Add headers for those columns and upload again.');
                $values=array_pad(array_slice($values,0,count($headers)),count($headers),'');
                $raw=array_combine($headers,$values);$data=[];
                foreach($mapping as $key=>$header)$data[$key]=$header!==''?trim((string)$raw[$header]):'';
                foreach(self::fields() as $key=>$field)if($key!=='employee_number'&&$field['required']&&$data[$key]==='')throw new DomainException($field['label'].' is required.');
                $identifier=$data['employee_number'];$view['employee']=$identifier;
                if(count($byNumber[$identifier]??[])>1)throw new DomainException('Employee number is duplicated in Employee Management; correct it first.');
                $employee=$byNumber[$identifier][0]??null;
                if(!$employee){
                    // A blank employee_number gives no evidence rows belong to the
                    // same person, so each blank row gets its own resolution bucket
                    // instead of being pooled and bulk-assignable to one employee.
                    $bucket=$identifier===''?'__row_'.$number:$identifier;
                    $unmatched[$bucket]=($unmatched[$bucket]??0)+1;
                    $unmatchedLabels[$bucket]=$identifier===''?'Row '.$number.' (no employee number)':$identifier;
                    $mapped=filter_var($employeeMap[$bucket]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                    $employee=$mapped?($byId[$mapped]??null):null;
                } elseif(!empty($employeeMap[$identifier])&&(int)$employeeMap[$identifier] !== (int)$employee['id'])throw new DomainException('A recognized employee number cannot be reassigned to another account.');
                if(!$employee)throw new DomainException($identifier===''?'Select the employee for rows without an employee number.':'Select the employee for this unrecognized number.');
                if(!(int)$employee['active']||$employee['deleted_at']!==null)throw new DomainException('Employee account is inactive or deleted.');
                $eid=(int)$employee['id'];$uid=(int)$employee['user_id'];
                $view['employee']=$employee['employee_number'].' — '.$employee['last_name'].', '.$employee['first_name'];
                $in=self::parseDateTime($data['time_in'],$data['date'],$dateOrder);
                if($data['time_out']===''){
                    // Still-open shifts (no clock-out yet in the source system) aren't
                    // rejected outright, but they also aren't auto-inserted as a new
                    // open attendance row here — that path is reserved for the live
                    // clock-in/out flow, which enforces "one open session per user".
                    // Report them so the admin can complete the clock-out in the app
                    // (or add a time out) and re-import, without blocking this file's
                    // other, complete rows.
                    $view=array_replace($view,['time_in'=>$in->format('Y-m-d H:i:s')]);
                    $view['status']='pending';$view['message']='No time out recorded; skipped. Complete the clock-out in the app or add a time out, then re-import.';
                    $summary[$view['status']]++;$display[]=$view;
                    continue;
                }
                $out=self::parseDateTime($data['time_out'],$data['date'] ?: $in->format('Y-m-d'),$dateOrder);
                $outHasDate=(bool)preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})[ T]/',$data['time_out']);
                if(!$outHasDate&&$out<$in)$out=$out->modify('+1 day');
                if($out<=$in)throw new DomainException('Time out must be after time in.');
                if($out>new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))throw new DomainException('Future attendance cannot be imported.');
                $break=$data['break_duration']===''?0:$data['break_duration'];
                if(!is_numeric($break)||!is_finite((float)$break)||(float)$break<0)throw new DomainException('Break duration must be a nonnegative number.');
                $unit=$breakUnit==='auto'?(str_contains(self::header($mapping['break_duration']),'min')?'minutes':'hours'):$breakUnit;
                $breakHours=round((float)$break/($unit==='minutes'?60:1),2);
                if($breakHours>($out->getTimestamp()-$in->getTimestamp())/3600)throw new DomainException('Break duration exceeds the attendance span.');
                $schedules[$eid]??=$this->db->table('employee_positions')->where('employee_id',$eid)->get()->getRowArray()??[];
                $schedule=array_intersect_key($schedules[$eid],array_flip(['start_time','end_time','break_schedule','working_days','working_day_type']));
                $timeIn=$in->format('Y-m-d H:i:s');$timeOut=$out->format('Y-m-d H:i:s');
                $shift=AttendanceWindow::shiftDate($timeIn,$schedule);
                if($periodStart!==null&&($shift<$periodStart||$shift>$periodEnd)){
                    // A chosen import period is a guardrail, not a relocation: a row
                    // whose actual shift date falls outside it is flagged for review
                    // instead of being silently imported into the wrong period (the
                    // shift date itself always stays the source of truth).
                    $view=array_replace($view,['time_in'=>$timeIn,'time_out'=>$timeOut,'shift_date'=>$shift]);
                    $view['status']='out_of_period';$view['message']="This row's shift date ($shift) is outside the selected import period ($periodStart to $periodEnd); skipped.";
                    $summary[$view['status']]++;$display[]=$view;
                    continue;
                }
                $record=['user_id'=>$uid,'time_in'=>$timeIn,'time_out'=>$timeOut,'time_in_dt'=>$timeIn,'time_out_dt'=>$timeOut,
                    'shift_date'=>$shift,'schedule_snapshot'=>json_encode($schedule,JSON_THROW_ON_ERROR),'break_duration'=>$breakHours,
                    'breaks_taken'=>0,'break_log'=>null,'state_version'=>0,'scheduled_start'=>$schedule['start_time']??null,'scheduled_end'=>$schedule['end_time']??null,
                    'total_hours'=>AttendanceWindow::elapsedHours($timeIn,$timeOut,$breakHours),'night_diff_hours'=>AttendanceWindow::nightHours($timeIn,$timeOut)];
                if($record['total_hours']>999.99||$record['night_diff_hours']>999.99||$breakHours>999.99)throw new DomainException('Attendance span exceeds supported limits.');
                [$start]=AttendanceWindow::bounds($record);$record['late_minutes']=AttendanceComputationService::lateMinutes($in,$start);
                $analysis=(new AttendanceComputationService())->analyzeClockOut($uid,$timeOut,$record);$ot=$analysis['overtime'];$ut=$analysis['undertime'];
                // Paid-window-restricted (not the full worked span, which the line
                // above set as a placeholder for the sanity-limit check only) — same
                // rule live clock-out, Timesheet, and Payroll all use.
                $record['night_diff_hours']=$analysis['night_diff_hours'];
                $record+=['approved_ot_hours'=>$ot['approved_ot'],'unapproved_ot_hours'=>$ot['unapproved_ot'],'approved_ut_hours'=>$ut['approved_ut'],'unapproved_ut_hours'=>$ut['unapproved_ut'],
                    'attendance_status'=>$ut['approved_ut']>0?'approved_undertime':($ut['unapproved_ut']>0?'unapproved_undertime':($ot['approved_ot']>0?'approved_overtime':($ot['unapproved_ot']>0?'unapproved_overtime':'present')))];
                $view=array_replace($view,['time_in'=>$timeIn,'time_out'=>$timeOut,'shift_date'=>$shift,'break_hours'=>$breakHours,'total_hours'=>$record['total_hours']]);
                $existing=$this->db->table('attendance')->where('user_id',$uid)->groupStart()->where('COALESCE(shift_date, DATE(time_in))',$shift)->orGroupStart()->where('time_in <',$timeOut)->groupStart()->where('time_out >',$timeIn)->orWhere('time_out',null)->groupEnd()->groupEnd()->groupEnd()->get()->getResultArray();
                $status='ready';$message='Ready to import; hours recalculated from timestamps.';
                if($existing){
                    if(count($existing)===1&&$existing[0]['time_in']===$timeIn&&$existing[0]['time_out']===$timeOut&&(float)$existing[0]['break_duration']===$breakHours){$status='duplicate';$message='Already recorded; will be skipped without changing the existing record.';}
                    else throw new DomainException('Conflicts with existing attendance or an open clock-in. No existing record will be overwritten.');
                }
                $key=$uid.'|'.$shift;$signature=$timeIn.'|'.$timeOut.'|'.$breakHours;
                if(isset($seen[$key])){
                    if($seen[$key]===$signature){$status='duplicate';$message='Repeated row in this file; will be skipped.';}
                    else throw new DomainException('This file contains conflicting rows for the same employee and shift date.');
                }
                if($status==='ready')foreach($intervals[$uid]??[] as [$previousIn,$previousOut]){
                    if($timeIn<$previousOut&&$timeOut>$previousIn)throw new DomainException('This file contains overlapping attendance for the same employee.');
                }
                $seen[$key]=$signature;
                if($status==='ready')$intervals[$uid][]=[$timeIn,$timeOut];
                if($status==='ready'){
                    $payslip=$this->db->table('employee_payslips')->where('employee_id',$eid)->where('period_start <=',$shift)->where('period_end >=',$shift)->countAllResults();
                    $paid=$this->db->table('payroll')->where('employee_id',$eid)->where('month_year',substr($shift,0,7))->where('status','Paid')->countAllResults();
                    if($payslip||$paid)throw new DomainException('This period has a saved payslip or paid payroll. Resolve it through the payroll review process first.');
                    $records[]=['row'=>$number,'attendance'=>$record];
                }
                $view['status']=$status;$view['message']=$message;
            }catch(DomainException|\InvalidArgumentException $e){$view['status']='error';$view['message']=$e->getMessage();}
            $summary[$view['status']]++;$display[]=$view;
        }
        if(!$display)throw new DomainException('The file contains no attendance rows.');
        $result=['success'=>true,'headers'=>$headers,'fields'=>array_map(fn($key,$f)=>['key'=>$key,'label'=>$f['label'],'required'=>$f['required']],array_keys(self::fields()),self::fields()),
            'guess'=>$mapping,'unmatched_employees'=>array_map(fn($value,$count)=>['value'=>(string)$value,'count'=>$count,'label'=>$unmatchedLabels[$value]??(string)$value],array_keys($unmatched),array_values($unmatched)),
            'employees'=>array_values(array_map(fn($e)=>['id'=>(int)$e['id'],'label'=>$e['employee_number'].' — '.$e['last_name'].', '.$e['first_name']],array_filter($employees,fn($e)=>(int)$e['active']&&$e['deleted_at']===null))),
            // Import is no longer all-or-nothing: any row-level error only removes
            // that row from $records, so we can still commit whatever is ready.
            'rows'=>$display,'summary'=>$summary,'can_import'=>$summary['ready']>0,'records'=>$records,'column_map'=>$mapping,
            'file_hash'=>hash_file('sha256',$path),'date_order'=>$dateOrder,'break_unit'=>$breakUnit,
            'period_start'=>$periodStart,'period_end'=>$periodEnd];
        $result['source_columns']=array_map(static function($header,$index)use($rows){
            $samples=[];
            foreach($rows as $row){$value=trim((string)($row[$index]??''));if($value!==''&&!in_array($value,$samples,true))$samples[]=mb_substr($value,0,100);if(count($samples)===3)break;}
            return ['header'=>$header,'letter'=>\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index+1),'samples'=>$samples];
        },$headers,array_keys($headers));
        $result['plan_hash']=hash('sha256',json_encode([$result['file_hash'],$mapping,$employeeMap,$dateOrder,$breakUnit,$periodStart,$periodEnd,$records,$display],JSON_THROW_ON_ERROR));
        return $result;
    }

    public function commit(string $path,string $extension,array $columns,array $employees,string $dateOrder,string $breakUnit,string $expectedHash,?string $periodStart=null,?string $periodEnd=null,?string $originalFilename=null,?int $importedBy=null): array
    {
        $plan=$this->preview($path,$extension,$columns,$employees,$dateOrder,$breakUnit,$periodStart,$periodEnd);
        if(!$plan['can_import']||!hash_equals($expectedHash,$plan['plan_hash']))throw new DomainException('The file or attendance data changed. Preview again before importing.');
        $uids=array_values(array_unique(array_map(fn($r)=>(int)$r['attendance']['user_id'],$plan['records'])));sort($uids);
        $this->db->transBegin();
        try{
            foreach($uids as $uid)$this->db->query('SELECT id FROM users WHERE id = ? FOR UPDATE',[$uid])->getRowArray();
            $checked=$this->preview($path,$extension,$columns,$employees,$dateOrder,$breakUnit,$periodStart,$periodEnd);
            if(!$checked['can_import']||!hash_equals($expectedHash,$checked['plan_hash']))throw new DomainException('Attendance changed during confirmation. Preview again.');
            $batch=bin2hex(random_bytes(12));
            foreach($checked['records'] as $item){
                if(!$this->db->table('attendance')->insert($item['attendance']))throw new RuntimeException('Unable to save imported attendance.');
                $id=(int)$this->db->insertID();
                if(!(($this->audit)('Timesheet import '.$batch,$id,$item['attendance']+['import_batch'=>$batch,'source_row'=>$item['row'],'source_sha256'=>$checked['file_hash']])))throw new RuntimeException('Unable to save import audit.');
            }
            if(!$this->db->transStatus()||!$this->db->transCommit())throw new RuntimeException('Import could not be committed.');
            $skipped=$checked['summary']['duplicate']+$checked['summary']['error']+$checked['summary']['pending']+$checked['summary']['out_of_period'];
            $message=count($checked['records']).' rows imported; '.$skipped.' skipped ('.$checked['summary']['duplicate'].' duplicate, '.$checked['summary']['error'].' invalid, '.$checked['summary']['pending'].' still open, '.$checked['summary']['out_of_period'].' outside the selected period).';

            // Recorded after the real commit, deliberately outside that
            // transaction: a history-logging hiccup must never be the reason an
            // otherwise-successful attendance import reports as failed.
            try {
                (new \App\Models\Client\ImportBatchModel())->record(
                    'timesheet', $originalFilename, $checked['file_hash'], $importedBy,
                    count($checked['rows']), count($checked['records']),
                    (int) $checked['summary']['duplicate'], (int) $checked['summary']['error'],
                    (int) $checked['summary']['pending'] + (int) $checked['summary']['out_of_period'],
                    ['batch' => $batch, 'period_start' => $periodStart, 'period_end' => $periodEnd]
                );
            } catch (Throwable $e) {
                log_message('error', 'Import history record failed (timesheet import itself succeeded): {message}', ['message' => $e->getMessage()]);
            }

            return ['success'=>true,'imported'=>count($checked['records']),'skipped'=>$skipped,'batch'=>$batch,'message'=>$message];
        }catch(Throwable $e){$this->db->transRollback();throw $e;}
    }
}
