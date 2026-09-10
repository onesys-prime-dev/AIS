<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** Local server operator only: no web route exposes role or team assignment. */
final class PortalAccessCommand extends BaseCommand
{
    protected $group = 'AIS';
    protected $name = 'ais:access';
    protected $description = 'Inspect roles; assign a role or explicit team membership using local server access.';
    protected $usage = 'ais:access [list|grant USER_ID GROUP|revoke USER_ID GROUP|assign HEAD_USER_ID EMPLOYEE_ID|unassign HEAD_USER_ID EMPLOYEE_ID]';

    public function run(array $params)
    {
        $db = db_connect();
        $action = $params[0] ?? 'list';
        if ($action === 'list') {
            $rows = $db->table('users u')->select("u.id, u.username, GROUP_CONCAT(DISTINCT g.group ORDER BY g.group) AS roles", false)
                ->join('auth_groups_users g', 'g.user_id=u.id', 'left')->where('u.deleted_at', null)->groupBy('u.id')->get()->getResultArray();
            CLI::table($rows, ['User ID', 'Username', 'Roles']);
            return;
        }
        $uid = filter_var($params[1] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $uid ? auth()->getProvider()->findById($uid) : null;
        if (!$user) { CLI::error('Valid user ID required.'); return EXIT_ERROR; }
        if (in_array($action, ['grant', 'revoke'], true)) {
            $group = $params[2] ?? '';
            if (!isset(config('AuthGroups')->groups[$group])) { CLI::error('Unknown role.'); return EXIT_ERROR; }
            if ($action === 'revoke' && $group === 'superadmin' && $user->inGroup('superadmin') && $db->table('auth_groups_users g')->join('users u', 'u.id=g.user_id')->where('g.group', 'superadmin')->where('u.active', 1)->where('u.deleted_at', null)->countAllResults() <= 1) {
                CLI::error('Cannot remove the last active Super Admin.'); return EXIT_ERROR;
            }
            $action === 'grant' ? $user->addGroup($group) : $user->removeGroup($group);
            if ($action === 'revoke' && $group === 'team_head') $db->table('team_access_assignments')->where('team_head_user_id', $uid)->delete();
            CLI::write('Roles: ' . implode(', ', $user->getGroups()));
            return;
        }
        if (!in_array($action, ['assign', 'unassign'], true) || !$user->inGroup('team_head')) { CLI::error('A Team Head and assign/unassign action are required.'); return EXIT_ERROR; }
        $eid = filter_var($params[2] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $employee = $eid ? $db->table('employees')->where('id', $eid)->get()->getRowArray() : null;
        if (!$employee || (int) $employee['user_id'] === $uid) { CLI::error('Select an existing employee other than the Team Head.'); return EXIT_ERROR; }
        $key = ['team_head_user_id' => $uid, 'employee_id' => $eid];
        if ($action === 'unassign') $db->table('team_access_assignments')->where($key)->delete();
        elseif (!$db->table('team_access_assignments')->where($key)->countAllResults()) $db->table('team_access_assignments')->insert($key + ['created_at' => date('Y-m-d H:i:s')]);
        CLI::write('Team assignment updated.');
    }
}
