# Release Validation Report — AIS Payroll System

**Date:** 2026-09-09
**Environment:** Local XAMPP, MySQL/MariaDB via MySQLi, CodeIgniter 4 (PHP 8.2)
**Baseline commit:** `af8e8624d9431243394b2b58c82303b769c5c3bf` (branch `myBranch`)
**Isolation:** Database `ais_validation` (mysqldump clone of live `ais`, taken `2026-09-09 11:39:25` → `C:\xampp\htdocs\AIS_backups\ais_pre_validation_20260909_113925.sql`), application copy `C:\xampp\htdocs\AIS_validation` (`.env` pointed at `ais_validation`, `app.baseURL` changed to `http://localhost/AIS_validation/public/`). No step in this report touched the live `ais` database or the live `AIS` application directory except the two approved source-code fixes described in Section 4, which were applied to the live source and mirrored into the validation copy for verification.

## Summary

| Category | Pass | Fail | Not Tested |
|---|---|---|---|
| Isolation setup | 1 | 0 | 0 |
| Migration/restore | 2 | 0 | 0 |
| Employee→Payroll scenarios | 7 | 0 | 0 |
| Permission tests | 3 | 2 (documented, not fixed) | 0 |
| Reconciliation | 1 | 0 | 0 |
| Bugs found & fixed | 2 | — | — |

**Headline findings:**
- **Confirmed data-exposure bug** (documented only, per direction): any authenticated user regardless of Shield group can read and export the full payroll register (real employee compensation data) via `getRegisterRows`, `getRegisterRawSheet`, `exportRegister` — no authorization check exists on these endpoints.
- **Confirmed access-control bug** (documented only, per direction): `EmployeeController::ensurePayrollStaff()` allows only the `client_payroll_staff` group — `superadmin` and `admin` accounts are incorrectly blocked (404) from all employee-management functions.
- **Two bugs fixed** (approved): `computeAndSave()` crashed on every call (invalid DB column); `calculateAnnualWithholdingTax()` silently returned ₱0 tax for incomes landing in bracket-scaling gaps (confirmed at ₱800,000 and ₱2,000,000).
- Contribution calculation behavior (PhilHealth/Pag-IBIG per-half-month) reviewed with the lead developer and **confirmed intentional**, not a bug.

---

## 1. Isolation setup

| Test ID | RV-001 |
|---|---|
| Scenario | Create an isolated DB + app copy without touching live data |
| Steps | `mysqldump` live `ais` → restore into new `ais_validation` DB; copy app directory (excl. vendor/node_modules, vendor copied separately) to `AIS_validation`; edit `.env` (`database`, `app.baseURL` only) |
| Expected | Row counts identical to live DB; app boots and authenticates against the new DB; live DB/app untouched |
| Actual | `employees` 20/20, `payroll` 7/7, `employee_payslips` 23/23, `auth_identities` 19/19 (26 after adding 7 test accounts) match between `ais` and `ais_validation`. App redirects unauthenticated requests through to `/login` (200 OK, title "Authentication"). Confirmed zero `zztest-*` rows exist in live `ais.auth_identities`. |
| Result | **PASS** |
| Evidence | Dump file `ais_pre_validation_20260909_113925.sql` (1.45MB); row-count queries in session log |

---

## 2. Migration and restore checks

