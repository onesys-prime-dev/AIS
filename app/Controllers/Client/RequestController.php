<?php

namespace App\Controllers\Client;

use App\Models\Client\RequestModel;
use App\Models\Client\ApprovalCodeModel;
use App\Models\Client\LeaveBalanceModel;
use App\Services\Client\RequestNotificationService;
use App\Controllers\BaseController;

class RequestController extends BaseController
{
    protected $requestModel;
    protected $approvalCodeModel;
    protected $leaveBalanceModel;
    protected RequestNotificationService $requestNotificationService;

    public function __construct()
    {
        $this->requestModel      = new RequestModel();
        $this->approvalCodeModel = new ApprovalCodeModel();
        $this->leaveBalanceModel = new LeaveBalanceModel();
        $this->requestNotificationService = new RequestNotificationService();
    }

    /**
     * Back-redirect + error, shown reliably even though this form is a plain
     * (non-AJAX) POST whose only feedback channel is a page reload.
     *
     * BL-046: a stale "code accepted" hint (checked once on blur, not
     * re-checked at submit) let an employee submit an already-used approval
     * code; the server correctly rejected it via redirect()->back()->
     * with('error', ...), but that relies on session flashdata, which
     * survives exactly one further request — the Dashboard's own
     * notification/presence polling can win that race and use it up first,
     * so the reload could show no error at all. The toast is now also
     * carried on the redirect URL itself (read and stripped by toast.js),
     * independent of session timing; flashdata is kept too as a harmless
     * fallback for anything still reading it directly.
     */
    private function backWithError(string $message)
    {
        return $this->backWithToast('error', $message)->withInput();
    }

    private function backWithSuccess(string $message)
    {
        return $this->backWithToast('success', $message);
    }

    private function backWithToast(string $type, string $message)
    {
        $target = (string) previous_url();
        $target .= (str_contains($target, '?') ? '&' : '?') . 'toast_' . $type . '=' . rawurlencode($message);

        return redirect()->to($target)->with($type, $message);
    }

