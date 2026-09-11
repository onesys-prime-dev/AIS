# Portal access restrictions

Applied to the main AIS site on 2026-09-08. Permission checks run after authentication for portal and settings routes. Public registration separately restricts privileged role selection. Sidebar visibility uses the same permission rules; changing a URL or sending an API request does not bypass them.

| Role | Management access |
| --- | --- |
| Super Admin | All management permissions, including privileged registration |
| Admin | Employee records, compensation, payroll, loans, attendance review, requests, announcements, support and branding |
| Client Payroll Staff | Existing payroll and employee management workflows, attendance review, requests, announcements, support and branding |
| HR Admin | Employee profiles and schedules, leave requests, approval codes, attendance review and announcements; no other employees' compensation, payslips or loans |
| Team Head | Attendance and requests for explicitly assigned employees; cannot approve their own request or generate global approval codes |
| Employee, User, Beta, Developer, or no assigned role | Personal profile, attendance, timesheets/tasks, requests, payslips, support submissions and personal settings |

Every authenticated role retains personal self-service. Roles are additive: an account with both HR Admin and Payroll Staff also has Payroll Staff's salary permissions. Developer's legacy administration permissions do not grant portal payroll access.

The designated sample account **Dela Cruz, Juan Bonifacio**, username **Juan**, has **Super Admin plus its existing Client Payroll Staff role**. Its employee profile and password were preserved. Access is granted by group membership; application code contains no name-based exception.

## Data boundaries

- HR responses redact salary, allowances and de minimis fields in employee page data, detail JSON and exports. HR edits preserve existing compensation, including when submitted values attempt to overwrite it. The current employee import includes compensation and requires compensation permission.
- Only payroll managers can compute, import/export payroll, change payment status, send payslips, manage loans, or browse all payslips. Personal payslip endpoints retain owner checks.
- Team assignment uses `team_access_assignments`, not department names or job titles. The attendance review selector returns only allowed employees and excludes salary data. Request lists and updates enforce the same assignment boundary.
- Accounts below Super Admin cannot update privileged employee accounts through Employee Management, preventing login-email changes from becoming account takeover. Their personal profile page remains available.
- Public registration creates Employee accounts only. Privileged registration requires Super Admin permission. Existing accounts without groups are not automatically promoted.
- Unknown authenticated portal endpoints fail closed. Add a permission rule and a regression check when introducing a route.

## Assigning roles and teams

The local server operator can use `php spark ais:access list` to inspect current memberships. From the application directory:

```text
php spark ais:access grant USER_ID hr_admin
php spark ais:access grant HEAD_USER_ID team_head
php spark ais:access assign HEAD_USER_ID EMPLOYEE_ID
php spark ais:access unassign HEAD_USER_ID EMPLOYEE_ID
php spark ais:access revoke USER_ID GROUP
```

Replace placeholders with verified IDs. Grant adds a role without removing existing memberships; revoke removes only the specified role. Removing Team Head also clears its assignments. The command prevents removing the last active Super Admin. No HR or Team Head assignments were guessed during deployment.

## Verification and recovery

- Isolated regression: 454 recorded checks, including 354 assertions, with no failed assertions or leftover fixture rows. Covers the role matrix, team boundaries, HR exports and edits, protected login updates, registered management routes, and designated sample account.
- PHPUnit in the isolated copy: 7 tests and 141 assertions passed. The committed route test checks that every portal/settings route has an explicit access rule.
- Main-site verification: 24 GET checks passed using the designated sample account and an existing Employee account.
- PHP syntax and Git whitespace checks passed.

Private evidence is stored outside the web root at `C:\Users\Stanley\AIS-Recovery\20260907-115226\access-implementation-20260908`. The fresh pre-deployment backup is `before-live-20260908-085442`: original changed files, database dump and checksum, original view definition, prior sample-account groups, and applied migration information. These are local backups, not a new GitHub push.

To roll back, first preserve any newer data. Restore the original files listed in `changed-files.json`, remove only newly added access files, and restore the prior sample-group membership if desired. The added team table can remain inert while its assignments are preserved. Full database restoration should use the private dump and separate view definition only when newer records have been accounted for.

Verification used temporary authenticated sessions. Password login, face recognition, browser interaction, real email delivery and payroll calculation accuracy were not retested end to end. This change implements access restrictions; the broader timekeeping/payroll remediation plan remains separate. Only the team-assignment migration was applied to live; pending Phase 1 migrations were not applied as part of this deployment.

## Follow-up verification — 2026-09-08, 13:42

The main EmployeeController contained an extra backtick in a query-builder call, causing a PHP parse error before the page could run. Removed that character; syntax validation passed and the controller again matches the isolated tested version.

Repeated verification passed: 24 main-site access checks, all 454 isolated regression records (354 assertions), and 7 PHPUnit tests (141 assertions). Synthetic records were cleaned up with no database fingerprint differences. The missing local copy of this guide was restored.

Overall database stability is still unresolved. The first main-site dashboard request failed with `MySQL server has gone away`. MariaDB restarted at 13:39:50 and logged InnoDB page/log-sequence consistency errors for AIS tables, including authentication and payroll tables. Later page checks and a fresh logical database dump succeeded; these do not establish that the underlying database files are healthy. No recovery mode or database repair was applied.

A fresh readable database dump, SHA-256 checksum and recent MariaDB log excerpt are preserved outside the web root in `access-implementation-20260908/recheck-20260908-134228` under the private recovery directory above. Database recovery should be handled before treating the whole system as stable.