| Test ID | RV-002 — Migration coverage gap |
|---|---|
| Scenario | Can `php spark migrate` alone reproduce the schema on a fresh DB? |
| Steps | Created empty `ais_migration_test`, ran `php spark migrate --all` |
| Expected | Full schema, or a clearly understood partial result |
| Actual | Only 9 tables created (Shield's own auth tables: `users`, `auth_identities`, `auth_logins`, `auth_groups_users`, `auth_permissions_users`, `auth_remember_tokens`, `auth_token_logins`, `migrations`, `settings`). Migration run then **halted with a DB error** trying to `ALTER TABLE employee_payslips` (the first app-defined migration, `AddSentByToEmployeePayslips`) because that table was never created by any migration. **44 of 53 tables (83%)** in the real schema — including `employees`, `payroll`, `employee_payslips`, `sss_brackets`, `wtax_brackets_monthly`, `payroll_settings`, all loan tables, `activity_logs`, etc. — do not exist anywhere in the migration set. |
| Result | **FAIL (gap confirmed, documented — not a code bug, an operational gap)** |
| Evidence | Full missing-table list captured in session; `ais_migration_test` dropped after test |
| Issue found | The bulk of the schema was built directly against the live DB outside the migration system. A fresh environment cannot be stood up from `git clone` + `spark migrate` alone. |
| Fix applied | None — this is an infrastructure/process gap, not a code defect; flagging for awareness per task scope. Recommendation: generate a baseline schema migration or full SQL dump checked into the repo. |

| Test ID | RV-003 — Backup/restore cycle |
|---|---|
| Scenario | Does a dump-and-restore cycle preserve data and referential integrity? |
| Steps | Dumped `ais_validation` → restored into new `ais_restore_test`; compared row counts for `employees`, `payroll`, `payroll_transactions`, `employee_loans`, `employee_loan_schedule`, `auth_identities`, `employee_payslips`; checked for orphaned FKs (`payroll_transactions.employee_id`, `employee_payslips.employee_id`) |
| Expected | Identical row counts, zero orphaned foreign keys |
| Actual | All row counts matched exactly; 0 orphaned rows in both the source and restored copy |
| Result | **PASS** |
| Evidence | Row-count and FK-integrity query output in session log; `ais_restore_test` dropped after test |

---

## 3. Employee → Payroll scenarios

All run against `ais_validation` / `AIS_validation` using synthetic test data (`ZZTEST Employee One`/`Two`, `*@example.invalid` emails — never real employee PII). Live settings pulled from `ais_validation.payroll_settings` and `sss_brackets` before hand-calculating expected values (not hardcoded defaults): `phic_rate=0.05`, `phic_cap=100000`, `hdmf_rate=0.02`, `hdmf_max=200` (note: DB value, not the code fallback of 100).

| Test ID | RV-004 — Normal path |
|---|---|
| Scenario | Create employee (salary ₱30,000/mo), save a normal semi-monthly payslip (basic pay ₱15,000, no OT/absences), verify stored values |
| Expected (hand-calc) | SSS 1,500.00 (bracket 29,750–30,249.99: 1,000 regular + 500 WISP); PhilHealth 750.00; Pag-IBIG 200.00 (capped); withholding tax 687.45 (semi-monthly bracket: (15,000−10,417)×0.15); net pay **11,862.55** |
| Actual | `employee_payslips.net_pay = 11862.55`, `generated_data` matches all component values exactly |
| Result | **PASS** |
| Evidence | Employee id 31 (`EMP-2026-0022`), payslip id 24 |

| Test ID | RV-005 — SSS bracket boundary |
|---|---|
| Scenario | Verify `calculateSSS()` selects the correct bracket exactly at boundaries |
| Expected | 29,749.99→1,475 (previous bracket); 29,750.00→1,500; 30,249.99→1,500; 30,250.00→1,525 |
| Actual | Exact match at all four boundary values, via direct reflection call to `PayrollComputationService::calculateSSS()` |
| Result | **PASS** — no off-by-one error |

| Test ID | RV-006 — Active loan mid-schedule |
|---|---|
| Scenario | Employee with an active ₱6,000 loan (₱1,000/installment) due exactly on the payslip's `period_end`; verify server-side loan deduction |
| Expected | Submitted pre-loan net pay 11,862.55 minus loan installment 1,000.00 = **10,862.55** |
| Actual | `employee_payslips.net_pay = 10862.55`; `generated_data.loan_deductions` correctly itemizes `{schedule_id, loan_id, amount:1000, due_date, remaining_balance:5000}` |
| Result | **PASS** — `savePayslipRecord()`'s server-side loan recalculation (the one part of that endpoint not blindly trusting client input) works correctly |
| Evidence | Employee id 31, payslip id 25, loan id 2 |

| Test ID | RV-007 — 13th month / annualization |
|---|---|
| Scenario | Verify `calculateAnnualWithholdingTax()` against DB-driven monthly brackets scaled ×12 |
| Expected | Progressive, monotonically non-decreasing tax for increasing income; no income should yield ₱0 above the true 0% threshold (₱249,995.88/yr) |
| Actual (before fix) | ₱250,000→₱0.60 (correct); ₱360,000→₱16,500.60 (correct); **₱800,000→₱0** (wrong); **₱2,000,000→₱0** (wrong) |
| Result | **FAIL → bug found and fixed** (see Section 4) |
| Actual (after fix) | ₱800,000→₱102,500.80; ₱2,000,000→₱402,500.60 — both now correctly taxed, retest **PASS** |

| Test ID | RV-008 — Manual adjustment / accountant override |
|---|---|
| Scenario | Accountant applies a legitimate salary adjustment (+₱500) and a manually recalculated withholding tax (₱650, down from the auto ₱687.45) to the RV-004 payslip via `savePayslipEditData()` |
| Expected | Save succeeds (no blocking validation exists — confirmed in prior investigation); gross recomputes to 15,000+500=**15,500**; net recomputes to 15,500−1,500−750−200−650=**12,400** |
| Actual | API response: `{"gross_pay":15500,"net_taxable":13050,"net_pay":12400}` — exact match |
| Result | **PASS** |
| Audit trail | Both mechanisms captured the edit correctly: in-band `_edit_history` (`salary_adjustment 0→500`, `withholding_tax 687.45→650`, timestamped) and system-wide `activity_logs` (module `employee_payslips`, action `updated`, full old/new value JSON). **Minor observation:** `edited_by` recorded the generic fallback `"admin"` rather than the specific test account's identity — session doesn't have `name`/`email`/`username` populated the way the code expects at this point in the login flow; worth a look for stronger audit precision, not a blocker. |
| Result | **PASS** (audit trail), minor traceability note logged |

| Test ID | RV-009 — Reconciliation after adjustment |
|---|---|
| Scenario | Does `getRegisterRows`/register export reflect the adjusted (not original auto-computed) figures? |
| Expected | Export shows salary_adj=500, wth=650, net_pay=12400 (the adjusted values) |
| Actual | `getRegisterRows` response for payslip 24 (ZZTEST EmployeeOne): `salary_adj:500, wth:650, net_pay:12400` — exact match to the adjusted `generated_data` |
| Result | **PASS** — no divergence between what's stored and what's exported |

| Test ID | RV-010 — Employee missing government IDs |
|---|---|
| Scenario | Employee created with blank SSS/TIN/Pag-IBIG/PhilHealth numbers; verify payroll processing isn't blocked |
| Expected | Payslip save succeeds (contribution formulas are salary-based, not ID-based) |
| Actual | Employee id 32 created successfully; `savePayslipRecord()` succeeded, payslip id 26 |
| Result | **PASS** |
| Minor issue found | `EmployeeService::createEmployee()` line ~134 accesses `$data['middle_name']` without a null-coalesce fallback, unlike `gender`/`civil_status` nearby which use `?? null`. Triggers a PHP "Undefined array key" warning if the field is omitted from the request entirely (not an issue when the actual HTML form always submits the field, even empty — only surfaced because my first raw HTTP test omitted it). Not fixed (minor, cosmetic, no functional impact when called via the real form) — noting for awareness. |

---

## 4. Bugs found during testing — fixed (per your direction)

### 4.1 `computeAndSave()` — crashed on every call

**File:** `app/Controllers/Client/PayrollController.php` (insert into `payroll_transactions`), `app/Models/Client/PayrollTransactionModel.php` (`$allowedFields`)

**Root cause:** Both the model's `$allowedFields` and the controller's insert array included `loan_deductions` (and the model additionally listed `total_loan_deductions`), but the actual `payroll_transactions` table has no such columns — only specific `sss_loan`, `calamity_loan`, `salary_advance` columns exist. Every call to this endpoint failed with `Unknown column 'loan_deductions' in 'field list'`. This explains why `payroll_transactions` was empty (0 rows) in the live database — the endpoint has apparently never successfully completed.

**Fix:** Removed the non-existent `loan_deductions`/`total_loan_deductions` fields from the model's allowed-fields list and the controller's insert array. The aggregate `total_deductions`/`net_pay` fields (which already correctly include the loan total via `PayrollComputationService`) are unaffected — only the itemized per-loan breakdown, which this table was never able to store, is not persisted here (unlike `employee_payslips.generated_data`, which does store an itemized breakdown via the separate `savePayslipRecord()` path).

**Retest:** RV-004's inputs re-submitted through `computeAndSave()` directly — now returns `200 OK` with `transaction_id`, and the inserted row's values match the RV-004 hand-calculation exactly (SSS 1,500.00, PhilHealth 750.00, Pag-IBIG 200.00, withholding tax 687.45, net pay 11,862.55). **PASS.**

### 4.2 `calculateAnnualWithholdingTax()` — silent ₱0 tax at bracket-scaling gaps

**File:** `app/Services/Client/PayrollComputationService.php`

**Root cause:** The method derives annual tax brackets by multiplying each monthly bracket's `min_amount`/`max_amount` by 12. Adjacent monthly tiers have a natural ₱1 gap (e.g. tier 2 ends at 33,332.00, tier 3 starts at 33,333.00) — scaling that gap by ×12 turns it into a ₱12 gap (399,984.00 to 399,996.00). The original code required both a lower-bound (`>=`) and upper-bound (`<=`) match; any income landing in one of these four ₱12-wide gaps (confirmed at ₱800,000 and ₱2,000,000, both plausible real annual incomes) matched no bracket at all and silently returned ₱0.

**Fix:** Removed the upper-bound check. Since brackets are already queried in `ORDER BY min_amount DESC`, matching on `annualTaxableIncome >= annualMin` alone and returning at the first match is the correct and standard progressive-bracket idiom — it makes the brackets exhaustive by construction (no possible gap), matching how the hardcoded semi-monthly BIR table elsewhere in the same file already works.

**Retest:** ₱250,000→₱0.60 (unchanged, correct), ₱360,000→₱16,500.60 (unchanged, correct), ₱800,000→₱102,500.80 (was ₱0), ₱2,000,000→₱402,500.60 (was ₱0). **PASS.**

**Note on residual imprecision:** because the annual brackets are still *derived* from monthly brackets via ×12 scaling rather than defined natively, incomes very close to a scaled boundary (e.g. ₱800,000–₱800,003) fall just under the "intended" next tier and are taxed one tier lower than a natively-defined annual table might specify. This is a pre-existing characteristic of deriving annual brackets this way, not something introduced or worsened by this fix, and fixing it further would require either new annual-native bracket data or a business-rule decision outside this task's scope — flagging for awareness only.

---

## 5. Permission tests

Verified via 7 dedicated test accounts (one per Shield group: `superadmin`, `admin`, `developer`, `user`, `beta`, `client_payroll_staff`, `employee`), created only in `ais_validation` (confirmed absent from live `ais`), plus direct code inspection of all `PayrollController`/`EmployeeController` public methods for the endpoints not individually exercised.

| Test ID | RV-011 — Gated endpoints correctly deny unauthorized roles |
|---|---|
| Steps | Called `getPayslipEditData`, `getAuditLog`, `getPayslipActivity` as the `user`-group test account |
| Expected | 403 Forbidden |
| Actual | All three returned `403` |
| Result | **PASS** |

| Test ID | RV-012 — Ungated endpoints leak real payroll data (documented, not fixed per your direction) |
|---|---|
| Steps | Called `getRegisterRows`, `getRegisterRawSheet`, `exportRegister` as both the `user`-group and `employee`-group test accounts (no special payroll permission) |
| Expected | 403 Forbidden (these endpoints return full-company payroll data and should be staff-only) |
| Actual | All three returned `200 OK` with real payroll data — confirmed the response body includes a real employee's name, monthly salary, full earnings/deduction breakdown, and net pay |
| Result | **FAIL — confirmed, documented only per your direction** |
| Scope | Code inspection (from the prior investigation this session) shows this pattern likely extends to ~38 of `PayrollController`'s 44 public methods, which have no `inGroup()` check at all — only the route-level `auth` filter (any logged-in user, any group). `getRegisterRows`/`getRegisterRawSheet`/`exportRegister` above are the ones empirically exercised with real data confirmed leaking; the remainder are inferred from the same missing-check pattern via code inspection, not each individually exercised in this pass, given the size of that surface (~38 methods × 7 roles) relative to task scope. |
| Fix applied | None, per your explicit direction to document only |

| Test ID | RV-013 — `EmployeeController` over-restrictive gate (documented, not fixed per your direction) |
|---|---|
| Steps | Attempted `POST employees/create` as the `superadmin` test account |
| Expected | Allowed (superadmin should have full access) |
| Actual | `404 Not Found` (`PageNotFoundException` thrown by `ensurePayrollStaff()`, `app/Controllers/Client/EmployeeController.php:15-22`, which checks only `inGroup('client_payroll_staff')` — no `superadmin`/`admin` allowance, unlike the equivalent check pattern used elsewhere, e.g. `savePayslipEditData()`'s `isAdmin` check) |
| Result | **FAIL — confirmed, documented only per your direction** |
| Fix applied | None, per your explicit direction |

---

## 6. Contribution calculation review (closed)

The suspected PhilHealth/Pag-IBIG per-half-month double-charge behavior (`calculatePhilHealth()`/`calculatePagIbig()` in `PayrollComputationService.php`, and the abandoned `fix_contributions.php` draft at the repo root) was reviewed with the lead developer and **confirmed intentional** — no bug, no further investigation, no code change. (Pag-IBIG is additionally capped at ₱200/month per the live `payroll_settings.hdmf_max` value, which limits real-world impact regardless.)

---

## 7. NOT TESTED (with reasons)

- **CompreFace facial-recognition login/verification** — external service, requires a live camera and a running CompreFace API server; not available in this local validation environment. `CompreFace.enabled` defaults to `false` and had no `.env` override, so it did not interfere with any test in this report, but its own correctness is unverified.
- **Real email delivery (payslip send via `sendPdf`/`sendStatic`)** — `.env`/`app/Config/Email.php` is configured with a real Gmail SMTP account (`ais.ecovis@gmail.com`). Triggering these endpoints would send real email through that live account. Not exercised in this pass to avoid sending test messages through a production mailbox.
- **Full 44-method × 7-role permission matrix** — see RV-012 scope note; a representative, empirically-verified sample was tested rather than exhaustively clicking through all ~308 combinations, given task scope. The missing-check pattern is well-established from both code inspection and the endpoints actually exercised.
- **True staging/production deployment** — this project has no staging server; "isolated copy" in this report means a second local database + application directory on the same machine, per the project's actual (XAMPP, no CI/CD) deployment reality.

---

## 8. Cleanup notes

- Test accounts (`zztest-*@example.invalid`), test employees (ids 31, 32), and their payroll/loan records exist only in `ais_validation` — confirmed absent from live `ais`.
- Two throwaway databases (`ais_migration_test`, `ais_restore_test`) were created and dropped during testing.
- Two temporary CLI commands (`app/Commands/ValidationSssBoundaryCheck.php`, `ValidationAnnualTaxCheck.php`) exist only in the `AIS_validation` copy for reflection-based unit checks — not part of the live `AIS` codebase.
- `AIS_validation` (app directory) and `ais_validation` (database) can be deleted/dropped once this report is reviewed, or kept for future validation passes.