    public function index()
    {
        $user = auth()->user();
 
        if (! $user) {
            return redirect()->to('/login');
        }
 
        if (\App\Libraries\PortalAccess::reviewsRequests($user)) {
 
            $data['requests'] = \App\Libraries\PortalAccess::scopeRequests($this->requestModel)
                ->select("requests.*, employees.employee_number, ea.photo_path,
                    COALESCE(NULLIF(CONCAT(employees.last_name, ', ', employees.first_name, CASE WHEN employees.middle_name IS NOT NULL AND TRIM(employees.middle_name) != '' THEN CONCAT(' ', UPPER(LEFT(TRIM(employees.middle_name), 1)), '.') ELSE '' END), ''), users.username) as requested_by_name")
                ->join('users', 'users.id = requests.requested_by', 'left')
                ->join('employees', 'employees.user_id = requests.requested_by', 'left')
                ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
                ->orderBy('requests.created_at', 'DESC')
                ->findAll();
 
            // Approval codes with generator + consumer names
            $data['approvalCodes'] = \App\Libraries\PortalAccess::can('request_codes') ? $this->approvalCodeModel
                ->select("
                    approval_codes.*,
                    COALESCE(NULLIF(CONCAT(ge.last_name, ', ', ge.first_name), ', '), gu.username) AS generated_by_name,
                    COALESCE(NULLIF(CONCAT(ue.last_name, ', ', ue.first_name), ', '), uu.username) AS used_by_name
                ")
                ->join('users      AS gu', 'gu.id      = approval_codes.generated_by', 'left')
                ->join('employees  AS ge', 'ge.user_id = approval_codes.generated_by', 'left')
                ->join('users      AS uu', 'uu.id      = approval_codes.used_by',      'left')
                ->join('employees  AS ue', 'ue.user_id = approval_codes.used_by',      'left')
                ->orderBy('approval_codes.created_at', 'DESC')
                ->findAll() : [];
 
        } else {
 
            $data['requests'] = \App\Libraries\PortalAccess::scopeRequests($this->requestModel)
                ->select("requests.*, employees.employee_number,
                    COALESCE(NULLIF(CONCAT(employees.last_name, ', ', employees.first_name, CASE WHEN employees.middle_name IS NOT NULL AND TRIM(employees.middle_name) != '' THEN CONCAT(' ', UPPER(LEFT(TRIM(employees.middle_name), 1)), '.') ELSE '' END), ''), users.username) as requested_by_name")
                ->join('users', 'users.id = requests.requested_by', 'left')
                ->join('employees', 'employees.user_id = requests.requested_by', 'left')
                ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
                ->where('requested_by', $user->id)
                ->orderBy('requests.created_at', 'DESC')
                ->findAll();
 
            $data['approvalCodes'] = [];
        }
 
        foreach ($data['requests'] as &$request) {
            if (! empty($request['photo_path'])) {
                $request['photo_path'] = base_url((string) $request['photo_path']);
            }
        }
        unset($request);
 
        return view('client_portal/requests', $data);
    }

    public function updateStatus($id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        // Only payroll staff can approve/reject
        if (!\App\Libraries\PortalAccess::canReviewRequest((int) $id, $user)) {
            return $this->respond(false, 'Unauthorized action.');
        }

        $status = trim((string) $this->request->getPost('status'));
        $reviewMessage = trim((string) $this->request->getPost('review_message'));

        if (! in_array($status, ['approved', 'rejected'], true)) {
            return $this->respond(false, 'Invalid request status.');
        }

        if ($status === 'rejected' && $reviewMessage === '') {
            return $this->respond(false, 'Please enter a rejection message before rejecting the request.');
        }

        // Fetch old state before updating
        $old = $this->requestModel->find($id);

        if (! $old) {
            return $this->respond(false, 'Request not found.');
        }

        $leaveCategory = (string) ($old['leave_category'] ?? 'leave');
        $endDate       = (string) ($old['end_date'] ?? $old['start_date']);
        // The stored duration stays authoritative for how many days are charged
        // (it's the same figure payroll pays out); the dates only decide how it
        // splits across calendar years.
        $leaveDuration = (float) ($old['duration'] ?? 0);
        $isLeaveApproval = $old['request_type'] === 'leave' && $status === 'approved' && $old['status'] !== 'approved';

        // Checked BEFORE writing anything: an approval that would exceed the
        // employee's remaining balance is refused outright with the reason,
        // rather than silently going through as unpaid leave with no signal
        // to the reviewer that anything was short.
        if ($isLeaveApproval) {
            $check = $this->leaveBalanceModel->checkSufficientBalance(
                (int) $old['requested_by'],
                $leaveCategory,
                (string) $old['start_date'],
                $endDate,
                $leaveDuration
            );
            if (! $check['sufficient']) {
                return $this->respond(false, 'Cannot approve — insufficient leave balance (' . implode('; ', $check['shortfalls']) . ').');
            }
        }

        // The status update and the balance deduction/refund must land together
        // or not at all — previously these were separate, unguarded writes, so
        // a failure partway through could leave a request marked approved with
        // its balance never touched, or vice versa.
        $db = db_connect();
        $db->transBegin();
        try {
            $this->requestModel->update($id, [
                'status'         => $status,
                'processed_by'   => $user->id,
                'review_message' => $reviewMessage !== '' ? $reviewMessage : null,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            if ($old['request_type'] === 'leave') {
                if ($isLeaveApproval) {
                    // Returns false when the balance was consumed between the
                    // pre-check above and this deduction — roll back instead of
                    // approving against a balance that's no longer there.
                    if (! $this->leaveBalanceModel->deductAcrossYears((int) $old['requested_by'], $leaveCategory, (string) $old['start_date'], $endDate, $leaveDuration)) {
                        throw new \RuntimeException('Leave balance was consumed by another approval.');
                    }
                    $this->requestModel->update($id, ['is_paid_leave' => 1]);
                } elseif ($status === 'rejected' && $old['status'] === 'approved') {
                    $this->leaveBalanceModel->refundAcrossYears((int) $old['requested_by'], $leaveCategory, (string) $old['start_date'], $endDate, $leaveDuration);
                    $this->requestModel->update($id, ['is_paid_leave' => 0]);
                }
            }

            if (! $db->transStatus()) throw new \RuntimeException('Request status update failed.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Request status update failed: {message}', ['message' => $e->getMessage()]);
            return $this->respond(false, 'Could not update the request. Please try again.');
        }

        $recipientUserId = (int) ($old['requested_by'] ?? 0);
        $requestType = (string) ($old['request_type'] ?? 'request');

        $this->requestNotificationService->createStatusNotification(
            (int) $id,
            $recipientUserId,
            (int) $user->id,
            $status,
            $requestType,
            $reviewMessage !== '' ? $reviewMessage : null
        );

        service('activityLogger')->requestStatusChanged($id, $old['status'] ?? 'pending', $status, $user->id, $reviewMessage !== '' ? $reviewMessage : null);

        return $this->respond(true, "Request $status successfully.");
    }

    /**
     * Send a success/error confirmation for updateStatus(). The review modal
     * submits via AJAX so a denial (e.g. no permission to review this
     * request) comes back as JSON to show as a toast, instead of the browser
     * navigating to a raw JSON body as if it were the page — the bug this
     * replaced. Non-AJAX submissions fall back to the previous redirect+flash.
     */
    private function respond(bool $ok, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $ok, 'message' => $message]);
        }

        return $ok
            ? redirect()->back()->with('success', $message)
            : redirect()->back()->with('error', $message);
    }

    public function generateApprovalCode()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated.'])->setStatusCode(401);
        }

        if (! \App\Libraries\PortalAccess::can('request_codes', $user)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized.'])->setStatusCode(403);
        }

        // --- Parse optional preset_data from request body ---
        $rawBody   = $this->request->getBody();
        $bodyJson  = json_decode($rawBody ?: '{}', true);
        $presetRaw = $bodyJson['preset_data'] ?? null;

        $presetData = null;
        if (is_array($presetRaw) && ! empty($presetRaw)) {
            $pDuration = isset($presetRaw['duration']) ? (int) $presetRaw['duration'] : null;
            $pReview   = isset($presetRaw['review_message']) ? trim((string) $presetRaw['review_message']) : null;

            $pd = [];
            if ($pDuration !== null && $pDuration >= 1) {
                $pd['duration'] = $pDuration;
            }
            if ($pReview !== null && $pReview !== '') {
                $pd['review_message'] = $pReview;
            }

            if (! empty($pd)) {
                $presetData = json_encode($pd);
            }
        }

        // XXXX-XXXX-XXXX-XXXX
        // Uses only uppercase letters + digits, excluding look-alike characters (0/O, 1/I/L).
        $code = $this->buildUniqueCode();

        if ($code === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Could not generate a unique code. Please try again.',
            ])->setStatusCode(500);
        }

        $insertId = $this->approvalCodeModel->insert([
            'code'         => $code,
            'generated_by' => $user->id,
            'is_used'      => 0,
            'preset_data'  => $presetData,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if (! $insertId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save approval code.',
            ])->setStatusCode(500);
        }

        $generatedByName = db_connect()->table('users AS u')
            ->select("COALESCE(NULLIF(CONCAT(e.last_name, ', ', e.first_name), ', '), u.username) AS generated_by_name", false)
            ->join('employees AS e', 'e.user_id = u.id', 'left')
            ->where('u.id', (int) $user->id)
            ->get()
            ->getRowArray();

        // logs the generation of approval code
        service('activityLogger')->log(
            'created',
            'approval_codes',
            'Approval code generated by payroll staff',
            (int) $insertId,
            null,
            ['generated_by' => $user->id, 'code' => $code]
        );

        return $this->response->setJSON([
            'success'     => true,
            'code'        => $code,
            'generated_by_name' => (string) ($generatedByName['generated_by_name'] ?? ($user->username ?? 'User')),
            'preset_data' => $presetData ? json_decode($presetData, true) : null,
        ]);
    }

    public function create()
    {
        return view('client_portal/dashboard');
    }

    /**
     * AJAX endpoint — returns whether a code is valid and its preset_data (if any).
     * Called by the frontend on blur of the approval code input.
     * GET client_portal/requests/lookup-code?code=XXXX-XXXX-XXXX-XXXX
     */
    public function lookupCode()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated.'])->setStatusCode(401);
        }

        $code    = strtoupper(trim((string) $this->request->getGet('code')));
        $codeRow = $code !== '' ? $this->approvalCodeModel->findActiveCode($code) : null;

        if ($codeRow === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid or already-used code.',
            ]);
        }

        $preset = ! empty($codeRow['preset_data'])
            ? json_decode((string) $codeRow['preset_data'], true)
            : null;

        return $this->response->setJSON([
            'success'     => true,
            'preset_data' => is_array($preset) ? $preset : (object)[],
        ]);
    }

    /**
     * Redeems an approval code and inserts an already-approved request,
     * atomically: claims the code (failing loudly if it was just used by
     * someone else), confirms leave balance sufficiency up front, then
     * inserts the request and deducts the balance together in one
     * transaction. Previously each of these was a separate, unguarded write —
     * both here and via a near-identical second copy of this logic further
     * down in store() — so two people submitting the same single-use code at
     * once could both succeed, and an approval could go through even when the
     * employee didn't have enough balance for it.
     *
     * @return array{success: bool, message?: string, request_id?: int}
     */
    private function redeemApprovalCode(array $codeRow, array $insertData, string $leaveCategory, int $userId): array
    {
        $isLeave  = $insertData['request_type'] === 'leave';
        $endDate  = (string) ($insertData['end_date'] ?? $insertData['start_date']);
        // Stored duration stays authoritative for the amount charged; the dates
        // only decide how it splits across calendar years.
        $duration = (float) ($insertData['duration'] ?? 0);

        if ($isLeave) {
            $check = $this->leaveBalanceModel->checkSufficientBalance($userId, $leaveCategory, (string) $insertData['start_date'], $endDate, $duration);
            if (! $check['sufficient']) {
                return ['success' => false, 'message' => 'This code would approve leave the employee doesn\'t have balance for (' . implode('; ', $check['shortfalls']) . ').'];
            }
        }

        $db = db_connect();
        $db->transBegin();
        try {
            if (! $this->approvalCodeModel->claim((int) $codeRow['id'], $userId)) {
                $db->transRollback();
                return ['success' => false, 'message' => 'This approval code was just used by someone else. Please request a new code.'];
            }

            $this->requestModel->insert($insertData);
            $newRequestId = (int) $this->requestModel->getInsertID();
            // Guarded explicitly: a model-level validation failure returns false
            // without registering a DB error, so transStatus() alone wouldn't
            // catch it — and rolling back matters here, since the code has
            // already been claimed inside this same transaction.
            if ($newRequestId <= 0) throw new \RuntimeException('Request row was not created.');

            if ($isLeave) {
                // False means the balance went in the meantime — roll back so a
                // code isn't burned on an approval that can't be funded.
                if (! $this->leaveBalanceModel->deductAcrossYears($userId, $leaveCategory, (string) $insertData['start_date'], $endDate, $duration)) {
                    $db->transRollback();
                    return ['success' => false, 'message' => 'The employee\'s leave balance changed while this was being submitted. Please try again.'];
                }
                $this->requestModel->update($newRequestId, ['is_paid_leave' => 1]);
            }

            if (! $db->transStatus()) throw new \RuntimeException('Approval-code request could not be saved.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Approval-code request failed: {message}', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not save the request. Please try again.'];
        }

        service('activityLogger')->requestFiled($newRequestId, $insertData);
        service('activityLogger')->requestApprovalCodeUsed($newRequestId);

        return ['success' => true, 'request_id' => $newRequestId];
    }

    public function store()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $userId       = $user->id;
        $allowedTypes = ['leave', 'overtime', 'undertime'];

        // approval_code comes from two possible inputs depending on which field the user filled
        $approvalCode   = trim((string) ($this->request->getPost('approval_code') ?: $this->request->getPost('approval_code_optional')));
        $normalizedCode = $approvalCode !== '' ? strtoupper($approvalCode) : '';
        $codeMode       = $this->request->getPost('code_mode') === '1';  // hidden field set by the toggle

        // ── CODE MODE: validate the code, use preset if available, else accept user fields ──
        if ($codeMode) {
            if ($normalizedCode === '') {
                return $this->backWithError('Please enter an approval code.');
            }

            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);
            if ($codeRow === null) {
                return $this->backWithError('The approval code is invalid or has already been used.');
            }

            // Decode preset data (may be empty / null)
            $preset = ! empty($codeRow['preset_data'])
                ? json_decode((string) $codeRow['preset_data'], true)
                : [];

            $hasPreset = is_array($preset) && ! empty($preset);

            // Collect user-submitted fields (always POSTed since the fields are always visible)
            $requestType = trim((string) $this->request->getPost('request_type'));
            $startDate   = trim((string) $this->request->getPost('start_date'));
            $endDate     = trim((string) $this->request->getPost('end_date'));
            $reason      = trim((string) $this->request->getPost('reason'));
            $presetDuration = null;

            if ($hasPreset && ! empty($preset['duration']) && (int) $preset['duration'] > 0) {
                $presetDuration = (int) $preset['duration'];
            }

            // Validate fields (whether they came from preset or user input)
            if (! in_array($requestType, $allowedTypes, true)) {
                return $this->backWithError('Invalid request type.');
            }
            if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                return $this->backWithError('Start date is required.');
            }
            $startDateObj = date_create($startDate);
            if (! $startDateObj) {
                return $this->backWithError('Invalid start date.');
            }
            $endDateObj = null;
            if ($endDate !== '') {
                $endDateObj = date_create($endDate);
                if ($endDateObj && $endDateObj < $startDateObj) {
                    return $this->backWithError('End date cannot be earlier than start date.');
                }
            }
            if ($reason === '') {
                return $this->backWithError('Message is required.');
            }

            // Overtime carries the time span it covers (checked and normalised here); it is always a single day.
            $otFrom = $otTo = null;
            if ($requestType === 'overtime') {
                $ot = $this->validateOvertimeSpan((int) $userId, $startDate, (string) $this->request->getPost('ot_start_time'), (string) $this->request->getPost('ot_end_time'));
                if ($ot['error'] !== null) {
                    return $this->backWithError($ot['error']);
                }
                [$otFrom, $otTo] = [$ot['from'], $ot['to']];
                $endDate = $startDate;
                $endDateObj = clone $startDateObj;
                $presetDuration = null;
            }
            if ($presetDuration !== null) {
                $endDateObj = clone $startDateObj;
                if ($presetDuration > 1) {
                    $endDateObj->modify('+' . ($presetDuration - 1) . ' day');
                }
                $endDate  = $endDateObj->format('Y-m-d');
                $duration = $presetDuration;
            } else {
                $effectiveEndDate = $endDateObj ?? $startDateObj;
                $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;
            }

            // Determine review message: prefer preset's review_message when present
            $finalReviewMessage = null;
            if ($hasPreset && ! empty($preset['review_message'])) {
                $finalReviewMessage = $preset['review_message'];
            } else {
                $finalReviewMessage = 'Approved via approval code';
            }

            $leaveCategory = null;
            if ($requestType === 'leave') {
                $lc = trim((string) $this->request->getPost('leave_category'));
                if (! in_array($lc, ['vacation', 'sick', 'emergency'], true)) {
                    // The real submission form always supplies one of these three
                    // — reaching here means a request bypassed it (a malformed or
                    // direct API call). Refusing it here, rather than falling back
                    // to the generic 'leave' category, closes a balance blind spot:
                    // that category is deducted from like any other but isn't
                    // shown on the employee profile or Employee Management, so a
                    // request landing there would be invisible to reconcile.
                    return $this->backWithError('Please choose a leave type (Vacation, Sick, or Emergency).');
                }
                $leaveCategory = $lc;
            }

            $insertData = [
                'requested_by'   => $userId,
                'request_type'   => $requestType,
                'leave_category' => $leaveCategory ?? 'leave',
                'start_date'     => $startDate,
                'end_date'       => $endDate !== '' ? $endDate : null,
                'ot_start_time'  => $otFrom,
                'ot_end_time'    => $otTo,
                'duration'       => $duration,
                'approval_code'  => $normalizedCode,
                'reason'         => $reason,
                'status'         => 'approved',
                'processed_by'   => $codeRow['generated_by'],
                'review_message' => $finalReviewMessage,
            ];

            $result = $this->redeemApprovalCode($codeRow, $insertData, $leaveCategory ?? 'leave', $userId);
            if (! $result['success']) {
                return $this->backWithError($result['message']);
            }

            return $this->backWithSuccess('Request submitted and automatically approved via approval code.');
        }

        // ── NORMAL MODE: full validation as before ────────────────────────────
        $requestType = trim((string) $this->request->getPost('request_type'));
        $startDate   = trim((string) $this->request->getPost('start_date'));
        $endDate     = trim((string) $this->request->getPost('end_date'));
        $reason      = trim((string) $this->request->getPost('reason'));
        $presetDuration = null;

        // Validate request type
        if (! in_array($requestType, $allowedTypes, true)) {
            return $this->backWithError('Invalid request type.');
        }

        // Validate start date
        if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return $this->backWithError('Start date is required and must use YYYY-MM-DD format.');
        }

        $startDateObj = date_create($startDate);
        if (! $startDateObj) {
            return $this->backWithError('Invalid start date.');
        }

        // Validate end date
        $endDateObj = null;
        if ($endDate !== '') {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return $this->backWithError('End date must use YYYY-MM-DD format.');
            }
            $endDateObj = date_create($endDate);
            if (! $endDateObj) {
                return $this->backWithError('Invalid end date.');
            }
            if ($endDateObj < $startDateObj) {
                return $this->backWithError('End date cannot be earlier than start date.');
            }
        }

        if ($reason === '') {
            return $this->backWithError('Message is required.');
        }

        // Optional approval code in normal mode (no preset required — just auto-approves)
        $status  = 'pending';
        $codeRow = null;
        $presetReviewMessage = null;

        if ($normalizedCode !== '') {
            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);
            if ($codeRow === null) {
                return $this->backWithError('The approval code is invalid or has already been used.');
            }
            $status = 'approved';

            // If the code carries a preset, extract duration and optional review message.
            $presetReviewMessage = null;
            if (! empty($codeRow['preset_data'])) {
                $preset = json_decode((string) $codeRow['preset_data'], true);
                if (is_array($preset)) {
                    if (! empty($preset['duration']) && (int) $preset['duration'] > 0) {
                        $presetDuration = (int) $preset['duration'];
                    }
                    if (! empty($preset['review_message'])) {
                        $presetReviewMessage = (string) $preset['review_message'];
                    }
                }
            }
        }

        // Overtime carries the time span it covers (checked and normalised here); it is always a single day.
        $otFrom = $otTo = null;
        if ($requestType === 'overtime') {
            $ot = $this->validateOvertimeSpan((int) $userId, $startDate, (string) $this->request->getPost('ot_start_time'), (string) $this->request->getPost('ot_end_time'));
            if ($ot['error'] !== null) {
                return $this->backWithError($ot['error']);
            }
            [$otFrom, $otTo] = [$ot['from'], $ot['to']];
            $endDate = $startDate;
            $endDateObj = clone $startDateObj;
            $presetDuration = null;
        }
        if ($presetDuration !== null) {
            $endDateObj = clone $startDateObj;
            if ($presetDuration > 1) {
                $endDateObj->modify('+' . ($presetDuration - 1) . ' day');
            }
            $endDate  = $endDateObj->format('Y-m-d');
            $duration = $presetDuration;
        } else {
            $effectiveEndDate = $endDateObj ?? $startDateObj;
            $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;
        }

        $leaveCategory = null;
        if ($requestType === 'leave') {
            $lc = trim((string) $this->request->getPost('leave_category'));
            if (! in_array($lc, ['vacation', 'sick', 'emergency'], true)) {
                // Same reasoning as the code-mode branch above: refuse rather
                // than silently filing it under the generic 'leave' category,
                // which is deducted from but never shown anywhere for anyone
                // to reconcile against.
                return $this->backWithError('Please choose a leave type (Vacation, Sick, or Emergency).');
            }
            $leaveCategory = $lc;
        }

        $insertData = [
            'requested_by'   => $userId,
            'request_type'   => $requestType,
            'leave_category' => $leaveCategory ?? 'leave',
            'start_date'     => $startDate,
            'end_date'       => $endDate !== '' ? $endDate : null,
            'ot_start_time'  => $otFrom,
            'ot_end_time'    => $otTo,
            'duration'       => $duration,
            'approval_code'  => $normalizedCode !== '' ? $normalizedCode : null,
            'reason'         => $reason,
            'status'         => $status,
            'review_message' => $presetReviewMessage !== null
                ? $presetReviewMessage
                : ($status === 'approved' && $normalizedCode !== '' ? 'Approved via approval code ' . $normalizedCode : null),
        ];

        if ($codeRow !== null) {
            // Approved via an optional code entered in normal mode — same
            // atomic claim-check-deduct path as the explicit code-mode toggle.
            $insertData['processed_by'] = $codeRow['generated_by'];
            $result = $this->redeemApprovalCode($codeRow, $insertData, $leaveCategory ?? 'leave', $userId);
            if (! $result['success']) {
                return $this->backWithError($result['message']);
            }
            return $this->backWithSuccess('Request submitted and automatically approved via approval code.');
        }

        // No code — plain pending request, no balance touched yet.
        $this->requestModel->insert($insertData);
        $newRequestId = (int) $this->requestModel->getInsertID();
        if ($newRequestId <= 0) {
            return $this->backWithError('Your request could not be saved. Please try again.');
        }
        service('activityLogger')->requestFiled($newRequestId, $insertData);

        return $this->backWithSuccess('Request submitted successfully.');
    }


    /**
     * Checks an overtime request's time span and returns it normalised ('HH:MM:00').
     * Rules: both times valid; To after From (or past midnight, at most 12 hours); not more than 14 days ahead;
     * starts at or after the employee's scheduled shift end on a regular working day (rest days, holidays and
     * overnight shifts are not checked against the shift); never overlaps another pending/approved OT request that day.
     *
     * @return array{error: ?string, from: ?string, to: ?string}
     */
    private function validateOvertimeSpan(int $userId, string $date, string $from, string $to): array
    {
        $fail = static fn (string $message): array => ['error' => $message, 'from' => null, 'to' => null];
        $minutes = static function (string $t): ?int {
            return preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', trim($t), $m) ? (int) $m[1] * 60 + (int) $m[2] : null;
        };
        $clock = static fn (int $mins): string => date('g:i A', mktime(intdiv($mins, 60), $mins % 60));

        $fromMin = $minutes($from);
        $toMin   = $minutes($to);
        if (trim($from) === '' || trim($to) === '') {
            return $fail('Please enter the From and To time for your overtime.');
        }
        if ($fromMin === null || $toMin === null) {
            return $fail('Overtime times must be valid times (for example 18:00).');
        }
        $crossesMidnight = $toMin <= $fromMin;
        $spanMin = $crossesMidnight ? $toMin + 1440 - $fromMin : $toMin - $fromMin;
        if ($crossesMidnight && $spanMin > 12 * 60) {
            return $fail('The To time must be later than the From time (a span that goes past midnight can be at most 12 hours).');
        }

        if (strtotime($date) > strtotime('+14 days', strtotime(date('Y-m-d')))) {
            return $fail('Overtime can be filed up to 14 days ahead.');
        }

        // Overtime is extra time beyond the regular shift, so on a normal working day it has to begin when the shift ends.
        $schedule = (new \App\Services\Client\AttendanceComputationService())->getEmployeeSchedule($userId);
        if ($schedule && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
            $workDays = json_decode((string) ($schedule['working_days'] ?? ''), true);
            $isWorkDay = !is_array($workDays) || $workDays === [] || in_array(strtolower(date('D', strtotime($date))), $workDays, true);
            $isHoliday = (new \App\Models\Client\HolidayModel())->isHoliday($date);
            $shiftStart = $minutes((string) $schedule['start_time']);
            $shiftEnd   = $minutes((string) $schedule['end_time']);
            if ($isWorkDay && !$isHoliday && $shiftStart !== null && $shiftEnd !== null && $shiftEnd > $shiftStart && $fromMin < $shiftEnd) {
                return $fail('Overtime must start at or after your shift ends (' . $clock($shiftEnd) . ').');
            }
        }

        // Another pending or approved OT request on the same date: a request without a span covers the whole day.
        $others = $this->requestModel
            ->where('requested_by', $userId)
            ->where('request_type', 'overtime')
            ->where('status !=', 'rejected')
            ->where('start_date <=', $date)
            ->groupStart()->where('end_date >=', $date)->orWhere('end_date', null)->groupEnd()
            ->findAll();
        foreach ($others as $other) {
            $oFrom = $minutes((string) ($other['ot_start_time'] ?? ''));
            $oTo   = $minutes((string) ($other['ot_end_time'] ?? ''));
            // For a request that has no end date, only its own start date counts (same rule as payroll and the timesheet).
            if (empty($other['end_date']) && $other['start_date'] !== $date) continue;
            if ($oFrom === null || $oTo === null) {
                return $fail('You already have an overtime request for that date.');
            }
            $oEnd = $oTo <= $oFrom ? $oTo + 1440 : $oTo;
            $newEnd = $fromMin + $spanMin;
            if ($fromMin < $oEnd && $oFrom < $newEnd) {
                return $fail('This overlaps your other overtime request for that date (' . $clock($oFrom) . ' to ' . $clock($oTo) . ').');
            }
        }

        return ['error' => null, 'from' => sprintf('%02d:%02d:00', intdiv($fromMin, 60), $fromMin % 60), 'to' => sprintf('%02d:%02d:00', intdiv($toMin, 60), $toMin % 60)];
    }
    /**
     * Generates approval code in the format XXXX-XXXX-XXXX-XXXX.
     * Retries up to 10 times to guarantee uniqueness in the DB.
     * Returns null if all attempts fail (extremely unlikely in practice).
     */
    private function buildUniqueCode(): ?string
    {
        // Alphabet excludes visually ambiguous characters: 0, O, I, 1, L
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $alphaLen = strlen($alphabet);

        for ($attempt = 0; $attempt < 10; $attempt++) {

            $segments = [];
            for ($s = 0; $s < 4; $s++) {
                $segment = '';
                for ($c = 0; $c < 4; $c++) {
                    $segment .= $alphabet[random_int(0, $alphaLen - 1)];
                }
                $segments[] = $segment;
            }

            $code = implode('-', $segments); // e.g. "A3KM-PQ72-XYRS-84NV"

            // ensure no collision in db
            if ($this->approvalCodeModel->findActiveCode($code) === null &&
                $this->approvalCodeModel->where('code', $code)->first() === null) {
                return $code;
            }
        }

        return null;
    }
}