# Attendance reminders

Reminders to clock in, clock out and take breaks, relative to each employee's own schedule. Admin settings live in
Access Control -> Reminders; per-employee overrides are in the employee form (Work Schedule). Employees switch their own
channels in Profile -> Reminders.

## What gets sent

| When | Channels | Notes |
|---|---|---|
| Clock in: -10, 0, +10, +30, +60 min from shift start | pop-up, push; email from 0 on | "before" email is off by default (admin switch) |
| +30 min with no clock-in | email to the Team Head and every Admin / Super Admin | one grouped email per recipient; skipped if the employee opted out |
| Clock out: -10, +30, +120 min from shift end (or the end of approved OT with a time span) | pop-up, push; email | stops 240 min after |
| Break window opens / 10 min before it closes / 2 min before a break ends | pop-up, push | never emailed; only breaks with a time window get window reminders |

Everything is computed live from attendance, leave, holidays and approved overtime, so a reminder disappears the moment
it is no longer true. After a scheduler outage only the current step is sent (no catch-up burst).

## Deploying (each environment)

1. `php spark migrate` (creates `attendance_reminders`, `attendance_reminder_prefs`, `attendance_reminder_overrides`, `push_subscriptions`).
2. `composer install` (adds minishlink/web-push and guzzlehttp/guzzle). Push needs PHP `openssl` and `gmp` or `bcmath`.
3. Email: set a real `fromEmail` in the Email config. With it blank, reminders are logged as `no_sender` and nothing is sent.
4. Browser push (optional): `php spark attendance:push-keys`, add the three printed `push.*` lines to that environment's `.env`
   (never commit the private key). Each environment needs its own keys. Employees then turn push on in Profile -> Reminders.
   Push only works over HTTPS (or localhost).
5. Scheduler, every 5 minutes:
   - Linux: `*/5 * * * * cd /path/to/AIS && php spark attendance:send-reminders >> writable/logs/reminders-cron.log 2>&1`
   - Windows Task Scheduler: run `php spark attendance:send-reminders` every 5 minutes.
6. Dry run first: `php spark attendance:send-reminders --dry-run --now "2026-11-23 09:30"` lists who would be reminded and sends nothing.

Access Control -> Reminders shows whether the scheduler ran recently, whether email and push are set up, and which
employees the reminders cannot reach (no schedule, no email, opted out).

## Testing safely

Tests must never send real email: blank `config('Email')->fromEmail` or inject a fake mailer into
`AttendanceReminderDelivery`. The dev machine has live SMTP credentials.
