<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class HolidayModel extends Model
{
    protected $table         = 'client_holidays';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['client_id', 'holiday_date', 'name', 'type', 'is_recurring'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function isHoliday(string $date): ?array
    {
        $md = substr($date, 5); // MM-DD

        return $this->where('holiday_date', $date)
                    ->orGroupStart()
                        ->where('is_recurring', 1)
                        ->where("DATE_FORMAT(holiday_date,'%m-%d')", $md)
                    ->groupEnd()
                    ->first();
    }

    public function forYear(int $year): array
    {
        return $this->where("YEAR(holiday_date)", $year)
                    ->orWhere('is_recurring', 1)
                    ->orderBy("DATE_FORMAT(holiday_date, '%m-%d') ASC", '', false)
                    ->findAll();
    }
}
