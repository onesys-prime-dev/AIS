/**
 * Access Control -> Reminders: the global settings for the attendance reminders, a health line (can email go out, is
 * the scheduler running) and the report of employees the reminders cannot fully reach.
 *
 * Loaded lazily the first time the tab is opened. Talks to access-control/reminders/{settings,save,report}.
 */
(function () {
    'use strict';

    const root = document.getElementById('remindersAdmin');
    if (!root) return;
    const urls = {
        settings: root.dataset.settingsUrl,
        save: root.dataset.saveUrl,
        report: root.dataset.reportUrl,
    };

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const csrf = () => { const m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; };
    const toast = (msg, ok) => { if (window.showToast) window.showToast(msg, ok ? 'success' : 'error'); };

    let defaults = null;
    let loaded = false;
    let saved = '';

    const BOOLS = { rmEnabled: ['enabled'], rmCiOn: ['clock_in', 'enabled'], rmCoOn: ['clock_out', 'enabled'], rmBrOn: ['break', 'enabled'], rmEscOn: ['escalation', 'enabled'], rmPopupOn: ['popup', 'enabled'], rmEmailOn: ['email', 'enabled'], rmEmailBefore: ['email', 'before'] };
    const TEXTS = { rmCiSteps: ['clock_in', 'steps'], rmCoSteps: ['clock_out', 'steps'], rmCiStop: ['clock_in', 'stop_after'], rmCoStop: ['clock_out', 'stop_after'], rmCiEsc: ['clock_in', 'escalate_step'], rmBrClose: ['break', 'window_close_before'], rmBrEnd: ['break', 'end_before'] };

    const dig = (o, path) => path.reduce((a, k) => (a == null ? a : a[k]), o);

    function fill(cfg) {
        Object.keys(BOOLS).forEach((id) => { $(id).checked = !!dig(cfg, BOOLS[id]); });
        Object.keys(TEXTS).forEach((id) => {
            const v = dig(cfg, TEXTS[id]);
            $(id).value = Array.isArray(v) ? v.join(', ') : v;
        });
        applyDisabled();
        saved = signature();
        updateSave();
    }

    function signature() {
        return JSON.stringify([Object.keys(BOOLS).map((id) => $(id).checked), Object.keys(TEXTS).map((id) => $(id).value.trim())]);
    }

    // Everything below the master switch greys out when it is off, so it is obvious nothing will be sent.
    function applyDisabled() {
        const on = $('rmEnabled').checked;
        root.querySelectorAll('[data-rm-dependent]').forEach((el) => { el.style.opacity = on ? '1' : '0.5'; el.style.pointerEvents = on ? '' : 'none'; });
    }

    function updateSave() {
        const changed = signature() !== saved;
        $('rmSave').disabled = !changed;
        $('rmNote').innerHTML = changed ? '<span class="acr-dirty"></span>Changes not saved yet' : '';
    }

    function payload() {
        const p = new URLSearchParams();
        const map = { rmEnabled: 'enabled', rmCiOn: 'clock_in_enabled', rmCoOn: 'clock_out_enabled', rmBrOn: 'break_enabled', rmEscOn: 'escalation_enabled', rmPopupOn: 'popup_enabled', rmEmailOn: 'email_enabled', rmEmailBefore: 'email_before' };
        Object.keys(map).forEach((id) => p.set(map[id], $(id).checked ? '1' : '0'));
        const txt = { rmCiSteps: 'clock_in_steps', rmCoSteps: 'clock_out_steps', rmCiStop: 'clock_in_stop_after', rmCoStop: 'clock_out_stop_after', rmCiEsc: 'clock_in_escalate_step', rmBrClose: 'break_close_before', rmBrEnd: 'break_end_before' };
        Object.keys(txt).forEach((id) => p.set(txt[id], $(id).value.trim()));
        return p;
    }

    function health(data) {
        const items = [];
        items.push(data.can_send_mail
            ? '<span style="color:#15803d;">&#10003;</span> Email sending is set up.'
            : '<span style="color:#b45309;">&#9888;</span> No sending address is configured yet (email.fromEmail). Pop-ups work, but emails cannot be sent until a server administrator sets it.');
        items.push(data.push_available
            ? '<span style="color:#15803d;">&#10003;</span> Browser push is set up (' + (data.push_devices || 0) + ' device' + (data.push_devices === 1 ? '' : 's') + ' registered).'
            : '<span style="color:#6b7280;">&#9432;</span> Browser push is not set up (it needs VAPID keys in .env). Pop-ups and email still work.');
        if (data.last_run_ago_minutes == null) {
            items.push('<span style="color:#b45309;">&#9888;</span> The scheduled job has not run yet. Reminders and emails are only sent while it runs every few minutes.');
        } else if (data.last_run_ago_minutes > 15) {
            items.push('<span style="color:#b45309;">&#9888;</span> The scheduled job last ran ' + data.last_run_ago_minutes + ' minutes ago (' + esc(data.last_run) + '). It should run every 5 minutes.');
        } else {
            items.push('<span style="color:#15803d;">&#10003;</span> The scheduled job ran ' + (data.last_run_ago_minutes <= 0 ? 'just now' : data.last_run_ago_minutes + ' minute' + (data.last_run_ago_minutes === 1 ? '' : 's') + ' ago') + '.');
        }
        $('rmHealth').innerHTML = items.map((h) => '<div style="padding:3px 0;">' + h + '</div>').join('');
    }

    async function loadSettings(fillForm) {
        try {
            const res = await fetch(urls.settings, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!res.ok || !data.config) throw new Error(data.message || 'failed');
            defaults = data.defaults;
            if (fillForm) fill(data.config);   // later opens only refresh the health line, never overwrite unsaved edits
            health(data);
        } catch (e) {
            $('rmHealth').textContent = 'Could not load the reminder settings.';
        }
    }

    async function loadReport() {
        const box = $('rmReport');
        box.innerHTML = '<p class="text-xs text-gray-400">Loading...</p>';
        try {
            const res = await fetch(urls.report, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!res.ok || !Array.isArray(data.rows)) throw new Error('failed');
            $('rmReportSummary').textContent = data.rows.length + ' of ' + data.total + ' employees have something to check.';
            box.innerHTML = data.rows.length
                ? '<table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr style="text-align:left;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.04em;"><th style="padding:6px 8px;">Employee</th><th style="padding:6px 8px;">Department</th><th style="padding:6px 8px;">To check</th></tr></thead><tbody>' +
                    data.rows.map((r) => '<tr style="border-top:1px solid #f1f5f9;"><td style="padding:8px;font-weight:600;">' + esc(r.name) + '</td><td style="padding:8px;color:#6b7280;">' + esc(r.department) + '</td><td style="padding:8px;">' +
                        r.issues.map((i) => '<span style="display:inline-block;margin:0 6px 4px 0;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;background:' + (i === 'Opted out' ? '#e5e7eb' : '#fef3c7') + ';color:' + (i === 'Opted out' ? '#374151' : '#92400e') + ';">' + esc(i) + '</span>').join('') +
                        '</td></tr>').join('') + '</tbody></table>'
                : '<p class="text-xs text-gray-500">Every employee with a schedule can be reached.</p>';
        } catch (e) {
            box.innerHTML = '<p class="text-xs text-gray-400">Could not load the report.</p>';
        }
    }

    async function save() {
        const btn = $('rmSave');
        btn.disabled = true;
        try {
            const res = await fetch(urls.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: payload().toString(),
            });
            const data = await res.json();
            toast(data.message || (data.success ? 'Saved.' : 'Could not save.'), !!data.success);
            if (data.success && data.config) fill(data.config);
        } catch (e) {
            toast('Something went wrong.', false);
        }
        updateSave();
    }

    function init() {
        if (loaded) return;
        loaded = true;
        root.addEventListener('input', () => { applyDisabled(); updateSave(); });
        root.addEventListener('change', () => { applyDisabled(); updateSave(); });
        $('rmSave').addEventListener('click', save);
        $('rmReset').addEventListener('click', () => { if (defaults) { fill(defaults); saved = ''; updateSave(); } });
        loadSettings(true);
        loadReport();
    }

    document.addEventListener('ac:tab', (e) => {
        if (e.detail !== 'reminders') return;
        const first = !loaded;
        init();
        if (!first) loadSettings(false);
    });
})();
