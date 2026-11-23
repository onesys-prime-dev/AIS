/**
 * Attendance reminders on screen: a pop-up (with a sound and a flashing tab title) for whatever the server says is due
 * right now - clock in, clock out, break windows, break ending.
 *
 * The server builds the list live from the real attendance record, so a reminder disappears by itself the moment the
 * employee clocks in / out or ends the break; this script only asks, shows and lets the person dismiss one.
 *
 * Config (set by the layout): window.AIS_REMINDERS = { pendingUrl, dismissUrl, intervalMs }.
 * window.AISReminders.refresh() asks again right away.
 */
(function () {
    'use strict';

    const cfg = window.AIS_REMINDERS;
    if (!cfg || !cfg.pendingUrl) return;

    const INTERVAL = Math.max(15000, Number(cfg.intervalMs) || 30000);
    const baseTitle = document.title;
    const shown = new Map();       // key -> item currently in the modal
    const notified = new Set();    // keys already announced with a sound / system notification
    let sound = true;
    let dashboardUrl = '';
    let overlay = null;
    let flashTimer = null;
    let audio = null;
    let audioUnlocked = false;
    let timer = null;
    let busy = false;
    let snoozeTimer = null;

    // "Open the dashboard" / "Close" hides the pop-up for a few minutes (this tab only), so it never sits on top of the
    // Clock In button; the tab title keeps flashing and the reminder comes back if it is still due afterwards.
    const SNOOZE_MS = 5 * 60 * 1000;
    const SNOOZE_KEY = 'ar_snooze_until';
    const snoozeLeft = () => { try { return Math.max(0, Number(sessionStorage.getItem(SNOOZE_KEY) || 0) - Date.now()); } catch (e) { return 0; } };
    const snooze = () => { try { sessionStorage.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_MS)); } catch (e) { /* no storage: it simply reappears */ } };
    const samePath = (url) => { try { return new URL(url, location.href).pathname === location.pathname; } catch (e) { return false; } };

    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const csrf = () => { const m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; };

    /* ---------------------------------------------------------------- styles */
    function injectStyle() {
        if (document.getElementById('ar-style')) return;
        const el = document.createElement('style');
        el.id = 'ar-style';
        el.textContent = `
.ar-overlay{position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:16px}
.ar-overlay.ar-open{display:flex}
.ar-box{background:#fff;color:#111827;width:100%;max-width:440px;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.25);overflow:hidden;font-family:inherit}
.ar-head{display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid #e5e7eb}
.ar-head svg{flex:none;color:#374151}
.ar-head h3{margin:0;font-size:16px;font-weight:700}
.ar-list{max-height:55vh;overflow-y:auto}
.ar-item{padding:14px 20px;border-bottom:1px solid #f1f5f9;border-left:4px solid #9ca3af}
.ar-item.ar-warning{border-left-color:#d97706}
.ar-item.ar-urgent{border-left-color:#dc2626}
.ar-item h4{margin:0 0 4px;font-size:14px;font-weight:700}
.ar-item p{margin:0 0 10px;font-size:13px;color:#4b5563;line-height:1.5}
.ar-btn{border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;padding:6px 12px;font:inherit;font-size:12px;font-weight:600;cursor:pointer}
.ar-btn:hover{background:#f3f4f6}
.ar-foot{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;background:#f9fafb}
.ar-primary{border:0;background:#1f2937;color:#fff;border-radius:8px;padding:9px 16px;font:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.ar-primary:hover{background:#111827}`;
        document.head.appendChild(el);
    }

    /* ----------------------------------------------------------------- sound */
    // Browsers only allow sound after the person has interacted with the page once, so it is unlocked on the first click/key.
    function unlockAudio() {
        if (audioUnlocked) return;
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            audio = audio || new Ctx();
            if (audio.state === 'suspended') audio.resume();
            audioUnlocked = true;
        } catch (e) { /* no sound; the title still flashes */ }
    }
    ['click', 'keydown', 'touchstart'].forEach((ev) => window.addEventListener(ev, unlockAudio, { once: true, passive: true }));

    function beep() {
        if (!sound || !audio || audio.state !== 'running') return;
        try {
            [0, 0.28].forEach((offset) => {
                const osc = audio.createOscillator();
                const gain = audio.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.0001, audio.currentTime + offset);
                gain.gain.exponentialRampToValueAtTime(0.25, audio.currentTime + offset + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + offset + 0.22);
                osc.connect(gain);
                gain.connect(audio.destination);
                osc.start(audio.currentTime + offset);
                osc.stop(audio.currentTime + offset + 0.25);
            });
        } catch (e) { /* ignore */ }
    }

    /* ------------------------------------------------------------ title flash */
    function flashTitle(on, text) {
        if (flashTimer) { clearInterval(flashTimer); flashTimer = null; }
        if (!on) { document.title = baseTitle; return; }
        let alt = false;
        document.title = text;
        flashTimer = setInterval(() => { alt = !alt; document.title = alt ? baseTitle : text; }, 1000);
    }

    /* ----------------------------------------------------------------- modal */
    function ensureOverlay() {
        if (overlay) return overlay;
        injectStyle();
        overlay = document.createElement('div');
        overlay.className = 'ar-overlay';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Attendance reminder');
        overlay.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-ar-dismiss]');
            if (btn) { dismiss(btn.dataset.arDismiss); return; }
            if (e.target.closest('[data-ar-go]')) {
                snooze();
                if (samePath(dashboardUrl)) { e.preventDefault(); render(); }   // already on the dashboard: just get out of the way
            }
        });
        document.body.appendChild(overlay);
        return overlay;
    }

    function render() {
        const items = Array.from(shown.values());
        if (!items.length) {
            if (overlay) overlay.classList.remove('ar-open');
            flashTitle(false);
            return;
        }
        const left = snoozeLeft();
        if (left > 0) {
            if (overlay) overlay.classList.remove('ar-open');
            flashTitle(true, '⏰ ' + items[0].title);
            clearTimeout(snoozeTimer);
            snoozeTimer = setTimeout(render, left + 250);
            return;
        }
        const o = ensureOverlay();
        o.innerHTML =
            '<div class="ar-box">' +
                '<div class="ar-head"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg><h3>' + (items.length === 1 ? 'Attendance reminder' : items.length + ' attendance reminders') + '</h3></div>' +
                '<div class="ar-list">' + items.map((i) =>
                    '<div class="ar-item ar-' + esc(i.severity) + '"><h4>' + esc(i.title) + '</h4><p>' + esc(i.body) + '</p>' +
                    '<button type="button" class="ar-btn" data-ar-dismiss="' + esc(i.key) + '">Dismiss</button></div>').join('') + '</div>' +
                '<div class="ar-foot"><a class="ar-primary" data-ar-go="1" href="' + esc(dashboardUrl) + '">' + (samePath(dashboardUrl) ? 'Close' : 'Open the dashboard') + '</a></div>' +
            '</div>';
        o.classList.add('ar-open');
        flashTitle(true, '⏰ ' + items[0].title);
    }

    /** A system notification when the tab is in the background and the person allowed notifications. */
    function systemNotify(item) {
        try {
            if (document.visibilityState === 'visible') return;
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            const n = new Notification(item.title, { body: item.body, tag: item.key });
            n.onclick = () => { window.focus(); n.close(); };
        } catch (e) { /* ignore */ }
    }

    /* ------------------------------------------------------------- server I/O */
    async function refresh() {
        if (busy) return;
        busy = true;
        try {
            const res = await fetch(cfg.pendingUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            sound = data.sound !== false;
            dashboardUrl = data.dashboard_url || dashboardUrl;

            const next = new Map((data.items || []).map((i) => [i.key, i]));
            // Whatever is no longer due (they clocked in, ended the break...) leaves the pop-up on its own.
            Array.from(shown.keys()).forEach((k) => { if (!next.has(k)) { shown.delete(k); try { closeSystemNotification(k); } catch (e) { /* ignore */ } } });
            let fresh = false;
            next.forEach((item, key) => {
                if (!shown.has(key)) shown.set(key, item);
                if (!notified.has(key)) { notified.add(key); fresh = true; systemNotify(item); }
            });
            if (fresh) beep();
            render();
        } catch (e) {
            /* offline or signed out: try again next time */
        } finally {
            busy = false;
        }
    }

    // Closes a system notification for a reminder that is no longer due (page-level; the push phase also does this in the service worker).
    function closeSystemNotification(key) {
        if (!('serviceWorker' in navigator) || !navigator.serviceWorker.getRegistration) return;
        navigator.serviceWorker.getRegistration().then((reg) => {
            if (!reg || !reg.getNotifications) return;
            reg.getNotifications({ tag: key }).then((list) => list.forEach((n) => n.close()));
        }).catch(() => {});
    }

    async function dismiss(key) {
        shown.delete(key);
        render();
        try {
            await fetch(cfg.dismissUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: new URLSearchParams({ key: key }).toString(),
            });
        } catch (e) { /* it simply shows again next time */ }
    }

    // On a shared computer the next person to sign in must not keep getting the previous person's push reminders: if this
    // browser's push subscription is registered to someone else (or to nobody), the server forgets it and the browser drops it.
    async function checkPushOwner() {
        try {
            if (!cfg.pushCheckUrl || !('serviceWorker' in navigator) || !('PushManager' in window)) return;
            const reg = await navigator.serviceWorker.getRegistration(cfg.swScope || undefined);
            const sub = reg ? await reg.pushManager.getSubscription() : null;
            if (!sub) return;
            const res = await fetch(cfg.pushCheckUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: new URLSearchParams({ endpoint: sub.endpoint }).toString(),
            });
            const data = await res.json();
            if (data && data.success && data.drop) await sub.unsubscribe();
        } catch (e) { /* not important */ }
    }

    function start() {
        if (timer) return;
        refresh();
        checkPushOwner();
        timer = setInterval(() => { if (document.visibilityState === 'visible') refresh(); }, INTERVAL);
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
        window.addEventListener('focus', refresh);
        window.addEventListener('pageshow', refresh);
    }

    window.AISReminders = { refresh: refresh };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
