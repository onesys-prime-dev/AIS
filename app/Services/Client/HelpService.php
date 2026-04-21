<?php

namespace App\Services\Client;

use App\Models\Client\HelpRequestModel;

class HelpService
{
	protected HelpRequestModel $helpRequestModel;

	public function __construct()
	{
		$this->helpRequestModel = new HelpRequestModel();
	}

	public function createHelpRequest(int $userId, array $data): bool
	{
		$inserted = $this->helpRequestModel->insert([
			'user_id' => $userId,
			'category' => $data['category'],
			'priority' => $data['priority'],
			'subject' => $data['subject'],
			'message' => $data['message'],
			'status' => 'open',
		]);

		return (bool) $inserted;
	}

	public function getHelpRequestsForUser(int $userId): array
	{
		return $this->helpRequestModel
			->select('help_requests.*, users.username as submitted_by_username')
			->join('users', 'users.id = help_requests.user_id', 'left')
			->where('help_requests.user_id', $userId)
			->orderBy('help_requests.created_at', 'DESC')
			->findAll();
	}

	public function getAllHelpRequests(): array
	{
		return $this->helpRequestModel
			->select('help_requests.*, users.username as submitted_by_username')
			->join('users', 'users.id = help_requests.user_id', 'left')
			->orderBy('help_requests.created_at', 'DESC')
			->findAll();
	}

	public function updateHelpRequest(int $id, int $respondedBy, array $data): bool
	{
		return (bool) $this->helpRequestModel->update($id, [
			'status' => $data['status'],
			'response' => $data['response'] !== '' ? $data['response'] : null,
			'responded_by' => $respondedBy,
			'responded_at' => date('Y-m-d H:i:s'),
		]);
	}
}

