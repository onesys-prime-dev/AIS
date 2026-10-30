<?php

namespace App\Services\Client;

use DomainException;

/**
 * The admin-managed client list that task logging picks from.
 *
 * A client is available to an employee when it is active AND either the employee's department
 * or the employee personally is assigned to it. A client nobody is assigned to is available to nobody.
 */
class ClientService
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct(?\CodeIgniter\Database\BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Everything the admin Clients tab needs: each client with its assignments and usage. */
    public function adminList(): array
    {
        $deptRows = $this->db->table('client_departments')->select('client_id, department_id')->get()->getResultArray();
        $userRows = $this->db->table('client_employees')->select('client_id, user_id')->get()->getResultArray();
        $usage    = [];
        foreach ($this->db->query('SELECT client_id, COUNT(*) AS task_count FROM tasks WHERE client_id IS NOT NULL GROUP BY client_id')->getResultArray() as $r) {
            $usage[(int) $r['client_id']] = (int) $r['task_count'];
        }

        $deps = $users = [];
        foreach ($deptRows as $r) $deps[(int) $r['client_id']][] = (int) $r['department_id'];
        foreach ($userRows as $r) $users[(int) $r['client_id']][] = (int) $r['user_id'];

        $out = [];
        foreach ($this->db->table('clients')->orderBy('name', 'ASC')->get()->getResultArray() as $c) {
            $id = (int) $c['id'];
            $out[] = [
                'id'             => $id,
                'name'           => $c['name'],
                'is_active'      => (int) $c['is_active'] === 1,
                'department_ids' => $deps[$id] ?? [],
                'user_ids'       => $users[$id] ?? [],
                'task_count'     => $usage[$id] ?? 0,
            ];
        }
        return $out;
    }

    /** Employees an admin can assign a client to individually (they need an account to log tasks). */
    public function assignableEmployees(): array
    {
        $rows = $this->db->table('employees e')
            ->select("e.user_id, CONCAT(e.first_name, ' ', e.last_name) AS name, ep.department_id", false)
            ->join('employee_positions ep', 'ep.employee_id = e.id', 'left')
            ->where('e.user_id IS NOT NULL', null, false)
            ->orderBy('e.first_name', 'ASC')
            ->get()->getResultArray();

        // One entry per person even if a position row was ever duplicated.
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['user_id']] ??= [
                'user_id'       => (int) $r['user_id'],
                'name'          => trim($r['name']),
                'department_id' => $r['department_id'] !== null ? (int) $r['department_id'] : null,
            ];
        }
        return array_values($out);
    }

    /** Active clients this user may log tasks against. */
    public function availableForUser(int $userId): array
    {
        $sql = 'SELECT DISTINCT c.id, c.name FROM clients c
                WHERE c.is_active = 1 AND (
                    EXISTS (SELECT 1 FROM client_employees ce WHERE ce.client_id = c.id AND ce.user_id = ?)
                    OR EXISTS (
                        SELECT 1 FROM client_departments cd
                        JOIN employee_positions ep ON ep.department_id = cd.department_id
                        JOIN employees e ON e.id = ep.employee_id
                        WHERE cd.client_id = c.id AND e.user_id = ?
                    )
                )
                ORDER BY c.name ASC';

        return array_map(static fn ($r) => ['id' => (int) $r['id'], 'name' => $r['name']], $this->db->query($sql, [$userId, $userId])->getResultArray());
    }

    /** The client row if this user may use it right now, otherwise null. */
    public function resolveForUser(int $userId, int $clientId): ?array
    {
        foreach ($this->availableForUser($userId) as $c) {
            if ($c['id'] === $clientId) return $c;
        }
        return null;
    }

    public function create(string $name): int
    {
        $name = $this->cleanName($name);
        $this->assertNameFree($name, null);
        $now = date('Y-m-d H:i:s');
        $this->db->table('clients')->insert(['name' => $name, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        return (int) $this->db->insertID();
    }

    /**
     * Saves everything about one client in a single all-or-nothing write.
     * Renaming also updates the name snapshot kept on its tasks, so exports stay consistent.
     *
     * @param int[] $departmentIds
     * @param int[] $userIds
     */
    public function update(int $id, string $name, bool $active, array $departmentIds, array $userIds): array
    {
        $client = $this->db->table('clients')->where('id', $id)->get()->getRowArray();
        if (! $client) throw new DomainException('Client not found.');

        $name = $this->cleanName($name);
        $this->assertNameFree($name, $id);

        $departmentIds = array_values(array_unique(array_filter(array_map('intval', $departmentIds))));
        $userIds       = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if ($departmentIds) {
            $valid = array_column($this->db->table('departments')->select('id')->whereIn('id', $departmentIds)->where('deleted_at', null)->get()->getResultArray(), 'id');
            $departmentIds = array_values(array_intersect($departmentIds, array_map('intval', $valid)));
        }
        if ($userIds) {
            $valid = array_column($this->db->table('employees')->select('user_id')->whereIn('user_id', $userIds)->get()->getResultArray(), 'user_id');
            $userIds = array_values(array_intersect($userIds, array_map('intval', $valid)));
        }

        $this->db->transBegin();
        $this->db->table('clients')->where('id', $id)->update(['name' => $name, 'is_active' => $active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
        if ($name !== $client['name']) {
            $this->db->table('tasks')->where('client_id', $id)->update(['client' => $name]);
        }
        $this->db->table('client_departments')->where('client_id', $id)->delete();
        foreach ($departmentIds as $d) $this->db->table('client_departments')->insert(['client_id' => $id, 'department_id' => $d]);
        $this->db->table('client_employees')->where('client_id', $id)->delete();
        foreach ($userIds as $u) $this->db->table('client_employees')->insert(['client_id' => $id, 'user_id' => $u]);

        if (! $this->db->transStatus()) {
            $this->db->transRollback();
            throw new DomainException('Could not save the client. Please try again.');
        }
        $this->db->transCommit();

        return [
            'before' => ['name' => $client['name'], 'is_active' => (int) $client['is_active']],
            'after'  => ['name' => $name, 'is_active' => $active ? 1 : 0, 'departments' => $departmentIds, 'employees' => $userIds],
        ];
    }

    private function cleanName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') throw new DomainException('Client name is required.');
        if (mb_strlen($name) > 190) throw new DomainException('Client name is too long.');
        return $name;
    }

    private function assertNameFree(string $name, ?int $exceptId): void
    {
        $q = $this->db->table('clients')->where('LOWER(name)', mb_strtolower($name));
        if ($exceptId !== null) $q->where('id !=', $exceptId);
        if ($q->countAllResults() > 0) throw new DomainException('A client with that name already exists.');
    }
}
