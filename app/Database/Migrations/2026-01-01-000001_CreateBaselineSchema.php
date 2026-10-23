<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The project ran on a hand-maintained schema before it adopted CI4
 * migrations; everything from here onward was captured as a proper
 * migration, but the original tables themselves never were. That meant
 * `spark migrate --all` could never build a working database from nothing
 * (confirmed: CI's own "migrate an empty database" check failed on a
 * missing employee_payslips table, since only ALTER migrations for it ever
 * existed).
 *
 * This creates every one of those original tables in the state they were in
 * *before* any later migration touched them — every column a later
 * migration adds, renames, or drops is deliberately left out here so that
 * migration still does the work and the two don't conflict. Dated before
 * every existing migration so it always runs first.
 *
 * Deliberately excluded (not part of the app's schema):
 *  - users, auth_*: created by Shield's own bundled migration (`users` is
 *    Shield's own table, identical schema — not something this app built).
 *  - settings: created by codeigniter4/settings' own bundled migration,
 *    same reason.
 *  - migrations: CodeIgniter's own bookkeeping table.
 *  - sss_brackets_backup_20260715: an ad-hoc manual backup copy, unused by
 *    any code path.
 *  - Every table already covered by its own CreateXxx migration.
 */
class CreateBaselineSchema extends Migration
{
    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($this->tableStatements() as $sql) {
            $this->db->query($sql);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach (array_reverse($this->tableNames()) as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function tableNames(): array
    {
        return [
            'employees', 'employee_accounts', 'employee_government_ids',
            'employee_positions', 'company_branding',
            'sss_brackets', 'wtax_brackets', 'wtax_brackets_daily', 'wtax_brackets_monthly',
            'wtax_brackets_semimonthly', 'wtax_brackets_weekly',
            'attendance', 'tasks', 'saved_tasks', 'requests', 'approval_codes',
            'request_notifications', 'leave_balances',
            'payroll', 'payroll_cutoffs', 'payroll_cutoff_summary', 'payroll_settings',
            'payroll_transactions', 'employee_payslips',
            'employee_loans', 'employee_loan_settings', 'employee_loan_schedule', 'employee_loan_payments',
            'annual_tax_adjustments', 'annualization_records',
            'thirteenth_month_pay', 'thirteenth_month_records',
            'announcements', 'announcement_attachments', 'announcement_views', 'announcement_dismissals',
            'help_requests', 'activity_logs',
        ];
    }

    private function tableStatements(): array
    {
        return [
            "CREATE TABLE `employees` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_number` varchar(20) NOT NULL,
              `user_id` int(11) unsigned NOT NULL,
              `first_name` varchar(100) NOT NULL,
              `middle_name` varchar(100) DEFAULT NULL,
              `last_name` varchar(100) NOT NULL,
              `birthdate` date NOT NULL,
              `gender` varchar(20) DEFAULT NULL,
              `civil_status` varchar(50) DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_number` (`employee_number`),
              KEY `employees_user_id_foreign` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `employee_accounts` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `photo_path` varchar(255) DEFAULT NULL,
              `secondary_email` varchar(255) DEFAULT NULL,
              `mobile_number` varchar(30) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `emergency_name` varchar(150) DEFAULT NULL,
              `emergency_relationship` varchar(100) DEFAULT NULL,
              `emergency_phone_primary` varchar(30) DEFAULT NULL,
              `emergency_phone_alternate` varchar(30) DEFAULT NULL,
              `updated_by` int(11) unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT NULL,
              `bank` varchar(100) DEFAULT NULL,
              `bank_address` varchar(255) DEFAULT NULL,
              `account_number` varchar(100) DEFAULT NULL,
              `swift_code` varchar(20) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_accounts_employee_id_unique` (`employee_id`),
              KEY `employee_accounts_updated_by_foreign` (`updated_by`),
              CONSTRAINT `employee_accounts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `employee_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `employee_government_ids` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `sss` varchar(50) NOT NULL,
              `tin` varchar(50) NOT NULL,
              `pagibig` varchar(50) NOT NULL,
              `philhealth` varchar(50) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `employee_government_ids_employee_id_foreign` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // break_schedule/break_windows/tardiness_grace_minutes/ot_grace_minutes/
            // additional_de_minimis_*/department_id/wfh_days all come from later
            // migrations.
            "CREATE TABLE `employee_positions` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `department` varchar(100) NOT NULL,
              `position` varchar(100) NOT NULL,
              `employment_type` varchar(100) NOT NULL,
              `working_day_type` varchar(40) DEFAULT NULL,
              `start_time` time DEFAULT NULL,
              `end_time` time DEFAULT NULL,
              `working_days` text DEFAULT NULL,
              `monthly_salary` decimal(12,2) DEFAULT 22000.00,
              `de_minimis_benefit` decimal(12,2) DEFAULT 0.00,
              `de_minimis_breakdown` text DEFAULT NULL,
              `allowances` decimal(12,2) DEFAULT 0.00,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `company_branding` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `company_id` int(11) unsigned NOT NULL,
              `logo_path` varchar(255) DEFAULT NULL,
              `logo_original_name` varchar(255) DEFAULT NULL,
              `logo_mime_type` varchar(100) DEFAULT NULL,
              `logo_size` int(11) unsigned DEFAULT NULL,
              `sidebar_color` varchar(7) NOT NULL DEFAULT '#1e1e1e',
              `header_color` varchar(7) NOT NULL DEFAULT '#ffffff',
              `background_color` varchar(7) NOT NULL DEFAULT '#f5f6fa',
              `sidebar_icon_mode` varchar(20) NOT NULL DEFAULT 'auto',
              `updated_by` int(11) unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_company_branding_company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `sss_brackets` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `effective_year` smallint(5) unsigned NOT NULL DEFAULT 2025,
              `is_active` tinyint(4) NOT NULL DEFAULT 0,
              `range_start` decimal(12,2) NOT NULL,
              `range_end` decimal(12,2) DEFAULT NULL,
              `monthly_salary_credit` decimal(12,2) NOT NULL,
              `ee_regular` decimal(12,2) NOT NULL,
              `ee_wisp` decimal(12,2) NOT NULL,
              `er_regular` decimal(12,2) NOT NULL,
              `er_wisp` decimal(12,2) NOT NULL,
              `total_ee` decimal(12,2) GENERATED ALWAYS AS (`ee_regular` + `ee_wisp`) STORED,
              `total_er` decimal(12,2) GENERATED ALWAYS AS (`er_regular` + `er_wisp`) STORED,
              PRIMARY KEY (`id`),
              KEY `range_start` (`range_start`),
              KEY `idx_sss_year` (`effective_year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `wtax_brackets` (
              `tax_bracket` int(11) NOT NULL,
              `min_amount` int(11) NOT NULL,
              `max_amount` int(11) DEFAULT NULL,
              `flat` float NOT NULL,
              `percentage` float NOT NULL,
              PRIMARY KEY (`tax_bracket`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `wtax_brackets_daily` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `effective_year` smallint(5) unsigned NOT NULL DEFAULT 2025,
              `is_active` tinyint(4) NOT NULL DEFAULT 0,
              `min_amount` decimal(12,2) NOT NULL,
              `max_amount` decimal(12,2) DEFAULT NULL,
              `fixed_tax` decimal(12,2) NOT NULL,
              `percent_over` decimal(5,4) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `wtax_brackets_monthly` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `effective_year` smallint(5) unsigned NOT NULL DEFAULT 2025,
              `is_active` tinyint(4) NOT NULL DEFAULT 0,
              `min_amount` decimal(12,2) NOT NULL,
              `max_amount` decimal(12,2) DEFAULT NULL,
              `fixed_tax` decimal(12,2) NOT NULL,
              `percent_over` decimal(5,4) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `min_amount` (`min_amount`),
              KEY `idx_wtax_year` (`effective_year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `wtax_brackets_semimonthly` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `effective_year` smallint(5) unsigned NOT NULL DEFAULT 2025,
              `is_active` tinyint(4) NOT NULL DEFAULT 0,
              `min_amount` decimal(12,2) NOT NULL,
              `max_amount` decimal(12,2) DEFAULT NULL,
              `fixed_tax` decimal(12,2) NOT NULL,
              `percent_over` decimal(5,4) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `wtax_brackets_weekly` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `effective_year` smallint(5) unsigned NOT NULL DEFAULT 2025,
              `is_active` tinyint(4) NOT NULL DEFAULT 0,
              `min_amount` decimal(12,2) NOT NULL,
              `max_amount` decimal(12,2) DEFAULT NULL,
              `fixed_tax` decimal(12,2) NOT NULL,
              `percent_over` decimal(5,4) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // breaks_taken/missed_clockout_reason/shift_date/schedule_snapshot/
            // break_log/state_version all come from later migrations.
            "CREATE TABLE `attendance` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned NOT NULL,
              `time_in` datetime NOT NULL,
              `break_start` datetime DEFAULT NULL,
              `break_end` datetime DEFAULT NULL,
              `break_duration` decimal(5,2) DEFAULT NULL,
              `time_out` datetime DEFAULT NULL,
              `total_hours` decimal(5,2) DEFAULT NULL,
              `night_diff_hours` decimal(5,2) DEFAULT NULL,
              `scheduled_start` time DEFAULT NULL,
              `scheduled_end` time DEFAULT NULL,
              `late_minutes` int(11) DEFAULT NULL,
              `approved_ot_hours` decimal(8,2) DEFAULT NULL,
              `unapproved_ot_hours` decimal(8,2) DEFAULT NULL,
              `approved_ut_hours` decimal(8,2) DEFAULT NULL,
              `unapproved_ut_hours` decimal(8,2) DEFAULT NULL,
              `unapproved_ut_reason` varchar(255) DEFAULT NULL,
              `attendance_status` varchar(50) DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              `time_in_dt` datetime DEFAULT NULL,
              `time_out_dt` datetime DEFAULT NULL,
              `break_start_dt` datetime DEFAULT NULL,
              `break_end_dt` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `fk_attendance_user` (`user_id`),
              CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `tasks` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `attendance_id` int(10) unsigned NOT NULL,
              `user_id` int(10) unsigned NOT NULL,
              `start_time` time NOT NULL,
              `end_time` time NOT NULL,
              `task_performed` text NOT NULL,
              `client` varchar(255) DEFAULT NULL,
              `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `attendance_id` (`attendance_id`),
              KEY `user_id` (`user_id`),
              CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE,
              CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // client: added by AddClientToSavedTasks, then dropped again by
            // CreateSavedClients once saved_clients took over that role.
            "CREATE TABLE `saved_tasks` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned NOT NULL,
              `task_performed` text NOT NULL,
              `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              CONSTRAINT `saved_tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `requests` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `requested_by` int(10) unsigned NOT NULL,
              `request_type` varchar(20) NOT NULL,
              `leave_category` varchar(20) NOT NULL DEFAULT 'leave',
              `start_date` date NOT NULL,
              `end_date` date DEFAULT NULL,
              `duration` int(11) NOT NULL DEFAULT 1,
              `is_paid_leave` tinyint(1) NOT NULL DEFAULT 1,
              `approval_code` varchar(100) DEFAULT NULL,
              `review_message` text DEFAULT NULL,
              `reason` text NOT NULL,
              `status` varchar(20) DEFAULT 'pending',
              `processed_by` int(10) unsigned DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `requested_by` (`requested_by`),
              KEY `processed_by` (`processed_by`),
              CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
              CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `approval_codes` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `code` varchar(100) NOT NULL,
              `generated_by` int(10) unsigned NOT NULL,
              `is_used` tinyint(1) NOT NULL DEFAULT 0,
              `used_by` int(10) unsigned DEFAULT NULL,
              `used_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              `preset_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preset_data`)),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_approval_code_code` (`code`),
              KEY `idx_approval_codes_generated_by` (`generated_by`),
              KEY `idx_approval_codes_is_used` (`is_used`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // status/details come from later migrations.
            "CREATE TABLE `request_notifications` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `request_id` int(11) DEFAULT NULL,
              `recipient_user_id` int(11) NOT NULL,
              `actor_user_id` int(11) DEFAULT NULL,
              `title` varchar(255) DEFAULT NULL,
              `message` text DEFAULT NULL,
              `type` varchar(50) DEFAULT NULL,
              `dismissed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT NULL,
              `viewed_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `leave_balances` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(10) unsigned NOT NULL,
              `leave_type` varchar(30) NOT NULL DEFAULT 'leave',
              `year` year(4) NOT NULL,
              `total_entitlement` decimal(5,2) NOT NULL DEFAULT 15.00,
              `used` decimal(5,2) NOT NULL DEFAULT 0.00,
              `remaining` decimal(5,2) NOT NULL DEFAULT 15.00,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_leave_balance` (`employee_id`,`leave_type`,`year`),
              CONSTRAINT `fk_leave_balance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // period_type comes from a later migration.
            "CREATE TABLE `payroll` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `month_year` varchar(7) NOT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `daily_rate` decimal(10,2) NOT NULL,
              `dst` tinyint(1) DEFAULT 0,
              `currency` varchar(3) DEFAULT 'PHP',
              `status` varchar(20) DEFAULT 'Pending',
              `created_at` datetime DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `payroll_employee_id_foreign` (`employee_id`),
              CONSTRAINT `payroll_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `payroll_cutoffs` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 0,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `payroll_cutoff_summary` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `cutoff_id` int(11) unsigned NOT NULL,
              `regular_hours` decimal(8,2) DEFAULT 0.00,
              `night_diff_hours` decimal(8,2) DEFAULT 0.00,
              `tardy_minutes` int(11) DEFAULT 0,
              `reg_ot_hours` decimal(8,2) DEFAULT 0.00,
              `rest_day_ot_hours` decimal(8,2) DEFAULT 0.00,
              `holiday_ot_hours` decimal(8,2) DEFAULT 0.00,
              `paid_leave_days` decimal(8,2) DEFAULT 0.00,
              `unpaid_leave_days` decimal(8,2) DEFAULT 0.00,
              `undertime_hours` decimal(8,2) DEFAULT 0.00,
              `request_overtime_hours` decimal(8,2) DEFAULT 0.00,
              `de_minimis_json` longtext DEFAULT NULL,
              `other_taxable` decimal(12,2) DEFAULT 0.00,
              `other_non_taxable` decimal(12,2) DEFAULT 0.00,
              `sss_loan` decimal(12,2) DEFAULT 0.00,
              `calamity_loan` decimal(12,2) DEFAULT 0.00,
              `salary_advance` decimal(12,2) DEFAULT 0.00,
              `basic_pay` decimal(12,2) DEFAULT 0.00,
              `night_diff_pay` decimal(12,2) DEFAULT 0.00,
              `overtime_pay` decimal(12,2) DEFAULT 0.00,
              `absent_deduction` decimal(12,2) DEFAULT 0.00,
              `undertime_deduction` decimal(12,2) DEFAULT 0.00,
              `gross_pay` decimal(12,2) DEFAULT 0.00,
              `sss_ee` decimal(12,2) DEFAULT 0.00,
              `phic_ee` decimal(12,2) DEFAULT 0.00,
              `hdmf_ee` decimal(12,2) DEFAULT 0.00,
              `withholding_tax` decimal(12,2) DEFAULT 0.00,
              `total_deductions` decimal(12,2) DEFAULT 0.00,
              `net_pay` decimal(12,2) DEFAULT 0.00,
              `status` enum('draft','computed','paid') DEFAULT 'draft',
              `computed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              `reg_ot_hours_le10` decimal(8,2) DEFAULT 0.00,
              `reg_ot_hours_excess` decimal(8,2) DEFAULT 0.00,
              `rest_ot_hours_le10` decimal(8,2) DEFAULT 0.00,
              `rest_ot_hours_excess` decimal(8,2) DEFAULT 0.00,
              `holiday_ot_hours_le10` decimal(8,2) DEFAULT 0.00,
              `holiday_ot_hours_excess` decimal(8,2) DEFAULT 0.00,
              `worked_days` int(11) DEFAULT 0,
              `total_working_days` int(11) DEFAULT 0,
              `absent_days` int(11) DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              KEY `cutoff_id` (`cutoff_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `payroll_settings` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `client_id` int(11) unsigned DEFAULT NULL,
              `setting_key` varchar(100) NOT NULL,
              `setting_value` text DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `key_client` (`setting_key`,`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // paid_by/paid_at come from a later migration.
            "CREATE TABLE `payroll_transactions` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `period_start` date NOT NULL,
              `period_end` date NOT NULL,
              `basic_pay` decimal(12,2) NOT NULL,
              `overtime_pay` decimal(12,2) DEFAULT 0.00,
              `night_diff_pay` decimal(12,2) DEFAULT 0.00,
              `holiday_pay` decimal(12,2) DEFAULT 0.00,
              `de_minimis` decimal(12,2) DEFAULT 0.00,
              `other_earnings_taxable` decimal(12,2) DEFAULT 0.00,
              `other_earnings_non_taxable` decimal(12,2) DEFAULT 0.00,
              `total_earnings` decimal(12,2) NOT NULL,
              `sss_ee` decimal(12,2) DEFAULT 0.00,
              `phic_ee` decimal(12,2) DEFAULT 0.00,
              `hdmf_ee` decimal(12,2) DEFAULT 0.00,
              `withholding_tax` decimal(12,2) DEFAULT 0.00,
              `sss_loan` decimal(12,2) DEFAULT 0.00,
              `calamity_loan` decimal(12,2) DEFAULT 0.00,
              `salary_advance` decimal(12,2) DEFAULT 0.00,
              `other_deductions` decimal(12,2) DEFAULT 0.00,
              `total_deductions` decimal(12,2) NOT NULL,
              `net_pay` decimal(12,2) NOT NULL,
              `status` enum('draft','computed','paid') DEFAULT 'draft',
              `computed_by` int(10) unsigned DEFAULT NULL,
              `computed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              CONSTRAINT `fk_payroll_trans_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // sent_by and every release-tracking column come from later migrations.
            "CREATE TABLE `employee_payslips` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `period_start` date NOT NULL,
              `period_end` date NOT NULL,
              `net_pay` decimal(12,2) NOT NULL,
              `sent_at` datetime DEFAULT NULL,
              `file_path` varchar(255) NOT NULL,
              `generated_data` longtext DEFAULT NULL COMMENT 'JSON of all payslip fields',
              `created_at` datetime DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              CONSTRAINT `fk_employee_payslips_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // Every column beyond sss_loan/calamity_loan/salary_advance comes from
            // AlignEmployeeLoansSchema / AddActiveOngoingLoanStatus.
            "CREATE TABLE `employee_loans` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `sss_loan` decimal(12,2) DEFAULT 0.00,
              `calamity_loan` decimal(12,2) DEFAULT 0.00,
              `salary_advance` decimal(12,2) DEFAULT 0.00,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // loan_category/grace_period_days/penalty_rate, and the
            // max_salary_multiplier/allow_multiple_active_loans renames, come
            // from AlignEmployeeLoansSchema.
            "CREATE TABLE `employee_loan_settings` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `loan_type` varchar(100) NOT NULL,
              `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
              `salary_multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
              `max_installments` int(11) NOT NULL DEFAULT 12,
              `deduction_frequency` enum('Semi-Monthly','Monthly') NOT NULL DEFAULT 'Semi-Monthly',
              `allow_multiple_loans` tinyint(1) NOT NULL DEFAULT 0,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `employee_loan_schedule` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `loan_id` bigint(20) unsigned NOT NULL,
              `installment_no` int(11) NOT NULL,
              `due_date` date NOT NULL,
              `amount_due` decimal(15,2) NOT NULL DEFAULT 0.00,
              `remaining_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
              `schedule_status` enum('pending','paid','partial','missed','cancelled') NOT NULL DEFAULT 'pending',
              `payroll_id` bigint(20) unsigned DEFAULT NULL,
              `paid_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `loan_id` (`loan_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // payroll_id starts as a plain BIGINT with no FK; a later migration
            // (AddPayrollIdForeignKeyToLoanPayments) narrows its type to match
            // employee_payslips.id and adds the foreign key.
            "CREATE TABLE `employee_loan_payments` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `loan_id` bigint(20) unsigned NOT NULL,
              `schedule_id` bigint(20) unsigned NOT NULL,
              `employee_id` bigint(20) unsigned NOT NULL,
              `payroll_id` bigint(20) unsigned DEFAULT NULL,
              `payment_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
              `payment_method` varchar(100) NOT NULL DEFAULT 'Payroll Deduction',
              `payment_reference` varchar(255) DEFAULT NULL,
              `remarks` text DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `loan_id` (`loan_id`),
              KEY `schedule_id` (`schedule_id`),
              KEY `employee_id` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `annual_tax_adjustments` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `year` year(4) NOT NULL,
              `ytd_taxable_income` decimal(12,2) NOT NULL DEFAULT 0.00,
              `ytd_tax_withheld` decimal(12,2) NOT NULL DEFAULT 0.00,
              `annual_tax_due` decimal(12,2) NOT NULL DEFAULT 0.00,
              `adjustment_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
              `adjusted_in_period` date DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              CONSTRAINT `fk_annual_tax_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `annualization_records` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(10) unsigned NOT NULL,
              `year` smallint(5) unsigned NOT NULL,
              `total_taxable_compensation` decimal(15,2) NOT NULL DEFAULT 0.00,
              `thirteenth_month_taxable` decimal(15,2) NOT NULL DEFAULT 0.00,
              `annual_taxable_income` decimal(15,2) NOT NULL DEFAULT 0.00,
              `annual_tax_due` decimal(15,2) NOT NULL DEFAULT 0.00,
              `total_tax_withheld` decimal(15,2) NOT NULL DEFAULT 0.00,
              `year_end_adjustment` decimal(15,2) NOT NULL DEFAULT 0.00,
              `status` varchar(20) NOT NULL DEFAULT 'computed',
              `computed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_id_year` (`employee_id`,`year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `thirteenth_month_pay` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) unsigned NOT NULL,
              `year` year(4) NOT NULL,
              `total_basic_earned` decimal(12,2) NOT NULL DEFAULT 0.00,
              `thirteenth_month_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
              `paid_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              CONSTRAINT `fk_thirteenth_month_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `thirteenth_month_records` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `employee_id` int(10) unsigned NOT NULL,
              `year` smallint(5) unsigned NOT NULL,
              `total_basic_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
              `total_gross_basic` decimal(15,2) NOT NULL DEFAULT 0.00,
              `total_absent_undertime` decimal(15,2) NOT NULL DEFAULT 0.00,
              `periods_count` int(11) NOT NULL DEFAULT 0,
              `thirteenth_month_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
              `tax_exempt_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
              `taxable_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
              `status` varchar(20) NOT NULL DEFAULT 'computed',
              `computed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_id_year` (`employee_id`,`year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `announcements` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `title` varchar(120) NOT NULL,
              `category` varchar(30) NOT NULL,
              `audience` varchar(30) NOT NULL,
              `department` varchar(100) DEFAULT NULL,
              `priority` varchar(20) NOT NULL DEFAULT 'normal',
              `publish_at` datetime NOT NULL,
              `expires_at` datetime DEFAULT NULL,
              `summary` varchar(180) NOT NULL,
              `details` text NOT NULL,
              `require_acknowledgement` tinyint(1) NOT NULL DEFAULT 0,
              `created_by` int(11) unsigned NOT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_announcements_audience` (`audience`),
              KEY `idx_announcements_publish_at` (`publish_at`),
              KEY `idx_announcements_expires_at` (`expires_at`),
              KEY `idx_announcements_created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `announcement_attachments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `announcement_id` int(11) NOT NULL,
              `file_path` varchar(255) NOT NULL,
              `original_name` varchar(255) NOT NULL,
              `mime_type` varchar(100) DEFAULT NULL,
              `file_size` int(11) DEFAULT 0,
              `uploaded_by` int(11) DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `announcement_views` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `announcement_id` int(11) unsigned NOT NULL,
              `user_id` int(11) unsigned NOT NULL,
              `viewed_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `announcement_id_user_id` (`announcement_id`,`user_id`),
              KEY `announcement_id` (`announcement_id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `announcement_dismissals` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `announcement_id` int(11) unsigned NOT NULL,
              `user_id` int(11) unsigned NOT NULL,
              `dismissed_at` datetime NOT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `announcement_user` (`announcement_id`,`user_id`),
              KEY `announcement_id` (`announcement_id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `help_requests` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(11) unsigned NOT NULL,
              `category` varchar(30) NOT NULL,
              `priority` varchar(20) NOT NULL DEFAULT 'normal',
              `subject` varchar(120) NOT NULL,
              `message` text NOT NULL,
              `status` varchar(20) NOT NULL DEFAULT 'open',
              `response` text DEFAULT NULL,
              `responded_by` int(11) unsigned DEFAULT NULL,
              `responded_at` datetime DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `status` (`status`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE `activity_logs` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned NOT NULL,
              `username` varchar(30) NOT NULL,
              `action` enum('created','updated','deleted') NOT NULL,
              `module` varchar(50) NOT NULL,
              `record_id` int(11) DEFAULT NULL,
              `description` varchar(255) NOT NULL,
              `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
              `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
              `ip_address` varchar(45) DEFAULT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_activity_logs_user_id` (`user_id`),
              KEY `idx_activity_logs_module` (`module`),
              KEY `idx_activity_logs_module_record_id` (`module`,`record_id`),
              KEY `idx_activity_logs_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];
    }
}
