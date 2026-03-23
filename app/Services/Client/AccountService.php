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
				'full_name' => $fullName,
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
}
