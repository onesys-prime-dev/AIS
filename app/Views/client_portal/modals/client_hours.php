<?php
/**
 * Client Hours: hours of logged tasks per client, with the detail behind every figure.
 * Everyone sees their own; reviewers (payroll / attendance reviewers, Team Heads) can also pick an employee
 * or look across everyone they review. Expects $isReviewer.
 */
$isReviewer = $isReviewer ?? false;
?>
<style>
    .ch-bar { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin-bottom:14px; }
    .ch-bar label { font-size:12px; color:#6b7280; display:block; }
    .ch-field { display:block; border:1px solid #d1d5db; border-radius:8px; padding:7px 10px; font-size:13px; background:#fff; color:#111827; min-width:150px; }
    .ch-btn { border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:8px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; }
    .ch-btn:hover { background:#f3f4f6; }
    .ch-btn-primary { background:#1f2937; border-color:#1f2937; color:#fff; }
    .ch-btn-primary:hover { background:#111827; }
    .ch-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin-bottom:14px; }
    .ch-card { border:1px solid #e5e7eb; border-radius:12px; padding:10px 14px; background:#fff; }
    .ch-card .ch-k { font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
    .ch-card .ch-v { font-size:22px; font-weight:700; color:#111827; margin-top:2px; }
    .ch-card .ch-s { font-size:11px; color:#9ca3af; }
    .ch-table { width:100%; border-collapse:collapse; font-size:13px; }
    .ch-table th { text-align:left; background:#DCDCDC; color:#737373; font-size:11px; text-transform:uppercase; letter-spacing:.04em; padding:9px 12px; position:sticky; top:0; }
    .ch-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .ch-num, .ch-table th.ch-num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .ch-row-client { cursor:pointer; background:#fff; }
    .ch-row-client:hover { background:#f9fafb; }
    .ch-row-emp { background:#fafafa; cursor:pointer; }
    .ch-row-emp td:first-child { padding-left:34px; }
    .ch-row-task td { background:#fcfcfc; color:#4b5563; font-size:12px; }
    .ch-row-task td:first-child { padding-left:60px; }
    .ch-chev { display:inline-block; width:14px; color:#9ca3af; transition:transform .12s; }
    .ch-open > td .ch-chev { transform:rotate(90deg); }
    .ch-bar-track { height:6px; background:#eef0f3; border-radius:4px; min-width:90px; }
    .ch-bar-fill { height:6px; background:#4b5563; border-radius:4px; }
    .ch-muted { color:#9ca3af; }
    .ch-warn { color:#b45309; }
    .ch-empty { padding:28px; text-align:center; color:#9ca3af; font-size:13px; }
</style>

<div id="chRoot">
    <div class="ch-bar">
        <label>From<input type="date" id="chStart" class="ch-field"></label>
        <label>To<input type="date" id="chEnd" class="ch-field"></label>
        <?php if ($isReviewer): ?>
        <label>Employee
            <select id="chEmployee" class="ch-field" style="min-width:210px;"><option value="">All employees</option></select>
        </label>
        <?php endif; ?>
        <label>Client
            <select id="chClient" class="ch-field"><option value="">All clients</option></select>
        </label>
        <button type="button" id="chShow" class="ch-btn ch-btn-primary">Show</button>
        <button type="button" id="chMonth" class="ch-btn">This month</button>
        <button type="button" id="chLast30" class="ch-btn">Last 30 days</button>
        <button type="button" id="chCsv" class="ch-btn" style="margin-left:auto;" disabled>Download CSV</button>
    </div>

    <div id="chError" class="ch-warn" style="display:none;font-size:13px;margin-bottom:10px;"></div>
    <div class="ch-cards" id="chCards"></div>
    <div id="chBody" style="max-height:52vh;overflow:auto;border:1px solid #e5e7eb;border-radius:12px;">
        <div class="ch-empty">Pick a date range and press Show.</div>
    </div>
    <p class="ch-muted" style="font-size:11px;margin-top:8px;">Hours are task length minus any break taken inside the task. “Unlogged” is time clocked in that was never logged to a client.</p>
</div>

<script>
(function () {
    const IS_REVIEWER = <?= json_encode((bool) $isReviewer) ?>;
    const URL_SELF = <?= json_encode(site_url('client_portal/timesheet/client-hours')) ?>;
    const URL_ADMIN = <?= json_encode(site_url('client_portal/timesheet/employee/client-hours')) ?>;
    const EMPLOYEES_URL = <?= json_encode(site_url('client_portal/timesheet/review-employees')) ?>;

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = (n) => String(n).padStart(2, '0');
    const iso = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const dec = (h) => Number(h).toFixed(2);
    const hm = (h) => { const m = Math.round(Number(h) * 60); return Math.floor(m / 60) + ':' + pad(m % 60); };
    const t12 = (t) => { const [H, M] = String(t).split(':').map(Number); return ((H % 12) || 12) + ':' + pad(M) + ' ' + (H >= 12 ? 'PM' : 'AM'); };
    const dayLabel = (d) => new Date(d + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });

    let data = null;
    let inited = false;

    function setRange(start, end) { $('chStart').value = iso(start); $('chEnd').value = iso(end); }
    function monthToDate() { const n = new Date(); setRange(new Date(n.getFullYear(), n.getMonth(), 1), n); }
    function last30() { const n = new Date(); const s = new Date(); s.setDate(n.getDate() - 29); setRange(s, n); }

    function showError(msg) { const e = $('chError'); e.textContent = msg || ''; e.style.display = msg ? 'block' : 'none'; }

    async function loadEmployees() {
        if (!IS_REVIEWER) return;
        try {
            const rows = await (await fetch(EMPLOYEES_URL)).json();
            const sel = $('chEmployee');
            (Array.isArray(rows) ? rows : []).forEach((r) => {
                const o = document.createElement('option');
                o.value = r.id; o.textContent = r.label;
                sel.appendChild(o);
            });
        } catch (e) { /* the filter just stays on "All employees" */ }
    }

    function fillClients(options, keep) {
        const sel = $('chClient');
        sel.innerHTML = '<option value="">All clients</option>' + options.map((c) => '<option value="' + c.id + '">' + esc(c.name) + '</option>').join('');
        sel.value = keep || '';
    }

    function cards(t) {
        const card = (k, v, s) => '<div class="ch-card"><div class="ch-k">' + k + '</div><div class="ch-v">' + v + '</div><div class="ch-s">' + (s || '&nbsp;') + '</div></div>';
        $('chCards').innerHTML =
            card('Logged to clients', dec(t.logged_hours) + ' h', hm(t.logged_hours) + ' · ' + t.tasks + ' task' + (t.tasks === 1 ? '' : 's')) +
            card('Clocked (worked)', dec(t.worked_hours) + ' h', hm(t.worked_hours)) +
            card('Unlogged', dec(t.unlogged_hours) + ' h', t.worked_hours > 0 ? Math.round(t.unlogged_hours / t.worked_hours * 100) + '% of worked time' : '') +
            card('Clients', t.clients, t.employees + ' employee' + (t.employees === 1 ? '' : 's'));
    }

    function render() {
        cards(data.totals);
        $('chCsv').disabled = !data.clients.length;
        if (!data.clients.length) {
            $('chBody').innerHTML = '<div class="ch-empty">No tasks were logged in this range.</div>';
            return;
        }
        let html = '<table class="ch-table"><thead><tr><th>Client</th><th class="ch-num">Employees</th><th class="ch-num">Tasks</th><th class="ch-num">Hours</th><th class="ch-num">h:mm</th><th style="width:20%">Share</th></tr></thead><tbody>';
        data.clients.forEach((c, ci) => {
            html += '<tr class="ch-row-client" data-c="' + ci + '"><td><span class="ch-chev">&#9656;</span><strong>' + esc(c.name) + '</strong></td>' +
                '<td class="ch-num">' + c.employees.length + '</td><td class="ch-num">' + c.tasks + '</td><td class="ch-num"><strong>' + dec(c.hours) + '</strong></td><td class="ch-num">' + hm(c.hours) + '</td>' +
                '<td><div style="display:flex;align-items:center;gap:8px;"><div class="ch-bar-track" style="flex:1"><div class="ch-bar-fill" style="width:' + c.share + '%"></div></div><span class="ch-muted" style="font-size:11px;width:38px;text-align:right">' + c.share + '%</span></div></td></tr>';
            c.employees.forEach((e, ei) => {
                html += '<tr class="ch-row-emp" data-p="' + ci + '" data-e="' + ei + '" hidden><td><span class="ch-chev">&#9656;</span>' + esc(e.name) + '</td><td></td><td class="ch-num">' + e.tasks + '</td><td class="ch-num">' + dec(e.hours) + '</td><td class="ch-num">' + hm(e.hours) + '</td><td></td></tr>';
                e.days.forEach((d) => {
                    d.tasks.forEach((t, ti) => {
                        html += '<tr class="ch-row-task" data-pp="' + ci + '-' + ei + '" hidden><td>' + (ti === 0 ? '<strong>' + esc(dayLabel(d.date)) + '</strong> &nbsp;' : '&nbsp;') + t12(t.start) + ' – ' + t12(t.end) + '</td>' +
                            '<td colspan="2">' + esc(t.description) + (t.break_excluded_minutes ? ' <span class="ch-warn">(' + t.break_excluded_minutes + ' min break excluded)</span>' : '') + '</td>' +
                            '<td class="ch-num">' + dec(t.hours) + '</td><td class="ch-num">' + hm(t.hours) + '</td><td></td></tr>';
                    });
                });
            });
        });
        $('chBody').innerHTML = html + '</tbody></table>';
    }

    // Click a client to open its employees, an employee to open their tasks.
    $('chBody').addEventListener('click', (ev) => {
        const row = ev.target.closest('tr');
        if (!row) return;
        const body = $('chBody');
        if (row.classList.contains('ch-row-client')) {
            const open = row.classList.toggle('ch-open');
            body.querySelectorAll('tr[data-p="' + row.dataset.c + '"]').forEach((r) => {
                r.hidden = !open;
                if (!open) { r.classList.remove('ch-open'); body.querySelectorAll('tr[data-pp="' + r.dataset.p + '-' + r.dataset.e + '"]').forEach((t) => { t.hidden = true; }); }
            });
        } else if (row.classList.contains('ch-row-emp')) {
            const open = row.classList.toggle('ch-open');
            body.querySelectorAll('tr[data-pp="' + row.dataset.p + '-' + row.dataset.e + '"]').forEach((t) => { t.hidden = !open; });
        }
    });

    async function load() {
        showError('');
        const params = new URLSearchParams({ start: $('chStart').value, end: $('chEnd').value });
        if ($('chClient').value) params.set('client_id', $('chClient').value);
        const emp = IS_REVIEWER ? $('chEmployee').value : '';
        if (emp) params.set('employee_id', emp);
        $('chShow').disabled = true;
        try {
            const res = await fetch((IS_REVIEWER ? URL_ADMIN : URL_SELF) + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            if (!res.ok || json.error) { showError(json.error || 'Unable to load client hours.'); return; }
            const keep = $('chClient').value;
            data = json;
            fillClients(json.client_options || [], keep);
            render();
        } catch (e) {
            showError('Network error. Please try again.');
        } finally {
            $('chShow').disabled = false;
        }
    }

    function csvCell(v) {
        let s = String(v == null ? '' : v);
        if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;     // never let a description run as a spreadsheet formula
        return /[",\n\r]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }
    function downloadCsv() {
        if (!data || !data.clients.length) return;
        const rows = [['Client', 'Employee', 'Date', 'Start', 'End', 'Hours', 'Break minutes excluded', 'Task']];
        data.clients.forEach((c) => c.employees.forEach((e) => e.days.forEach((d) => d.tasks.forEach((t) => {
            rows.push([c.name, e.name, d.date, t.start, t.end, dec(t.hours), t.break_excluded_minutes, t.description]);
        }))));
        const blob = new Blob(['﻿' + rows.map((r) => r.map(csvCell).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'client-hours_' + data.range.start + '_to_' + data.range.end + '.csv';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    }

    $('chShow').addEventListener('click', load);
    $('chMonth').addEventListener('click', () => { monthToDate(); load(); });
    $('chLast30').addEventListener('click', () => { last30(); load(); });
    $('chCsv').addEventListener('click', downloadCsv);

    // Called when the modal is opened (first time sets defaults and loads).
    window.openClientHours = function () {
        if (typeof openModalById === 'function') openModalById('viewClientHoursModal');
        if (inited) return;
        inited = true;
        monthToDate();
        loadEmployees();
        load();
    };
})();
</script>
