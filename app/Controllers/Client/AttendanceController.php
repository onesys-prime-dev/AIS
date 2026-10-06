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
            return $this->backWithError($e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Attendance transition failed: {message}', ['message' => $e->getMessage()]);
            return $this->backWithError('Attendance was not saved. Please reload and try again.');
        }
    }

    /**
     * Error toast carried on the redirect URL (read and stripped by toast.js),
     * not just session flashdata — the Dashboard's own notification/presence
     * polling can use up a one-shot flash before the page reads it, which
     * would make a blocked clock-in (e.g. outside the office network) fail
     * silently. Flashdata is kept too as a fallback. Same approach as
     * RequestController::backWithToast().
     */
    private function backWithError(string $message)
    {
        $target = site_url('client_portal/dashboard') . '?toast_error=' . rawurlencode($message);
        return redirect()->to($target)->with('error', $message);
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
            $service->clockIn($userId, $this->request->getIPAddress());
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
            // Set by the dashboard's own countdown when it auto-submits End Break
            // right as the scheduled length runs out — same end action as a manual
            // click, just flagged so the redirect can explain what happened.
            $autoEnded = $action === 'end' && $this->request->getPost('auto_ended') === '1';
            try {
                $service->changeBreak($userId, (int) $id, $action, $this->expectedVersion(), $this->request->getIPAddress());
            } catch (DomainException $e) {
                if ($e->getMessage() === 'All scheduled breaks have been used.') {
                    return redirect()->to(site_url('client_portal/dashboard'))->with('break_limit_reached', true);
                }
                throw $e;
            }
            if ($autoEnded) {
                return redirect()->to(site_url('client_portal/dashboard'))->with('break_auto_ended', true);
            }
            return redirect()->to(site_url('client_portal/dashboard'))->with('success', $action === 'start' ? 'Break started!' : 'Break ended!');
        });
    }
}