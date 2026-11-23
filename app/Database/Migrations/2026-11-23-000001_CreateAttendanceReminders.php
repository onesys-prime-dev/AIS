<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Attendance reminders (clock in / clock out / breaks).
 *
 * - attendance_reminders:          one row per employee, shift date, reminder type, step and break slot. It dedupes
 *                                  sends (unique key), records what was delivered, and remembers a dismissed pop-up.
 * - attendance_reminder_prefs:     each employee's own switches (no row = everything on).
 * - attendance_reminder_overrides: per-employee custom reminder minutes (JSON; blank/missing = use the global defaults).
 * - push_subscriptions:            browser push devices (used from the browser-push phase).
 *
 * Safe to re-run: every table is created only when missing (these servers' schemas were partly hand-built).
 */
class CreateAttendanceReminders extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('attendance_reminders')) {
            $this->forge->addField([
                'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'shift_date'   => ['type' => 'DATE'],
                'type'         => ['type' => 'VARCHAR', 'constraint' => 20],      // clock_in | clock_out | break_window | break_end | escalation
                'step'         => ['type' => 'VARCHAR', 'constraint' => 20],      // e.g. -10, 0, 30, open, close, pre, over
                'slot'         => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
                'due_at'       => ['type' => 'DATETIME', 'null' => true],
                'status'       => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'pending'],   // pending | sending | sent | failed | skipped
                'email_status' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],           // sent | failed | off | no_email | no_sender
                'push_status'  => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],           // sent | failed | off | no_device
                'attempts'     => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
                'last_error'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'claimed_at'   => ['type' => 'DATETIME', 'null' => true],      // when a run took the row to send it (a stuck run is detected from this)
                'sent_at'      => ['type' => 'DATETIME', 'null' => true],
                'dismissed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_id', 'shift_date', 'type', 'step', 'slot'], 'uq_attendance_reminder');
            $this->forge->addKey(['shift_date', 'status']);
            $this->forge->createTable('attendance_reminders', true);
        }

        if ($this->db->tableExists('attendance_reminders') && ! $this->db->fieldExists('claimed_at', 'attendance_reminders')) {
            $this->forge->addColumn('attendance_reminders', ['claimed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_error']]);
        }

        if (! $this->db->tableExists('attendance_reminder_prefs')) {
            $this->forge->addField([
                'user_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'reminders_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'email_enabled'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'push_enabled'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'sound_enabled'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('user_id', true);
            $this->forge->createTable('attendance_reminder_prefs', true);
        }

        if (! $this->db->tableExists('attendance_reminder_overrides')) {
            $this->forge->addField([
                'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'config'      => ['type' => 'TEXT', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('employee_id', true);
            $this->forge->createTable('attendance_reminder_overrides', true);
        }

        if (! $this->db->tableExists('push_subscriptions')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'endpoint_hash' => ['type' => 'CHAR', 'constraint' => 64],
                'endpoint'      => ['type' => 'TEXT'],
                'p256dh'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'auth'          => ['type' => 'VARCHAR', 'constraint' => 255],
                'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'failed_count'  => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'last_used_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('endpoint_hash', 'uq_push_endpoint');
            $this->forge->addKey('user_id');
            $this->forge->createTable('push_subscriptions', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('push_subscriptions', true);
        $this->forge->dropTable('attendance_reminder_overrides', true);
        $this->forge->dropTable('attendance_reminder_prefs', true);
        $this->forge->dropTable('attendance_reminders', true);
    }
}
