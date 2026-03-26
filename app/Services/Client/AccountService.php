<?php

namespace App\Services\Client;

use App\Models\Client\AccountModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Shield\Models\UserModel;

class AccountService
{
	protected EmployeeModel $employeeModel;
	protected EmployeePositionModel $positionModel;
	protected AccountModel $accountModel;
	protected UserModel $userModel;

	public function __construct()
	{
		$this->employeeModel = new EmployeeModel();
		$this->positionModel = new EmployeePositionModel();
		$this->accountModel = new AccountModel();
		$this->userModel = new UserModel();
	}

	public function getAccountViewData(int $userId): array
	{
		$user = $this->userModel->find($userId);
		$employee = $this->employeeModel->where('user_id', $userId)->first();

		// Auto-create missing employee profile to maintain data consistency
		if (! $employee) {
			$this->ensureEmployeeProfileExists($userId, $user);
			$employee = $this->employeeModel->where('user_id', $userId)->first();
		}

		if (! $employee) {
			return [
				'identity' => [],
				'employment' => [],
				'contact' => [
					'primary_email' => $user->email ?? '',
					'secondary_email' => '',
					'mobile_number' => '',
					'address' => '',
				],
				'emergency' => [],
				'audit' => [
					'updated_at' => null,
					'updated_by' => null,
				],
			];
		}

		$position = $this->positionModel->where('employee_id', (int) $employee['id'])->first() ?: [];
		$account = $this->getOrCreateAccount((int) $employee['id']);

		$fullName = trim(sprintf(
			'%s %s %s',
			(string) ($employee['first_name'] ?? ''),
			(string) ($employee['middle_name'] ?? ''),
			(string) ($employee['last_name'] ?? '')
		));

		return [
			'identity' => [
				'first_name' => (string) ($employee['first_name'] ?? ''),
				'middle_name' => (string) ($employee['middle_name'] ?? ''),
				'last_name' => (string) ($employee['last_name'] ?? ''),
				'full_name' => $fullName,
				'username' => (string) ($user->username ?? ''),
				'preferred_name' => (string) ($employee['first_name'] ?? ''),
				'birthdate' => (string) ($employee['birthdate'] ?? ''),
				'joined' => (string) ($employee['created_at'] ?? ''),
				'photo_url' => ! empty($account['photo_path']) ? base_url((string) $account['photo_path']) : null,
			],
			'employment' => [
				'employee_number' => (string) ($employee['employee_number'] ?? ''),
				'department' => (string) ($position['department'] ?? ''),
				'position' => (string) ($position['position'] ?? ''),
				'employment_type' => (string) ($position['employment_type'] ?? ''),
				'status' => (isset($user->active) && (int) $user->active === 1) ? 'Active' : 'Inactive',
				'hire_date' => (string) ($employee['created_at'] ?? ''),
				'reports_to' => '-',
			],
			'contact' => [
				'primary_email' => (string) ($user->email ?? ''),
				'secondary_email' => (string) ($account['secondary_email'] ?? ''),
				'mobile_number' => (string) ($account['mobile_number'] ?? ''),
				'address' => (string) ($account['address'] ?? ''),
			],
			'emergency' => [
				'emergency_name' => (string) ($account['emergency_name'] ?? ''),
				'emergency_relationship' => (string) ($account['emergency_relationship'] ?? ''),
				'emergency_phone_primary' => (string) ($account['emergency_phone_primary'] ?? ''),
				'emergency_phone_alternate' => (string) ($account['emergency_phone_alternate'] ?? ''),
			],
			'audit' => [
				'updated_at' => $account['updated_at'] ?? ($employee['updated_at'] ?? null),
				'updated_by' => $this->resolveUpdatedByName($account['updated_by'] ?? null) ?? 'System',
			],
		];
	}

	public function updateContact(int $userId, array $payload): bool
	{
		$employee = $this->employeeModel->where('user_id', $userId)->first();
		if (! $employee) {
			return false;
		}

		$account = $this->getOrCreateAccount((int) $employee['id']);
		if (empty($account['id'])) {
			return false;
		}

		return (bool) $this->accountModel->update((int) $account['id'], [
			'secondary_email' => $payload['secondary_email'] ?? null,
			'mobile_number' => $payload['mobile_number'] ?? null,
			'address' => $payload['address'] ?? null,
			'updated_by' => $userId,
		]);
	}

