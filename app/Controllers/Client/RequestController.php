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

    public function index()
    {
        $user = auth()->user();
 
        if (! $user) {
            return redirect()->to('/login');
        }
 
        if ($user->inGroup('client_payroll_staff')) {
 
            $data['requests'] = $this->requestModel
                ->select("requests.*, employees.employee_number, ea.photo_path,
                    COALESCE(NULLIF(CONCAT(employees.last_name, ', ', employees.first_name, CASE WHEN employees.middle_name IS NOT NULL AND TRIM(employees.middle_name) != '' THEN CONCAT(' ', UPPER(LEFT(TRIM(employees.middle_name), 1)), '.') ELSE '' END), ''), users.username) as requested_by_name")
                ->join('users', 'users.id = requests.requested_by', 'left')
                ->join('employees', 'employees.user_id = requests.requested_by', 'left')
                ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
                ->orderBy('requests.created_at', 'DESC')
                ->findAll();
 
            // Approval codes with generator + consumer names
            $data['approvalCodes'] = $this->approvalCodeModel
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
                ->findAll();
 
        } else {
 
            $data['requests'] = $this->requestModel
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
        if (!$user->inGroup('client_payroll_staff')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $status = trim((string) $this->request->getPost('status'));
        $reviewMessage = trim((string) $this->request->getPost('review_message'));

        if (! in_array($status, ['approved', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Invalid request status.');
        }

        if ($status === 'rejected' && $reviewMessage === '') {
            return redirect()->back()->with('error', 'Please enter a rejection message before rejecting the request.');
        }

        // Fetch old state before updating
        $old = $this->requestModel->find($id);

        if (! $old) {
            return redirect()->back()->with('error', 'Request not found.');
        }

        $this->requestModel->update($id, [
            'status'         => $status,
            'processed_by'   => $user->id,
            'review_message' => $reviewMessage !== '' ? $reviewMessage : null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // Handle leave balance when a leave request changes status
        if ($old['request_type'] === 'leave') {
            $requestedBy   = (int) $old['requested_by'];
            $days          = (float) $old['duration'];
            $year          = (int) date('Y', strtotime($old['start_date']));
            $leaveCategory = (string) ($old['leave_category'] ?? 'leave');

            $isPaid = $this->leaveBalanceModel->applyRequestStatusChange(
                $requestedBy,
                (string) $old['request_type'],
                $days,
                $status,
                (string) ($old['status'] ?? null),
                $year,
                $leaveCategory
            );

            if ($status === 'approved' && $old['status'] !== 'approved') {
                $this->requestModel->update($id, ['is_paid_leave' => $isPaid ? 1 : 0]);
            } elseif ($status === 'rejected' && $old['status'] === 'approved') {
                $this->requestModel->update($id, ['is_paid_leave' => null]);
            }
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

        return redirect()->back()->with('success', "Request $status successfully.");
    }

    public function generateApprovalCode()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated.'])->setStatusCode(401);
        }

        if (! $user->inGroup('client_payroll_staff')) {
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
                return redirect()->back()->withInput()->with('error', 'Please enter an approval code.');
            }

            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);
            if ($codeRow === null) {
                return redirect()->back()->withInput()->with('error', 'The approval code is invalid or has already been used.');
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
                return redirect()->back()->withInput()->with('error', 'Invalid request type.');
            }
            if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                return redirect()->back()->withInput()->with('error', 'Start date is required.');
            }
            $startDateObj = date_create($startDate);
            if (! $startDateObj) {
                return redirect()->back()->withInput()->with('error', 'Invalid start date.');
            }
            $endDateObj = null;
            if ($endDate !== '') {
                $endDateObj = date_create($endDate);
                if ($endDateObj && $endDateObj < $startDateObj) {
                    return redirect()->back()->withInput()->with('error', 'End date cannot be earlier than start date.');
                }
            }
            if ($reason === '') {
                return redirect()->back()->withInput()->with('error', 'Message is required.');
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

            $leaveCategory = 'leave';
            if ($requestType === 'leave') {
                $lc = trim((string) $this->request->getPost('leave_category'));
                if (in_array($lc, ['leave', 'vacation', 'sick', 'emergency'], true)) {
                    $leaveCategory = $lc;
                }
            }

            $insertData = [
                'requested_by'   => $userId,
                'request_type'   => $requestType,
                'leave_category' => $leaveCategory,
                'start_date'     => $startDate,
                'end_date'       => $endDate !== '' ? $endDate : null,
                'duration'       => $duration,
                'approval_code'  => $normalizedCode,
                'reason'         => $reason,
                'status'         => 'approved',
                'processed_by'   => $codeRow['generated_by'],
                'review_message' => $finalReviewMessage,
            ];

            $this->requestModel->insert($insertData);
            $newRequestId = (int) $this->requestModel->getInsertID();

            service('activityLogger')->requestFiled($newRequestId, $insertData);
            $this->approvalCodeModel->markUsed((int) $codeRow['id'], $userId);
            service('activityLogger')->requestApprovalCodeUsed($newRequestId);

            // Deduct leave balance for approved leave requests
            if ($insertData['request_type'] === 'leave') {
                $year   = (int) date('Y', strtotime($insertData['start_date']));
                $isPaid = $this->leaveBalanceModel->applyRequestStatusChange(
                    $userId,
                    (string) $insertData['request_type'],
                    (float) $duration,
                    'approved',
                    null,
                    $year,
                    $leaveCategory
                );
                $this->requestModel->update($newRequestId, ['is_paid_leave' => $isPaid ? 1 : 0]);
            }

            $message = 'Request submitted and automatically approved via approval code.';

            return redirect()->back()->with('success', $message);
        }

        // ── NORMAL MODE: full validation as before ────────────────────────────
        $requestType = trim((string) $this->request->getPost('request_type'));
        $startDate   = trim((string) $this->request->getPost('start_date'));
        $endDate     = trim((string) $this->request->getPost('end_date'));
        $reason      = trim((string) $this->request->getPost('reason'));
        $presetDuration = null;

        // Validate request type
        if (! in_array($requestType, $allowedTypes, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid request type.');
        }

        // Validate start date
        if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return redirect()->back()->withInput()->with('error', 'Start date is required and must use YYYY-MM-DD format.');
        }

        $startDateObj = date_create($startDate);
        if (! $startDateObj) {
            return redirect()->back()->withInput()->with('error', 'Invalid start date.');
        }

        // Validate end date
        $endDateObj = null;
        if ($endDate !== '') {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return redirect()->back()->withInput()->with('error', 'End date must use YYYY-MM-DD format.');
            }
            $endDateObj = date_create($endDate);
            if (! $endDateObj) {
                return redirect()->back()->withInput()->with('error', 'Invalid end date.');
            }
            if ($endDateObj < $startDateObj) {
                return redirect()->back()->withInput()->with('error', 'End date cannot be earlier than start date.');
            }
        }

        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Message is required.');
        }

        // Optional approval code in normal mode (no preset required — just auto-approves)
        $status  = 'pending';
        $codeRow = null;
        $presetReviewMessage = null;

        if ($normalizedCode !== '') {
            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);
            if ($codeRow === null) {
                return redirect()->back()->withInput()->with('error', 'The approval code is invalid or has already been used.');
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

        $leaveCategory = 'leave';
        if ($requestType === 'leave') {
            $lc = trim((string) $this->request->getPost('leave_category'));
            if (in_array($lc, ['leave', 'vacation', 'sick', 'emergency'], true)) {
                $leaveCategory = $lc;
            }
        }

        $insertData = [
            'requested_by'   => $userId,
            'request_type'   => $requestType,
            'leave_category' => $leaveCategory,
            'start_date'     => $startDate,
            'end_date'       => $endDate !== '' ? $endDate : null,
            'duration'       => $duration,
            'approval_code'  => $normalizedCode !== '' ? $normalizedCode : null,
            'reason'         => $reason,
            'status'         => $status,
            'review_message' => $presetReviewMessage !== null
                ? $presetReviewMessage
                : ($status === 'approved' && $normalizedCode !== '' ? 'Approved via approval code ' . $normalizedCode : null),
        ];

        if ($status === 'approved' && $codeRow !== null) {
            $insertData['processed_by'] = $codeRow['generated_by'];
        }

        $this->requestModel->insert($insertData);
        $newRequestId = (int) $this->requestModel->getInsertID();

        service('activityLogger')->requestFiled($newRequestId, $insertData);

        if ($codeRow !== null) {
            $this->approvalCodeModel->markUsed((int) $codeRow['id'], $userId);
            service('activityLogger')->requestApprovalCodeUsed($newRequestId);
        }

        // Deduct leave balance for approved leave requests
        if ($status === 'approved' && $insertData['request_type'] === 'leave') {
            $year   = (int) date('Y', strtotime($insertData['start_date']));
            $isPaid = $this->leaveBalanceModel->applyRequestStatusChange(
                $userId,
                (string) $insertData['request_type'],
                (float) $duration,
                $status,
                null,
                $year,
                $leaveCategory
            );
            $this->requestModel->update($newRequestId, ['is_paid_leave' => $isPaid ? 1 : 0]);
        }

        if ($status === 'approved') {
            $message = 'Request submitted and automatically approved via approval code.';
        } else {
            $message = 'Request submitted successfully.';
        }

        return redirect()->back()->with('success', $message);
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