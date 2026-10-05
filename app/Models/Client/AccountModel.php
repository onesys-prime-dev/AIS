<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AccountModel extends Model
{
	protected $table = 'employee_accounts';
	protected $primaryKey = 'id';

	protected $returnType = 'array';
	protected $useAutoIncrement = true;

	protected $protectFields = true;
	protected $useTimestamps = true;
	protected $dateFormat = 'datetime';
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'employee_id',
		'photo_path',
		'secondary_email',
		'mobile_number',
		'address',
		'emergency_name',
		'emergency_relationship',
		'emergency_phone_primary',
		'emergency_phone_alternate',
		'updated_by',
		'created_at',
		'updated_at',
	];
}
