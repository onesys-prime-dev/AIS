-- ============================================================================
-- RESET DATABASE FOR A FRESH NEW COMPANY (ALL-IN-ONE)
-- ============================================================================
-- Wipes every company-specific / employee / transactional table completely,
-- trims every login/auth table down to ONLY user_id 18 (jdelacruz@gmail.com,
-- username "Juan"), and then immediately recreates a fresh employee profile
-- for that same login — so this one script leaves the database in a
-- complete, consistent state instead of "user exists, employee missing."
--
-- NOT executed by me — you asked for the script only. Review it, TAKE A FULL
-- DATABASE BACKUP FIRST (e.g. `mysqldump ais > backup_before_reset.sql`),
-- then run it yourself when ready. This is irreversible.
--
-- Uses DELETE FROM rather than TRUNCATE: TRUNCATE has a stricter FK rule in
-- MySQL that can still refuse even with foreign_key_checks disabled,
-- depending on how a tool executes the statements. DELETE always respects
-- foreign_key_checks regardless of table order, so this works no matter
-- which client (CLI, phpMyAdmin, etc.) or execution mode you run it in.
--
-- Left untouched (not company-specific data): migrations, sss_brackets*,
-- wtax_brackets*, de_minimis_categories, settings — these are BIR/tax
-- reference tables and framework config, not employee/company data.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. FULL WIPE — every row, every company/employee/transactional table.
-- ----------------------------------------------------------------------------

DELETE FROM activity_logs;
DELETE FROM announcement_attachments;
DELETE FROM announcement_dismissals;
DELETE FROM announcement_views;
DELETE FROM announcements;
DELETE FROM annual_tax_adjustments;
DELETE FROM annualization_records;
DELETE FROM approval_codes;
DELETE FROM tasks;                  -- has an FK into attendance, so goes before it
DELETE FROM attendance;
DELETE FROM client_holidays;
DELETE FROM company_branding;
DELETE FROM employee_loan_payments; -- has an FK into employee_payslips, so goes before it
DELETE FROM employee_accounts;
DELETE FROM employee_government_ids;
DELETE FROM employee_loan_schedule;
DELETE FROM employee_loan_settings;
DELETE FROM employee_loans;
DELETE FROM employee_payslips;
DELETE FROM employee_positions;
DELETE FROM employees;
DELETE FROM face_embeddings;
DELETE FROM help_requests;
DELETE FROM import_batches;
DELETE FROM leave_balances;
DELETE FROM payroll;
DELETE FROM payroll_cutoff_summary;
DELETE FROM payroll_cutoffs;
DELETE FROM payroll_settings;
DELETE FROM payroll_transactions;
DELETE FROM request_notifications;
DELETE FROM requests;
DELETE FROM saved_tasks;
DELETE FROM team_access_assignments;
DELETE FROM thirteenth_month_pay;
DELETE FROM thirteenth_month_records;
DELETE FROM timesheet_histories;
DELETE FROM trusted_devices;
DELETE FROM user_settings;

-- ----------------------------------------------------------------------------
-- 2. PARTIAL CLEANUP — login/auth tables: keep ONLY user_id 18.
-- ----------------------------------------------------------------------------

SET @keep_user_id = 18;

DELETE FROM auth_identities        WHERE user_id <> @keep_user_id;
DELETE FROM auth_groups_users      WHERE user_id <> @keep_user_id;
DELETE FROM auth_permissions_users WHERE user_id <> @keep_user_id;
DELETE FROM auth_remember_tokens   WHERE user_id <> @keep_user_id;
DELETE FROM auth_logins            WHERE user_id IS NULL OR user_id <> @keep_user_id;
DELETE FROM auth_token_logins      WHERE user_id IS NULL OR user_id <> @keep_user_id;

-- users itself last, since the auth_* tables above have an FK into it.
DELETE FROM users WHERE id <> @keep_user_id;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 3. RECREATE J DELACRUZ'S EMPLOYEE PROFILE for that same login (user_id 18).
--    Mirrors EmployeeService::createEmployeeProfileForExistingUser() — same
--    placeholder pattern the app itself uses for a freshly-registered user.
--    Everything marked PENDING/placeholder below is meant to be corrected
--    afterward via Employee Management > Edit Employee.
-- ----------------------------------------------------------------------------

SET @employee_number = 'EMP-2026-0001'; -- table is empty after section 1, so this is safely the first one

INSERT INTO employees (employee_number, user_id, first_name, middle_name, last_name, birthdate)
VALUES (@employee_number, @keep_user_id, 'Juan', NULL, 'Dela Cruz', '1900-01-01'); -- birthdate is a placeholder

SET @employee_id = LAST_INSERT_ID();

INSERT INTO employee_government_ids (employee_id, sss, tin, pagibig, philhealth)
VALUES (@employee_id, 'PENDING', 'PENDING', 'PENDING', 'PENDING');

INSERT INTO employee_positions (employee_id, department, position, employment_type, working_day_type, start_time, end_time, working_days)
VALUES (@employee_id, 'Administration', 'System Administrator', 'Regular', 'full_weekdays', '08:00', '17:00', '["mon","tue","wed","thu","fri"]');

-- Optional — only if you also want a row here now rather than filling it in
-- later via Edit Employee (photo, contact, bank details all stay NULL until set):
-- INSERT INTO employee_accounts (employee_id) VALUES (@employee_id);

-- ----------------------------------------------------------------------------
-- 4. OPTIONAL — reset auto-increment counters back to 1 for a true fresh
--    start (cosmetic only; skip if you don't care about IDs restarting).
--    Do this BEFORE section 3 if you want employees.id to start at 1 too —
--    as written here (after), Juan's employee record already took id 1
--    naturally anyway since the table was empty.
-- ----------------------------------------------------------------------------
-- ALTER TABLE activity_logs AUTO_INCREMENT = 1;
-- ALTER TABLE announcements AUTO_INCREMENT = 1;
-- ALTER TABLE attendance AUTO_INCREMENT = 1;
-- ALTER TABLE payroll AUTO_INCREMENT = 1;
-- ALTER TABLE requests AUTO_INCREMENT = 1;
-- ALTER TABLE tasks AUTO_INCREMENT = 1;
-- (add any other emptied table you want restarted)

-- ----------------------------------------------------------------------------
-- 5. Sanity check afterward:
-- ----------------------------------------------------------------------------
-- SELECT COUNT(*) FROM users;              -- should be 1
-- SELECT id, username FROM users;          -- should show only id=18, "Juan"
-- SELECT * FROM employees WHERE user_id = 18;  -- should show Juan's fresh profile
