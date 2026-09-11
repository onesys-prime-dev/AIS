# Phase 2: attendance and timesheet import

Version: 1.0.0 | Updated: 2026-09-09

Purpose: record the tested attendance changes, import procedure, and remaining release work.

Audience: payroll staff, administrators, HR reviewers, and maintainers.

Owner: AIS maintenance team. Business reviewers: payroll and HR; policy acceptance pending.

## Release status and prerequisites

The changes were tested in the isolated application at `http://127.0.0.1:8088/` and deployed to the main application on 2026-09-09. The deployment preserved all 54 existing attendance rows. All 24 live read-only page/access checks passed. Juan's previously requested `superadmin` role was restored alongside `client_payroll_staff`.

The fresh deployment backup is `C:\Users\Stanley\AIS-Recovery\20260907-115226\phase2\before-live-20260909-083858`, containing the database dump, checksum, original changed files, and schema verification report. This is a local recovery backup, not a new GitHub push.

Deployment requires a reachable live database, a successful fresh database/file backup, and matching staged/tested file hashes. Apply only migration `2026-09-08-000002_AddAttendanceSessionState`; do not run all pending migrations as part of this release.

## Attendance behavior in the tested copy

- Clock-in uses the authenticated account and server time. Concurrent requests are serialized per user; existing open sessions prevent a second clock-in.
- Break start/end use POST, a session token, and a record version. Replaying an old request cannot toggle a break or close a newer attendance state.
- Clock-out closes an active break, records its interval, and saves the counter and deduction together with the attendance audit record. Failed audit writes roll back the change.
- Break deductions preserve the configured preset minutes; actual elapsed break duration does not replace that policy.
- New attendance stores its shift date and schedule snapshot. Overnight calculations retain complete dates, and later schedule edits do not change the saved schedule used by the timesheet.
- Existing historical attendance is preserved. Nullable snapshot columns are added without guessing historical schedules or timestamps.

## Import procedure after deployment

1. Open **Payroll Management → Import Timesheet** using a role with payroll access.
2. Select a CSV or XLSX file, at most 5 MB and 5,000 data rows. XLSX reads the first worksheet. Replace formulas with values before uploading.
3. Choose day/month/year or month/day/year for slash dates, and confirm whether breaks are hours or minutes. ISO dates avoid slash-date ambiguity.
4. Click **Upload**. Recognized, valid files import automatically after server validation. The compact dialog shows column matching only when required columns cannot be identified.
5. If prompted, match the columns or select the employee. A file without employee numbers can be assigned directly to one employee; recognized employee numbers retain their own account assignments.
6. Click **Continue Import** after resolving the prompts. Invalid rows remain visible and block saving. Internally, every save remains bound to the validated file and attendance state through a short-lived confirmation token.

Required fields: `employee_number`, `time_in`, `time_out`. Include `date` when times do not contain dates. `break_duration` is optional; `break_minutes` is recognized as minutes. Supplied `total_hours` and `night_diff_hours` are not trusted as calculated attendance totals.

Example:

```csv
employee_number,date,time_in,time_out,break_minutes
YOUR-EMPLOYEE-NUMBER,2026-08-10,22:00,06:00,60
```

This represents an overnight session ending the next day, with a one-hour break and seven net attendance hours.

## Expected result

Payroll Management's **Export Timesheet** now downloads an XLSX workbook. `Timesheet` and `Timesheet Totals` use the system timesheet service; `Payroll Summary` uses the existing payroll attendance-summary endpoint's calculations for the selected employee and cutoff. The payroll formulas and stored payroll data are unchanged. The summary contains attendance inputs, not unsaved manual payroll adjustments or a saved payslip snapshot. Differences between system timesheet policy and payroll policy remain explicitly separated rather than silently changing payable values.

Export verification: 60 payroll attendance summaries compared unchanged before/after; live workbook checked against 24 detailed attendance rows, system totals, and payroll summary fields, plus an empty cutoff. Numerical totals compared within floating-point serialization precision.

All ready rows are saved in one transaction with an audit entry containing the import batch, source row, and file hash. Exact duplicates are reported and skipped. Conflicts block the entire batch; the importer does not overwrite existing attendance. A repeated confirmation cannot insert the same batch again.

## Common issues and fixes

- **Unknown employee number:** verify Employee Management and explicitly select the intended employee. Recognized numbers cannot be redirected to another employee.
- **Conflicting or open attendance:** review the existing record before importing. Do not change file dates to bypass a conflict.
- **Invalid or missing timestamp:** use full timestamps or a separate date with both clock times. Open sessions and future attendance cannot be imported.
- **Protected payroll period:** a saved payslip covering the shift, or a paid payroll record for that month, blocks new imported attendance. The paid-month protection is intentionally conservative.
- **Preview expired or records changed:** upload and preview again. No partial batch is saved when validation or audit logging fails.
- **Test site unavailable:** start the isolated test services before reviewing the staged screens.

## Verification and remaining work

The isolated checks passed: 13 unit tests / 157 assertions, 23 attendance integration checks, 26 import integration checks, and 373 HTTP assertions across 473 recorded role/attendance/import cases. The import checks include unrecognized headers, explicit column selection, committing the mapped result, and rejecting reuse of one column for two fields. Synthetic fixtures were removed; database fingerprints showed no remaining test-data differences. These checks do not constitute a visual browser test or certification of all payroll calculations.

Still outstanding:

- Visual acceptance of the import workflow with the user's actual workbook layout. Files still require a header row; column matching does not convert summary reports into individual attendance rows.
- Human review of historical duplicate open sessions and closed sessions retaining an unfinished break. Actual correction times must be confirmed.
- Attendance correction/approval workflow and task-log validation improvements.
- Payroll policy review: existing overtime/undertime classification is preserved, including comparison against the gross scheduled window; night-overlap hours are not a certification of legally payable night differential.

Related: [Access control](ACCESS_CONTROL.md), [Documentation standards](DOCUMENTATION_STANDARDS.md).
