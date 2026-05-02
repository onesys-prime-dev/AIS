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
            ['generated_by' => $user->id, 'code' => $code]  // log the actual code
        );

        return $this->response->setJSON(['success' => true, 'code' => $code]);
    }

    public function create()
    {
        return view('client_portal/dashboard');
    }

    public function store()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $userId       = $user->id;
        $requestType  = trim((string) $this->request->getPost('request_type'));
        $startDate    = trim((string) $this->request->getPost('start_date'));
        $endDate      = trim((string) $this->request->getPost('end_date'));
        $approvalCode = trim((string) $this->request->getPost('approval_code'));
        $reason       = trim((string) $this->request->getPost('reason'));

        // --- Validate request type ---
        $allowedTypes = ['leave', 'overtime', 'undertime'];
        if (! in_array($requestType, $allowedTypes, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid request type.');
        }

        // --- Validate start date ---
        if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return redirect()->back()->withInput()->with('error', 'Start date is required and must use YYYY-MM-DD format.');
        }

        $startDateObj = date_create($startDate);
        if (! $startDateObj) {
            return redirect()->back()->withInput()->with('error', 'Invalid start date.');
        }

        // --- Validate end date ---
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

        $effectiveEndDate = $endDateObj ?? $startDateObj;
        $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;

        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Message is required.');
        }

        // --- Approval code validation ---
        // Default: pending. Will be switched to approved if a valid code is supplied.
        $status          = 'pending';
        $codeRow         = null;
        $normalizedCode  = $approvalCode !== '' ? strtoupper($approvalCode) : '';

        if ($normalizedCode !== '') {
            $codeRow = $this->approvalCodeModel->findActiveCode($normalizedCode);

            if ($codeRow === null) {
                // Code was provided but is invalid or already used — reject the submission.
                return redirect()->back()->withInput()->with('error', 'The approval code is invalid or has already been used.');
            }

            $status = 'approved';
        }

        // --- Insert request ---
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

        // If auto-approved, record who "processed" it — we use the code generator's user id.
        if ($status === 'approved' && $codeRow !== null) {
            $insertData['processed_by'] = $codeRow['generated_by'];
        }

        $this->requestModel->insert($insertData);
        $newRequestId = (int) $this->requestModel->getInsertID();

        service('activityLogger')->requestFiled($newRequestId, $insertData);

        // --- Consume the code ---
        if ($codeRow !== null) {
            $this->approvalCodeModel->markUsed((int) $codeRow['id'], $userId);

            // Log code usage against the requests module (ties back to the new request)
            service('activityLogger')->requestApprovalCodeUsed($newRequestId);
        }

        $message = $status === 'approved'
            ? 'Request submitted and automatically approved via approval code.'
            : 'Request submitted successfully.';

        return redirect()->back()->with('success', $message);
    }


    /**
     * generats approval code in the format XXXX-XXXX-XXXX-XXXX.
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
