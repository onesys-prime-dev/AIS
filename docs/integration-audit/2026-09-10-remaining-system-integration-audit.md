# Remaining System Integration Audit — AIS

**Date:** 2026-09-10 (updated same day — announcement attachment fix)
**Baseline commit:** `fae3157fc30a3aaf5732f9eab67e05162eeba933` (branch `myBranch`)
**Method:** Full code inspection (Dashboard, Notifications, Announcements, Help, Settings, Audit logs, Routes, dead code) plus hands-on verification of every fix against the isolated `ais_validation` DB / `AIS_validation` app copy used in the prior release-validation pass. No live `ais` data touched except the code fixes described below, applied to the live source and mirrored into the validation copy for testing.

## Summary

| Area | Consistent w/ core workflow? | Issues found | Fixed |
|---|---|---|---|
| Dashboard totals | Yes (shows no aggregates, so no cross-source drift is possible today) | 1 minor (unfiltered request list) | No — documented |
| Notifications | Yes | None | — |
| Announcements | Mostly | 1 significant (attachment auth), 2 minor (viewed/dismiss don't re-check audience, deletion unlogged) | **Yes** (attachment auth fixed; 2 minor items still documented only) |
| Help module | Yes | Minor (no audit logging) | No — documented |
| Settings | **No** | **1 critical** (unrestricted write to company payroll settings) | **Yes** |
| Audit logs | Partially | Large coverage gaps (8 categories) | No — documented |
| Broken routes | 1 confirmed broken route | 1 | **Yes** |
| Obsolete code | Several candidates | 5 findings | 2 removed, 3 documented (need a decision) |

---

## 1. Dashboard totals

**Finding:** The dashboard (`app/Controllers/Client/DashboardController.php::index()`) displays **no org-wide aggregate totals at all** — no employee count, no payroll sum, no attendance-stats aggregate. Everything shown is scoped to the logged-in user (their own clock-in status, break minutes, saved tasks) plus one payroll-staff-only widget: a list of all `requests` records (leave/OT/undertime requests).

**Result:** The specific risk this task asked about — a dashboard total drifting from what the core payroll/employee workflow shows — **does not currently exist**, because the dashboard doesn't compute or display any such total. Confirmed by reading the full controller and its two model calls.

**Minor issue found:** the payroll-staff "requests" list on the dashboard (`DashboardController::index()`, lines ~60-66) has no status filter and no limit — it pulls every request, all statuses, all time, via a plain `findAll()`. If the intent is a "pending approvals" widget, it's currently showing everything instead. Not fixed — this is a product-intent question (what should the widget show?), not a clear bug, so flagging for a decision rather than guessing.

**Latent risk to flag:** both `payroll_transactions` (empty/legacy table) and `employee_payslips` (the real one) exist in the codebase. If a payroll-total tile is ever added to the dashboard in the future, it must read from `employee_payslips` — the same source the real Payroll Register uses — not `payroll_transactions`.

---

## 2. Notifications

**Finding:** Correctly scoped throughout. `RequestNotificationService::getVisibleNotificationsForUser()` filters every read by `recipient_user_id = current user`. The mark-viewed and dismiss endpoints (`RequestNotificationController`) each independently re-check `recipient_user_id` before updating a row, so a user cannot act on another user's notification by guessing/incrementing an ID (returns not-found instead). Badge counts are computed client-side from the same already-scoped array the list uses, so they can't drift from the list itself.

**Result:** No issues found. **PASS.**

---

## 3. Announcements

**Finding — visibility scoping (list): correct.** `AnnouncementService::applyVisibilityScope()` correctly restricts `audience='payroll'` announcements to `client_payroll_staff`/admin roles and `audience='department'` announcements to users in the matching department, in addition to an expiry check and already-dismissed exclusion. Write access (`store()`/`delete()`) is correctly gated to `client_payroll_staff`.

**Finding — attachment downloads: confirmed unauthorized — FIXED.** Attachments were saved as plain files under `public/uploads/announcement_attachments/` with no dedicated, authenticated download route (unlike payslips, which are served through an authenticated controller action). **Empirically verified before the fix:** a request with zero session cookie (fully logged out) successfully downloaded a real attachment file (`200 OK`, correct file size and content). The audience/department restriction that correctly gates the announcement *list* was never re-checked at file-serving time — it only prevented a restricted announcement from *appearing in the list*; the file itself was a public, unauthenticated static asset reachable by anyone with the URL.

**Fix applied:**
- Moved attachment storage from `public/uploads/announcement_attachments/` to `writable/uploads/announcement_attachments/` (outside the webroot — already `.gitignore`d, matching how session/cache/logs are handled). The two existing real attachment files were moved, not copied, so the old public URL no longer serves anything.
- `AnnouncementController::store()` now writes new uploads to the `writable/` path.
- New `AnnouncementController::download(int $attachmentId)` action (route: `GET client_portal/announcements/download/(:num)`, inside the existing `auth`-filtered group) looks up the attachment, calls a new `AnnouncementService::canUserAccessAnnouncement()` (same audience/department/expiry rules as the list-visibility check, minus the dismissal exclusion — dismissing an announcement from your feed shouldn't revoke access to a file you were legitimately shown), and only then streams the file. A denied or nonexistent attachment returns a plain 404 (doesn't reveal whether the ID exists), mirroring the pattern already used for `ensurePayrollStaff()` elsewhere in the app.
- `NotificationService` now builds attachment links pointing at this authenticated route instead of a raw public path — no other frontend/JS changes were needed since the attachment JSON shape (`file_path`, `original_name`, `mime_type`, etc.) is unchanged, only the URL it resolves to.

**Retested (all against `ais_validation`):**
- Old public URL for a real attachment → `404` (file physically gone from the webroot).
- New download route, zero session → redirects to login (`302`).
- Announcement with `audience='all'`, both a plain `user`-group account and `client_payroll_staff` → `200`, correct file, correct size (byte-for-byte match to the original).
- A new test announcement with `audience='payroll'` and its own attachment: plain `user`-group account → `404` (correctly blocked, not just "forbidden" — doesn't confirm the attachment exists); `client_payroll_staff` account → `200`.
- Fresh upload through the real `store()` endpoint → landed in `writable/uploads/announcement_attachments/` (confirmed via filesystem listing), immediately downloadable through the new authenticated route.
- **PASS** on all of the above.

**Minor items still not fixed (unchanged from before):**

**Minor findings (not fixed, low severity):**
- `viewed()` and single-ID `dismiss()` don't re-verify the announcement was actually visible/targeted to the calling user before writing a view/dismissal row (only the "dismiss all visible" variant re-derives the eligible set). Low impact — it only writes a marker, not a data leak.
- `AnnouncementService::deleteAnnouncement()` never calls `ActivityLogger` — announcement deletions are silent in the audit trail (see Section 6).

---

## 4. Help module

**Finding:** Access control is reasonable and matches intent: any authenticated user can view/submit their own tickets; `client_payroll_staff` additionally sees and can respond to all tickets. `superadmin`/`admin`/`developer` are not special-cased (they fall into "see only your own" unless also in `client_payroll_staff`) — plausibly intentional (help desk staff, not admins generally, handle tickets), not flagging as a bug without a business-rule confirmation.

**Issue found:** No `ActivityLogger` integration anywhere in the Help module — ticket creation and staff responses/status changes are not audit-logged at all. Not fixed — see Section 6 for the consolidated audit-log gap list.

---

## 5. Settings — critical issue found and fixed

There are four separate settings surfaces in this codebase; each was checked independently.

### 5.1 `payroll_settings` (company-wide payroll parameters) — **CRITICAL, FIXED**

**Root cause:** `PayrollController::saveSettings()` (the endpoint that writes government-contribution rates — `phic_rate`, `phic_cap`, `hdmf_rate`, `hdmf_max` — plus ~15 other parameters: monthly working hours, night-differential rate, overtime rate multipliers, semi-monthly period cutoff days) had **no role/permission check of any kind**. Every other admin-facing method in the same controller uses the same repeated pattern —
```php
$user = auth()->user();
$isAdmin = $user->inGroup('client_payroll_staff') || $user->inGroup('superadmin') || $user->inGroup('admin');
```
— but `saveSettings()` was missing it entirely. Combined with the route-level `auth` filter only requiring login (no group), **any authenticated user in any Shield group — including a plain `employee` or `user` account — could POST to `client_portal/payroll/saveSettings` and change company-wide payroll computation parameters**, including the tax/contribution rates that determine every employee's net pay.

**Fix applied** (`app/Controllers/Client/PayrollController.php`, `saveSettings()`): added the same `isAdmin` check used elsewhere in the file, returning `Unauthorized` and leaving the settings untouched for any non-staff caller.

**Retest:**
- `zztest-user` (plain `user` group) → POST `setting_phic_rate=0.99` → request redirected, **no error surfaced to the attacker, but the database value was confirmed unchanged** (`phic_rate` still `0.05`). **PASS.**
- `zztest-client_payroll_staff` → POST `setting_hdmf_rate=0.025` → **database value confirmed updated to 0.025**, then reset back to `0.02`. Legitimate access still works. **PASS.**

**Propagation confirmed live (no caching risk):** `PayrollSettingsModel::getSetting()` runs a fresh DB query every call, and `PayrollComputationService` re-reads it on every computation — a saved change takes effect on the very next payroll run, no staleness.

**Audit logging gap on this same endpoint (documented, not fixed):** only the 4 "contribution" keys are captured in the audit log entry when changed (`service('activityLogger')->payrollSettingsUpdated($changed)`); the other ~15 parameters (working hours, OT/night-diff rates, period cutoff days) are saved via the same loop but never logged, even now that unauthorized writes are blocked. See Section 6.

### 5.2 `user_settings` (personal preferences) — correct

`SettingsController::saveCustomization()` scopes personal color/UI preferences to `(int) $user->id` — inherently self-service, no elevated permission needed, correctly implemented.

### 5.3 `company_branding` / company logo — correct

The one company-wide setting bundled into the same personal-settings controller (logo replacement) is correctly gated: `if (auth()->user()->inGroup('client_payroll_staff'))` before allowing the file to change.

**Issue found (not fixed):** no `ActivityLogger` calls anywhere in `SettingsController.php` — neither personal preference changes nor company logo/branding changes are audit-logged. See Section 6.

### 5.4 Loan settings — issue found (not fixed)

`LoanController::saveLoanSettings()` has no role check beyond the generic `auth` filter (same class of issue as 5.1, not yet verified with the same empirical rigor since it was lower severity than the payroll-parameters case — flagging for the same treatment). No `ActivityLogger` usage anywhere in `LoanController.php`. Recommend applying the same `isAdmin` pattern here in a follow-up pass.

---

## 6. Audit logs — coverage gap analysis

**Infrastructure is sound.** `app/Services/ActivityLogger.php` is a well-designed, module-scoped logger with before/after diffing and PII masking, and the viewer (`PayrollController::getAuditLog()` / `payroll_audit_log.php`) is correctly restricted to `client_payroll_staff`/`admin`/`superadmin`, with adequate who/what/when/old→new detail for whatever it does capture.

**What IS logged (confirmed):** attendance clock in/out and breaks; employee self-service profile updates (contact, emergency contact, photo, status); payroll row create/rate-change/status/delete; payslip edit/send/generate; the 4 contribution settings keys; SSS/withholding-tax bracket table changes; leave/OT/undertime requests (file, status change, approval codes); timesheet tasks (log/update/delete).

**What is NOT logged — confirmed gaps, all left as documentation for now (each is a real but separate follow-up, not a one-line fix):**

1. **Employee onboarding/lifecycle** — `EmployeeService::createEmployee()`/`updateEmployee()` have zero logging calls, despite `ActivityLogger` already having purpose-built, ready-to-use methods for exactly this (`employeeCreated()`, `employeeDeleted()`, `governmentIdsCreated()`, `positionCreated()`, etc.) that are simply never called anywhere in the codebase. New-hire SSS/TIN/PhilHealth/Pag-IBIG numbers and account creation are completely unaudited.
2. **User role/group changes** — `roleAssigned()`/`roleRemoved()` exist but have zero callers; group assignment during employee creation (`addGroup('employee')`) is silent.
3. **Manual attendance edits by staff** — `attendanceManualEdit()` exists but has zero callers; `TimesheetController::updateAttendance()` updates clock-in/out times directly with no audit trail.
4. **Loans — entirely unaudited** — zero `ActivityLogger` usage anywhere in `LoanController.php`: loan creation, approval, completion, cancellation, and settings changes are all silent, despite being financially significant.
5. **Announcement deletion** — logged on create, silent on delete.
6. **Most settings changes** — see Section 5 (only 4 of ~19 payroll-settings keys logged; personal/company-branding settings not logged at all).
7. **Help module** — no logging (Section 4).
8. **Minor: username changes logged under the wrong method** — `AccountService::updateUsername()` reuses `userStatusChanged()` rather than a dedicated method, so it displays as "User status updated" in the audit viewer instead of a clear username-change entry. Functionally captured, just mislabeled.

These gaps appear to be incomplete wiring rather than a deliberate design choice — several of the missing logger calls already exist as unused methods in `ActivityLogger.php`, suggesting the intent was there but the call sites were never connected. Recommend a dedicated follow-up pass to wire these in, prioritized by risk: employee PII/onboarding and loan operations first, then role changes and manual attendance edits, then the lower-risk settings/help/announcement-deletion gaps.

---

## 7. Broken links/routes — fixed

**Finding:** `app/Config/Routes.php` registered `GET client_portal/loans → Client\LoanController::loanView`, but `LoanController` has no `loanView` method — hitting this URL directly would throw a framework "method does not exist" error instead of a clean 404. Verified via full route-by-route cross-check against every controller's actual public methods (~120 routes checked; this was the only mismatch found) and a spot-check of frontend links in the dashboard, payroll, employee, and timesheet views (no dead links found there — every `fetch`/form-action/`site_url` target in those views matched a registered route).

**Practical impact:** low — no view anywhere links to the bare `client_portal/loans` URL; the real Loans tab is rendered directly via `employee_main.php → employee_loan.php`, bypassing this route entirely. It was reachable only by typing the URL directly.

**Fix applied:** removed the broken route registration (with a comment explaining why), so the URL now returns a clean `404` instead of a fatal error. **Retested:** confirmed `404` after the fix (previously would have been an uncaught framework exception).

---

## 8. Obsolete/duplicate code paths

| Finding | Confidence | Action |
|---|---|---|
| `app/Models/AttendanceModel.php` (non-`Client` namespace duplicate of `App\Models\Client\AttendanceModel`) | Definitely dead — confirmed zero `use`/instantiation anywhere in `app/` | **Removed** |
| `app/Views/client_portal/payroll_management.php.bak` (2,099-line backup of the live 5,142-line file) | Definitely dead — confirmed zero references | **Removed** |
| `PayrollCutoffController` + `payroll_cutoffs`/`payroll_cutoff_form` views (neither exists) + `PayrollCutoffModel`/`PayrollCutoffSummaryModel` + `PayrollAggregationService` | Probably dead — zero routes registered, referenced views don't exist, no other callers of the aggregation service | **Not removed** — looks like an entire half-built "payroll cutoff period" feature superseded by the current date-range approach, but deleting DB-table-backed models is a bigger call than this pass should make unilaterally. Flagging for your decision. |
| `Client\TaskController::showLogTaskForm()` | Probably dead — zero call sites (no route, no internal reference) | **Not removed** — low value either way, noting for awareness only |
| Two parallel payroll persistence pipelines (`payroll_transactions` via `computeAndSave()` vs. `employee_payslips` via `savePayslipRecord()`/`savePayslipEditData()`, the latter confirmed to be what actually drives the visible Payroll UI) | Confirmed (from the prior release-validation pass, re-confirmed here) | **Not removed** — this is an architectural "which pipeline is the source of truth going forward" decision, not a dead-code cleanup; both are still reachable from the live UI |
| Root-level one-off scripts `fix_contributions.php`, `sss_fix_2025.sql` | Probably safe to archive/remove — both are already-applied one-time patches, not referenced by any app code; keeping them risks someone re-running them against an already-fixed database | **Not removed** — git blame attributes both to you; flagging so you can confirm and remove/archive them yourself rather than assuming |
| `preload.php` at repo root | Stock CodeIgniter 4 framework scaffolding, not project logic, not wired to anything active | **Not removed** — harmless, not really in scope of "obsolete business logic" |

---

## Fixes applied in this pass (summary)

1. **`app/Controllers/Client/PayrollController.php` — `saveSettings()`**: added the missing `isAdmin` authorization check (matches the pattern used ~8 other places in the same file). Retested: unauthorized writes now rejected and leave the DB unchanged; authorized writes still succeed.
2. **`app/Config/Routes.php`**: removed the broken `GET client_portal/loans → LoanController::loanView` route (method never existed, nothing links to it). Retested: now returns a clean 404 instead of a fatal framework error.
3. **`app/Models/AttendanceModel.php`**: removed (confirmed dead duplicate of the `Client`-namespaced model actually used everywhere).
4. **`app/Views/client_portal/payroll_management.php.bak`**: removed (confirmed unreferenced backup file).
5. **Announcement attachment authorization** (`app/Controllers/Client/AnnouncementController.php`, `app/Services/Client/AnnouncementService.php`, `app/Services/Client/NotificationService.php`, `app/Config/Routes.php`): moved attachment storage outside the public webroot (`writable/uploads/announcement_attachments/`) and added an authenticated `download()` action that re-checks the announcement's audience/department/expiry scope before streaming the file. Retested: old public URL now 404s; new route requires login and correctly allows/denies by audience (verified with both an `audience='all'` and an `audience='payroll'` test announcement); a fresh upload through the real form lands in the new location and is immediately downloadable through the authenticated route.

All five changes were mirrored into the isolated `AIS_validation`/`ais_validation` copy (including physically moving the two real attachment files) and verified there before being confirmed safe; the live `AIS` app and database were only touched via these same source-file edits and the equivalent file move (no announcement/attachment data was deleted, only relocated).

## Open items requiring your decision (not fixed in this pass)

- Loan settings missing role check (Section 5.4) — same class of bug as the fixed payroll-settings issue; recommend the same fix, not yet applied.
- Audit-log coverage gaps (Section 6) — 8 categories of unlogged significant actions; infrastructure exists, wiring doesn't.
- Dashboard "requests" widget scope (Section 1) — unclear if "show everything" or "show only pending" is intended.
- Orphaned `PayrollCutoff*`/`PayrollAggregationService` code and root-level one-off scripts (Section 8) — candidates for removal, deferred to you.
