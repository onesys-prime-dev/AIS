-- Backfill missing employee records for users with missing employee profiles
-- This script ensures data consistency for existing user accounts

-- User ID 3: johndoe@gmail.com
INSERT INTO employees (employee_number, user_id, first_name, middle_name, last_name, birthdate, created_at, updated_at)
VALUES ('EMP-2026-0016', 3, 'John', NULL, 'Doe', '1900-01-01', NOW(), NULL);

SET @emp_id = LAST_INSERT_ID();

INSERT INTO employee_government_ids (employee_id, sss, tin, pagibig, philhealth)
VALUES (@emp_id, 'PENDING', 'PENDING', 'PENDING', 'PENDING');

INSERT INTO employee_positions (employee_id, department, position, employment_type)
VALUES (@emp_id, 'General', 'Employee', 'Regular');

-- User ID 15: Sean (2022-2459@sanbeda.edu.ph)
INSERT INTO employees (employee_number, user_id, first_name, middle_name, last_name, birthdate, created_at, updated_at)
VALUES ('EMP-2026-0017', 15, 'Sean', NULL, 'Student', '1900-01-01', NOW(), NULL);

SET @emp_id = LAST_INSERT_ID();

INSERT INTO employee_government_ids (employee_id, sss, tin, pagibig, philhealth)
VALUES (@emp_id, 'PENDING', 'PENDING', 'PENDING', 'PENDING');

INSERT INTO employee_positions (employee_id, department, position, employment_type)
VALUES (@emp_id, 'General', 'Employee', 'Regular');