	public function updateEmergency(int $userId, array $payload): bool
	{
		$employee = $this->employeeModel->where('user_id', $userId)->first();
		if (! $employee) {
			return false;
		}

		$account = $this->getOrCreateAccount((int) $employee['id']);
		if (empty($account['id'])) {
			return false;
		}

		return (bool) $this->accountModel->update((int) $account['id'], [
			'emergency_name' => $payload['emergency_name'] ?? null,
			'emergency_relationship' => $payload['emergency_relationship'] ?? null,
			'emergency_phone_primary' => $payload['emergency_phone_primary'] ?? null,
			'emergency_phone_alternate' => $payload['emergency_phone_alternate'] ?? null,
			'updated_by' => $userId,
		]);
	}

	public function updatePassword(int $userId, string $currentPassword, string $newPassword): array
	{
		$user = $this->userModel->find($userId);

		if (! $user) {
			return ['ok' => false, 'message' => 'User account not found.'];
		}

		$passwordHash = (string) ($user->password_hash ?? '');

		if ($passwordHash === '' || ! password_verify($currentPassword, $passwordHash)) {
			return ['ok' => false, 'message' => 'Current password is incorrect.'];
		}

		$user->password = $newPassword;

		if (! $this->userModel->save($user)) {
			$errors = $this->userModel->errors();
			$message = ! empty($errors) ? implode(' ', $errors) : 'Unable to update password.';

			return ['ok' => false, 'message' => $message];
		}

		return ['ok' => true, 'message' => 'Password updated successfully.'];
	}

	public function updatePhoto(int $userId, UploadedFile $file): array
	{
		$employee = $this->employeeModel->where('user_id', $userId)->first();

		if (! $employee) {
			return ['ok' => false, 'message' => 'Employee profile not found.'];
		}

		if (! in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
			return ['ok' => false, 'message' => 'Only JPG, PNG, or WEBP files are allowed.'];
		}

		if ($file->getSizeByUnit('mb') > 2) {
			return ['ok' => false, 'message' => 'Image must be 2MB or below.'];
		}

		$account = $this->getOrCreateAccount((int) $employee['id']);
		if (empty($account['id'])) {
			return ['ok' => false, 'message' => 'Unable to prepare account profile.'];
		}

		$uploadDir = ROOTPATH . 'public/uploads/profile_photos';

		if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
			return ['ok' => false, 'message' => 'Unable to create upload directory.'];
		}

		$newName = 'user_' . $userId . '_' . date('YmdHis') . '.' . $file->getExtension();

		if (! $file->move($uploadDir, $newName, true)) {
			return ['ok' => false, 'message' => 'Failed to upload photo.'];
		}

		$newPath = 'uploads/profile_photos/' . $newName;

		if (! empty($account['photo_path'])) {
			$oldPath = ROOTPATH . 'public/' . ltrim((string) $account['photo_path'], '/');
			if (is_file($oldPath)) {
				@unlink($oldPath);
			}
		}

		$ok = $this->accountModel->update((int) $account['id'], [
			'photo_path' => $newPath,
			'updated_by' => $userId,
		]);

		if (! $ok) {
			return ['ok' => false, 'message' => 'Unable to save photo details.'];
		}

