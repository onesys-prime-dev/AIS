<?php
namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\AttendanceSessionService;
use DomainException;
use Throwable;

class AttendanceController extends BaseController
{
    private function perform(callable $operation)
    {
        if (!auth()->user()) return redirect()->to(site_url('login'));
        $expected = session()->get('attendance_csrf');
        $submitted = $this->request->getPost('attendance_token');
        if (!is_string($expected) || !is_string($submitted) || !hash_equals($expected, $submitted)) {
            return $this->response->setStatusCode(403)->setBody('Reload the dashboard before recording attendance.');
        }
        try {
            return $operation(new AttendanceSessionService(), (int) auth()->id());
        } catch (DomainException|\InvalidArgumentException $e) {
            return redirect()->to(site_url('client_portal/dashboard'))->with('error', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Attendance transition failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('client_portal/dashboard'))->with('error', 'Attendance was not saved. Please reload and try again.');
        }
    }

    private function expectedVersion(): int
    {
        $value = filter_var($this->request->getPost('state_version'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($value === false) throw new DomainException('Reload the dashboard before recording attendance.');
        return $value;
    }

    public function timeIn()
    {
        return $this->perform(function($service, $userId) {
            $service->clockIn($userId);
            return redirect()->to(site_url('client_portal/dashboard'))->with('success', 'Time in recorded!');
        });
    }

    public function timeOut($id)
    {
        return $this->perform(function($service, $userId) use ($id) {
            $row = $service->clockOut($userId, (int) $id, $this->expectedVersion(),
                trim((string) $this->request->getPost('unapproved_ut_reason')),
                trim((string) $this->request->getPost('missed_clockout_reason')));
            $response = redirect()->to(site_url('client_portal/dashboard'))->with('success', 'Time out recorded!');
            if (!empty($row['missed_clockout'])) $response->with('prompt_ot_request', ['date' => $row['shift_date'] ?? substr($row['time_in'], 0, 10), 'reason' => $row['missed_clockout_reason']]);
            return $response;
        });
    }

    public function toggleBreak($id)
    {
        // Older bookmarks and prefetches are read-only. The dashboard submits an explicit POST action.
        if (!$this->request->is('post')) return redirect()->to(site_url('client_portal/dashboard'));
        return $this->perform(function($service, $userId) use ($id) {
            $action = (string) $this->request->getPost('break_action');
            $service->changeBreak($userId, (int) $id, $action, $this->expectedVersion());
            return redirect()->to(site_url('client_portal/dashboard'))->with('success', $action === 'start' ? 'Break started!' : 'Break ended!');
        });
    }
}