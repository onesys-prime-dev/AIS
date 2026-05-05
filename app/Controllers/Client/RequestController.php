<?php

namespace App\Controllers\Client;

use App\Models\Client\RequestModel;
use App\Models\Client\ApprovalCodeModel;
use App\Controllers\BaseController;

class RequestController extends BaseController
{
    protected $requestModel;
    protected $approvalCodeModel;

    public function __construct()
    {
        $this->requestModel      = new RequestModel();
        $this->approvalCodeModel = new ApprovalCodeModel();
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
            $allowedTypes = ['leave', 'overtime', 'undertime'];
            $pType        = isset($presetRaw['request_type']) && in_array($presetRaw['request_type'], $allowedTypes, true)
                            ? $presetRaw['request_type'] : null;
            $pStart       = isset($presetRaw['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $presetRaw['start_date'])
                            ? $presetRaw['start_date'] : null;
            $pEnd         = isset($presetRaw['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $presetRaw['end_date'])
                            ? $presetRaw['end_date'] : null;
            $pReason      = isset($presetRaw['reason']) ? trim((string) $presetRaw['reason']) : null;

            // Only persist preset if at least one meaningful field is set
            if ($pType !== null || $pStart !== null) {
                // Ensure end date is not before start date
                if ($pStart !== null && $pEnd !== null && $pEnd < $pStart) {
                    $pEnd = null;
                }
                $presetData = json_encode(array_filter([
                    'request_type' => $pType,
                    'start_date'   => $pStart,
                    'end_date'     => $pEnd,
                    'reason'       => $pReason !== '' ? $pReason : null,
                ], fn($v) => $v !== null));
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

            $hasPreset    = is_array($preset) && ! empty($preset);
            $presetOverride = false;

            // Collect user-submitted fields (always POSTed since the fields are always visible)
            $requestType = trim((string) $this->request->getPost('request_type'));
            $startDate   = trim((string) $this->request->getPost('start_date'));
            $endDate     = trim((string) $this->request->getPost('end_date'));
            $reason      = trim((string) $this->request->getPost('reason'));

            if ($hasPreset) {
                // Overwrite submitted values with preset where preset has a value
                if (! empty($preset['request_type']) && in_array($preset['request_type'], $allowedTypes, true)) {
                    $requestType    = $preset['request_type'];
                    $presetOverride = true;
                }
                if (! empty($preset['start_date'])) {
                    $startDate      = $preset['start_date'];
                    $presetOverride = true;
                }
                $endDate = $preset['end_date'] ?? $endDate;
                if (! empty($preset['reason'])) {
                    $reason = $preset['reason'];
                }
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

            $effectiveEndDate = $endDateObj ?? $startDateObj;
            $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;

            $insertData = [
                'requested_by'  => $userId,
                'request_type'  => $requestType,
                'start_date'    => $startDate,
                'end_date'      => $endDate !== '' ? $endDate : null,
                'duration'      => $duration,
                'approval_code' => $normalizedCode,
                'reason'        => $reason,
                'status'        => 'approved',
                'processed_by'  => $codeRow['generated_by'],
            ];

            $this->requestModel->insert($insertData);
            $newRequestId = (int) $this->requestModel->getInsertID();

            service('activityLogger')->requestFiled($newRequestId, $insertData);
            $this->approvalCodeModel->markUsed((int) $codeRow['id'], $userId);
            service('activityLogger')->requestApprovalCodeUsed($newRequestId);

            $message = $presetOverride
                ? 'Request submitted and automatically approved. The approval code\'s preset details were applied to your request.'
                : 'Request submitted and automatically approved via approval code.';

            return redirect()->back()->with('success', $message);
        }

        // ── NORMAL MODE: full validation as before ────────────────────────────
        $requestType = trim((string) $this->request->getPost('request_type'));
        $startDate   = trim((string) $this->request->getPost('start_date'));
        $endDate     = trim((string) $this->request->getPost('end_date'));
        $reason      = trim((string) $this->request->getPost('reason'));

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
        $presetOverride = false;

        if ($normalizedCode !== '') {
            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);
            if ($codeRow === null) {
                return redirect()->back()->withInput()->with('error', 'The approval code is invalid or has already been used.');
            }
            $status = 'approved';

            // If the code happens to carry preset data, overwrite the submitted fields
            if (! empty($codeRow['preset_data'])) {
                $preset = json_decode((string) $codeRow['preset_data'], true);
                if (is_array($preset)) {
                    if (! empty($preset['request_type']) && in_array($preset['request_type'], $allowedTypes, true)) {
                        $requestType    = $preset['request_type'];
                        $presetOverride = true;
                    }
                    if (! empty($preset['start_date'])) {
                        $startDate    = $preset['start_date'];
                        $startDateObj = date_create($startDate);
                        $presetOverride = true;
                    }
                    $endDate    = $preset['end_date'] ?? '';
                    $endDateObj = $endDate ? date_create($endDate) : null;
                    if (! empty($preset['reason'])) {
                        $reason = $preset['reason'];
                    }
                }
            }
        }

        $effectiveEndDate = $endDateObj ?? $startDateObj;
        $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;

        $insertData = [
            'requested_by'  => $userId,
            'request_type'  => $requestType,
            'start_date'    => $startDate,
            'end_date'      => $endDate !== '' ? $endDate : null,
            'duration'      => $duration,
            'approval_code' => $normalizedCode !== '' ? $normalizedCode : null,
            'reason'        => $reason,
            'status'        => $status,
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

        if ($status === 'approved' && $presetOverride) {
            $message = 'Request submitted and automatically approved. The approval code\'s preset details were applied to your request.';
        } elseif ($status === 'approved') {
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