		return ['ok' => true, 'message' => 'Profile photo updated.'];
	}

	public function updateUsername(int $userId, string $username): array
	{
		$username = trim($username);

		if ($username === '') {
			return ['ok' => false, 'message' => 'Username is required.'];
		}

		$user = $this->userModel->find($userId);

		if (! $user) {
			return ['ok' => false, 'message' => 'User account not found.'];
		}

		$existing = $this->userModel
			->where('username', $username)
			->where('id !=', $userId)
			->first();

		if ($existing) {
			return ['ok' => false, 'message' => 'This username is already taken.'];
		}

		$user->username = $username;

		if (! $this->userModel->save($user)) {
			$errors = $this->userModel->errors();
			$message = ! empty($errors) ? implode(' ', $errors) : 'Unable to update username.';

			return ['ok' => false, 'message' => $message];
		}

		return ['ok' => true, 'message' => 'Username updated successfully.'];
	}

	public function updateIdentity(int $userId, array $payload): array
	{
		$user = $this->userModel->find($userId);

		if (! $user) {
			return ['ok' => false, 'message' => 'User account not found.'];
		}

		$employee = $this->employeeModel->where('user_id', $userId)->first();

		if (! $employee) {
			return ['ok' => false, 'message' => 'Employee profile not found.'];
		}

		$username = trim((string) ($payload['username'] ?? ''));

		$existing = $this->userModel
			->where('username', $username)
			->where('id !=', $userId)
			->first();

		if ($existing) {
			return ['ok' => false, 'message' => 'This username is already taken.'];
		}

		$db = \Config\Database::connect();
		$db->transBegin();

		try {
			[$firstName, $middleName, $lastName] = $this->splitFullName(
				trim((string) ($payload['full_name'] ?? '')),
				(string) ($employee['last_name'] ?? 'User')
			);

			$user->username = $username;

			if (! $this->userModel->save($user)) {
				$errors = $this->userModel->errors();
				$message = ! empty($errors) ? implode(' ', $errors) : 'Unable to update username.';

				throw new \RuntimeException($message);
			}

			$updated = $this->employeeModel->update((int) $employee['id'], [
				'first_name' => $firstName,
				'middle_name' => $middleName,
				'last_name' => $lastName,
				'birthdate' => trim((string) ($payload['birthdate'] ?? '')),
			]);

			if (! $updated) {
				$errors = $this->employeeModel->errors();
				$message = ! empty($errors) ? implode(' ', $errors) : 'Unable to update employee identity.';

				throw new \RuntimeException($message);
			}

			$db->transCommit();

			return ['ok' => true, 'message' => 'Personal identity updated successfully.'];
		} catch (\Throwable $e) {
			$db->transRollback();

			return ['ok' => false, 'message' => $e->getMessage()];
		}
	}

	private function splitFullName(string $fullName, string $fallbackLastName = 'User'): array
	{
		$fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

		if ($fullName === '') {
			return ['Employee', null, $fallbackLastName !== '' ? $fallbackLastName : 'User'];
		}

		// Standard profile input format: "Lastname, FirstName(s) MiddleName"
		if (str_contains($fullName, ',')) {
			[$lastRaw, $givenRaw] = array_pad(explode(',', $fullName, 2), 2, '');
			$lastName = trim($lastRaw);
			$givenNames = trim($givenRaw);

			if ($lastName === '' || $givenNames === '') {
				throw new \RuntimeException('Full name must follow this format: Lastname, FirstName(s) MiddleName.');
			}

			$givenParts = preg_split('/\s+/', $givenNames) ?: [];
			if (count($givenParts) === 0) {
				throw new \RuntimeException('Full name must include at least one first name after the comma.');
			}

			if (count($givenParts) === 1) {
				return [$givenParts[0], null, $lastName];
			}

			$middle = (string) array_pop($givenParts);
			$first = trim(implode(' ', $givenParts));

			return [$first !== '' ? $first : 'Employee', $middle !== '' ? $middle : null, $lastName];
		}

		// Backward compatibility for old "First Middle Last" values.
		$parts = preg_split('/\s+/', $fullName) ?: [];
		if (count($parts) === 1) {
			return [$parts[0], null, $fallbackLastName !== '' ? $fallbackLastName : 'User'];
		}

		if (count($parts) === 2) {
			return [$parts[0], null, $parts[1]];
		}

		$first = (string) array_shift($parts);
		$last = (string) array_pop($parts);
		$middle = trim(implode(' ', $parts));

		return [$first, $middle !== '' ? $middle : null, $last];
	}

	public function getHeaderIdentity(int $userId): array
	{
		$user = $this->userModel->find($userId);

		if (! $user) {
			return [
				'photo_url' => null,
				'display_name' => 'User',
				'display_email' => '',
			];
		}

		$employee = $this->employeeModel->where('user_id', $userId)->first();
		$photoUrl = null;

		if (! empty($employee['id'])) {
			$account = $this->accountModel->where('employee_id', (int) $employee['id'])->first();
			if (! empty($account['photo_path'])) {
				$photoUrl = base_url((string) $account['photo_path']);
			}
		}

		$displayName = (string) ($user->username ?: 'User');
		$displayEmail = (string) ($user->email ?? '');

		return [
			'photo_url' => $photoUrl,
			'display_name' => $displayName,
			'display_email' => $displayEmail,
		];
	}

	protected function getOrCreateAccount(int $employeeId): array
	{
		$account = $this->accountModel->where('employee_id', $employeeId)->first();

		if ($account) {
			return $account;
		}

		$this->accountModel->insert([
			'employee_id' => $employeeId,
		]);

		return $this->accountModel->find((int) $this->accountModel->getInsertID()) ?? [];
	}

	protected function resolveUpdatedByName(?int $updatedBy): ?string
	{
		if (! $updatedBy) {
			return null;
		}

		$user = $this->userModel->find($updatedBy);
		if (! $user) {
			return 'User #' . $updatedBy;
		}

		return (string) ($user->username ?: $user->email ?: ('User #' . $updatedBy));
	}

	/**
	 * Ensures an employee profile exists for the given user.
	 * Automatically creates a minimal profile if missing.
	 * This guards against users logging in without an employee record.
	 *
	 * @param int $userId
	 * @param object|null $user
	 * @return void
	 */
	protected function ensureEmployeeProfileExists(int $userId, ?object $user = null): void
	{
		if (! $user) {
			$user = $this->userModel->find($userId);
		}

		if (! $user) {
			return;
		}

		$existingEmployee = $this->employeeModel->where('user_id', $userId)->first();
		if ($existingEmployee) {
			return; // Already exists
		}

		$db = \Config\Database::connect();
		$db->transBegin();

		try {
			// Extract name parts from username or email
			$usernameOrEmail = (string) ($user->username ?? '');
			$parts = preg_split('/\s+/', $usernameOrEmail) ?: [];

			$firstName = 'Employee';
			$lastName = 'User';
			$middleName = null;

			if (count($parts) >= 1) {
				$firstName = $parts[0];
			}
			if (count($parts) >= 2) {
				$lastName = $parts[count($parts) - 1];
			}
			if (count($parts) >= 3) {
				unset($parts[0], $parts[count($parts) - 1]);
				$middleName = implode(' ', array_values($parts)) ?: null;
			}

			// Generate employee number
			$employeeNumber = $this->generateEmployeeNumber();

			// Create employee record
			$employeeId = $this->employeeModel->insert([
				'employee_number' => $employeeNumber,
				'user_id' => $userId,
				'first_name' => $firstName,
				'middle_name' => $middleName,
				'last_name' => $lastName,
				'birthdate' => '1900-01-01',
			]);

			if (! $employeeId) {
				throw new \RuntimeException('Failed to create employee record.');
			}

			// Create government IDs placeholder
			$govModel = new \App\Models\Client\EmployeeGovernmentIdModel();
			$govModel->insert([
				'employee_id' => $employeeId,
				'sss' => 'PENDING',
				'tin' => 'PENDING',
				'pagibig' => 'PENDING',
				'philhealth' => 'PENDING',
			]);

			// Create position placeholder
			$posModel = new \App\Models\Client\EmployeePositionModel();
			$posModel->insert([
				'employee_id' => $employeeId,
				'department' => 'General',
				'position' => 'Employee',
				'employment_type' => 'Regular',
			]);

			$db->transCommit();
		} catch (\Throwable $e) {
			$db->transRollback();
			log_message('error', 'Failed to auto-create employee profile for user {id}: {message}', [
				'id' => $userId,
				'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 * Generates the next employee number following the format EMP-YYYY-XXXX
	 *
	 * @return string
	 */
	protected function generateEmployeeNumber(): string
	{
		$year = date('Y');
		$prefix = 'EMP-' . $year . '-';

		$latestEmployee = $this->employeeModel
			->select('employee_number')
			->like('employee_number', $prefix, 'after')
			->orderBy('employee_number', 'DESC')
			->first();

		$lastSequence = 0;
		if (! empty($latestEmployee['employee_number'])) {
			$parts = explode('-', (string) $latestEmployee['employee_number']);
			$lastChunk = end($parts);
			$lastSequence = (int) $lastChunk;
		}

		$nextSequence = $lastSequence + 1;

		return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
	}
}
