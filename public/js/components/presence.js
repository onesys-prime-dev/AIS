/**
 * presence.js
 * Handles:
 *   1. Heartbeat — POSTs to server every minute
 *   2. Idle detection — mouse/keyboard inactivity for 10 min → Away
 *   3. Badge polling — GETs statuses every 15 seconds and updates DOM badges
 *
 * Deliberately does NOT treat a hidden/backgrounded tab as Away by itself —
 * switching to another app (email, Slack, a spreadsheet) while this tab
 * stays open in the background is completely normal while still at work,
 * and instantly flipping to Away the moment focus leaves the tab made the
 * status flicker and feel wrong for anyone who wasn't staring at this one
 * tab all day. Only real inactivity (no mouse/keyboard/scroll for 10 min)
 * — regardless of which window has focus — should count as Away.
 */

const Presence = (() => {
    const HEARTBEAT_INTERVAL_MS  = 60 * 1000;  // 1 minute
    const IDLE_THRESHOLD_MS      = 10 * 60 * 1000;  // 10 minutes → Away
    const BADGE_POLL_INTERVAL_MS = 15 * 1000;        // 15 seconds

    const HEARTBEAT_URL = window.PRESENCE_HEARTBEAT_URL || '/client_portal/presence/heartbeat';
    const STATUSES_URL  = window.PRESENCE_STATUSES_URL  || '/client_portal/presence/statuses';

    // Pull CSRF token from meta tag (added to main.php below)
    const getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    let currentStatus  = 'Online';
    let lastActivity   = Date.now();
    let heartbeatTimer = null;
    let pollTimer      = null;

    // ── Idle + Visibility Detection ──────────────────────────────────────────

    const resetActivity = () => { lastActivity = Date.now(); };

    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click']
        .forEach(evt => document.addEventListener(evt, resetActivity, { passive: true }));

    // Coming back to the tab is still a useful, unambiguous "I'm here" signal
    // worth reporting immediately — it's only leaving/hiding the tab that no
    // longer forces a status change, since that alone doesn't mean idle.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            resetActivity();
            currentStatus = 'Online';
            sendHeartbeat();
        }
    });

    const computeStatus = () => {
        return (Date.now() - lastActivity >= IDLE_THRESHOLD_MS) ? 'Away' : 'Online';
    };

    // ── Heartbeat ─────────────────────────────────────────────────────────────

    const sendHeartbeat = async () => {
        currentStatus = computeStatus();

        try {
            await fetch(HEARTBEAT_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ status: currentStatus }),
            });
        } catch (e) {
            // Silently fail — server will expire the status naturally
            console.warn('[Presence] Heartbeat failed:', e);
        }
    };

    const startHeartbeat = () => {
        sendHeartbeat(); // Send immediately on load
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL_MS);
    };

    // ── Badge Polling ─────────────────────────────────────────────────────────

    const BADGE_CLASSES = {
        Online:    'bg-green-200 text-green-700',
        Away:      'bg-yellow-200 text-yellow-700',
        Offline:   'bg-gray-200 text-gray-500',
        Inactive:  'bg-red-200 text-red-600',
        'On Break': 'bg-orange-100 text-orange-700',
        // fallback — Active is the old manual-only state label
        Active:    'bg-green-200 text-green-700',
    };

    const updateBadges = async () => {
        try {
            const res  = await fetch(STATUSES_URL, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;

            const statuses = await res.json(); // { "42": "Online", "7": "Away", ... }

            document.querySelectorAll('[data-presence-badge]').forEach(badge => {
                const employeeId = badge.getAttribute('data-presence-badge');
                const newStatus  = statuses[employeeId];

                if (!newStatus) return;

                // Only re-render if changed (avoids unnecessary DOM flicker)
                if (badge.getAttribute('data-current-status') === newStatus) return;

                badge.setAttribute('data-current-status', newStatus);

                // Clear old color classes
                Object.values(BADGE_CLASSES).forEach(cls =>
                    cls.split(' ').forEach(c => badge.classList.remove(c))
                );

                // Apply new color classes
                const classes = BADGE_CLASSES[newStatus] || BADGE_CLASSES['Offline'];
                classes.split(' ').forEach(c => badge.classList.add(c));

                badge.textContent = newStatus;
            });

        } catch (e) {
            console.warn('[Presence] Badge poll failed:', e);
        }
    };

    const startPolling = () => {
        pollTimer = setInterval(updateBadges, BADGE_POLL_INTERVAL_MS);
    };

    // ── Mark Offline on Page Unload ───────────────────────────────────────────
    // sendBeacon is fire-and-forget — works even as page closes
    window.addEventListener('beforeunload', () => {
        const token = getCsrfToken();
        const blob  = new Blob(
            [JSON.stringify({ status: 'Offline' })],
            { type: 'application/json' }
        );
        // We repurpose the heartbeat endpoint — the server accepts 'Offline' too
        // by treating it as a signal to zero out (handled below in PresenceModel)
        navigator.sendBeacon(
            HEARTBEAT_URL + '?_token=' + encodeURIComponent(token),
            blob
        );
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    const init = async () => {
        await sendHeartbeat();
        startHeartbeat();
        // Only poll badges if we're on a page that has presence badges
        if (document.querySelector('[data-presence-badge]')) {
            await updateBadges(); // Immediate first fetch
            startPolling();
        }
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => Presence.init());