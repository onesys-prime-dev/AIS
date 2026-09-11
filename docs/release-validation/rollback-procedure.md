# Rollback Procedure — AIS Payroll System

This matches the project's actual deployment reality: no CI/CD, no staging server — deployment means copying/pulling code into the live XAMPP `htdocs\AIS` directory, which shares one live MySQL database (`ais`) with no separate environment. There is no automated rollback tooling in the repo (confirmed absent during release validation); this procedure is manual.

## Before deploying anything

1. **Back up the live database:**
   ```powershell
   cd C:\xampp\mysql\bin
   .\mysqldump.exe -u root ais > C:\xampp\htdocs\AIS_backups\ais_pre_deploy_<yyyyMMdd_HHmmss>.sql
   ```
   Verify the file is non-trivial (check size, spot-check it contains `INSERT INTO` for `employees`, `payroll`, `employee_payslips`, `auth_identities`).

2. **Note the current commit** the live directory is on:
   ```powershell
   cd C:\xampp\htdocs\AIS
   git rev-parse HEAD
   ```
   Record this hash somewhere retrievable (deploy log, chat, ticket) — it's your rollback target.

3. **Check for uncommitted local changes** in the live directory before deploying (a copy-deployed directory can accumulate untracked edits):
   ```powershell
   git status --short
   ```
   If anything unexpected shows up, investigate before proceeding — don't deploy over unknown local state.

## If a deployment fails or introduces a regression

### 1. Code rollback

Prefer `git revert` (adds a new commit undoing the bad one) over `git reset --hard` (rewrites history) unless you've explicitly confirmed no one else depends on the current history:

```powershell
cd C:\xampp\htdocs\AIS
git log --oneline -10          # confirm current HEAD and identify the bad commit(s)
git revert <bad-commit-hash>   # for a single bad commit
# or, to return to a known-good commit exactly:
git checkout <previous-good-commit-hash> -- .
```

Do not use `git reset --hard` or `--no-verify`/`--no-gpg-sign` unless explicitly instructed — these bypass safety checks and can discard work.

### 2. Database rollback

Only do this if the bad deploy changed data or schema that must be discarded. If only application code changed (no migrations run, no data corruption), **skip this step** — restoring the DB would also discard any legitimate payroll work done during the bad-code window.

```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -e "DROP DATABASE ais; CREATE DATABASE ais CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
.\mysql.exe -u root ais < C:\xampp\htdocs\AIS_backups\ais_pre_deploy_<yyyyMMdd_HHmmss>.sql
```

### 3. Migration rollback

Only 9 migrations exist in `app/Database/Migrations/` (all small incremental ALTERs — see the release validation report for the full list and the confirmed gap: most of the schema was never migration-managed). If the bad deploy included one of these:

```powershell
cd C:\xampp\htdocs\AIS
php spark migrate:status      # see what's actually applied
php spark migrate:rollback    # rolls back the most recent batch
```

Because most tables are not migration-managed, `migrate:rollback` will not undo hand-made schema/data changes — for those, the database-dump restore in step 2 is the only real rollback path.

### 4. Configuration/environment considerations

`.env` is git-ignored (confirmed), so a code rollback (`git checkout`/`git revert`) will **not** touch it automatically. If the bad deploy required an `.env` change (a new setting key, a changed DB name, etc.), manually revert that specific key — check `.env.example`/`env` (the template file at repo root) or your deploy notes for the prior value. Do not blindly overwrite `.env` wholesale; it holds live credentials and settings unrelated to the rollback.

### 5. Post-rollback verification

Confirm the rollback actually restored a working state before considering the incident closed:

1. **Login smoke test**: log in with an existing account, confirm the dashboard loads without errors.
2. **Payroll smoke test**: for one employee (real or synthetic), generate/save one payslip and confirm net pay computes to a plausible, non-error value.
3. **Export smoke test**: run `exportRegister` or generate one payslip PDF and confirm the file produces without a server error.
4. **Check the error log** for the affected period: `writable/logs/log-<date>.log` — confirm no new unhandled exceptions since the rollback.
5. **Diff-check**: `git status` should be clean at the target commit (no unexpected local modifications left over from the failed deploy).

If all five checks pass, the previous stable version is confirmed working and the rollback is complete. If step 2 or 3 still fails after a code-only rollback, the root cause may be in data/schema state rather than code — proceed to the database rollback (step 2 above) even if you initially thought it was code-only.

## Reference

Full validation evidence (scenarios tested, bugs found, fixes applied) is in [`2026-09-09-release-validation-report.md`](./2026-09-09-release-validation-report.md) in this same directory.
