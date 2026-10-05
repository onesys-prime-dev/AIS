<?php
// Fetch company logo from database (mirrors payroll_management.php) so the
// stored-payslip viewer's document preview looks identical to Generate Payslip.
$companyBranding = model(\App\Models\Client\CompanyBrandingModel::class)
    ->where('company_id', 1)
    ->first();
$companyLogoUrl = $companyBranding && !empty($companyBranding['logo_path'])
    ? base_url($companyBranding['logo_path'])
    : '';

// ── Payslip sending: shared dialogs + actions ─────────────────────────────
// No page of its own any more. Embedded once in Payroll Output (see
// payroll_register.php); the Payslip sub-tab's cards call the functions below
// (View, Activity, Send Now / Resend, Schedule, Cancel, Mark as Paid, release
// mode). Card data comes from getRegisterRows(); window.PAYSLIP_DATA (below)
// feeds the viewer.
$payslips    = $payslips ?? [];
$releaseMode = ($releaseMode ?? 'manual') === 'automatic' ? 'automatic' : 'manual';
$canRelease  = (bool) ($canRelease ?? false);
$today       = date('Y-m-d');
$btn = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition';
?>
<!-- Schedule dialog -->
<div id="relScheduleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:22px; width:min(420px, calc(100vw - 32px)); border-radius:14px; box-shadow:0 8px 30px rgba(0,0,0,.25);">
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin-bottom:6px;">Schedule send</h3>
        <p id="relScheduleInfo" style="font-size:13px;color:#4b5563;margin-bottom:12px;"></p>
        <label style="font-size:12px;font-weight:600;color:#374151;">Send on</label>
        <input type="date" id="relScheduleDate" min="<?= $today ?>" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;margin-top:4px;font-size:14px;">
        <p id="relScheduleHint" style="font-size:12px;color:#6b7280;margin-top:6px;"></p>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
            <button type="button" onclick="closeScheduleDialog()" class="<?= $btn ?> bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300">Cancel</button>
            <button type="button" id="relScheduleConfirm" onclick="confirmSchedule()" class="<?= $btn ?> bg-blue-600 hover:bg-blue-700 text-white">Schedule</button>
        </div>
    </div>
</div>

<!-- Confirm dialog — replaces window.confirm() for Send/Resend prompts -->
<div id="relConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:22px; width:min(420px, calc(100vw - 32px)); border-radius:14px; box-shadow:0 8px 30px rgba(0,0,0,.25);">
        <h3 id="relConfirmTitle" style="font-size:16px;font-weight:700;color:#111827;margin-bottom:6px;">Confirm</h3>
        <p id="relConfirmMessage" style="font-size:13px;color:#4b5563;line-height:1.5;"></p>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
            <button type="button" id="relConfirmCancel" class="<?= $btn ?> bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300">Cancel</button>
            <button type="button" id="relConfirmOk" class="<?= $btn ?> bg-[#7b5cff] hover:bg-[#6a4de0] text-white">Send</button>
        </div>
    </div>
</div>

<!-- Payslip Viewer Modal -->
<div id="payslipViewerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; overflow:auto;">
    <div style="background:#fff; margin:2% auto; padding:24px; max-width:860px; border-radius:16px; box-shadow:0 8px 40px rgba(0,0,0,.35); position:relative; max-height:90vh; overflow-y:auto;">
        <button onclick="document.getElementById('payslipViewerModal').style.display='none'"
            style="position:absolute; top:12px; right:16px; font-size:24px; background:none; border:none; cursor:pointer; color:#666;">&times;</button>
        <div id="payslipViewerContent" style="min-height:200px;">
            <p style="text-align:center; color:#888; padding:40px 0;">Select a payslip to view.</p>
        </div>
        <div style="text-align:center; margin-top:16px; padding-top:16px; border-top:1px solid #e5e7eb; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
            <button id="payslipViewerSendBtn"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition"
                onclick="sendFromViewer()">Send to Employee Email</button>
            <a id="payslipViewerPdfLink" href="#"
                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition">PDF</a>
            <button id="payslipViewerActivityBtn" type="button"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-6 py-3 rounded-lg text-sm font-semibold transition">Activity</button>
            <button onclick="document.getElementById('payslipViewerModal').style.display='none'"
                class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg text-sm font-semibold transition">Close</button>
        </div>
    </div>
</div>

<!-- Activity Timeline Modal — generated, every send (individual/bulk), every edit -->
<div id="payslipActivityModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; overflow:auto;">
    <div style="background:#fff; margin:4% auto; padding:24px; max-width:640px; border-radius:16px; box-shadow:0 8px 40px rgba(0,0,0,.35); position:relative; max-height:88vh; overflow-y:auto;">
        <button onclick="document.getElementById('payslipActivityModal').style.display='none'"
            style="position:absolute; top:12px; right:16px; font-size:24px; background:none; border:none; cursor:pointer; color:#666;">&times;</button>
        <h3 style="font-size:16px;font-weight:700;color:#374151;margin-bottom:16px;">Payslip Activity</h3>
        <div id="payslipActivityContent" style="min-height:100px;">
            <p style="text-align:center; color:#888; padding:20px 0;">Loading…</p>
        </div>
    </div>
</div>

<!-- Embed payslip data server-side so JS doesn't need to call getPayslipData (avoids permission issues) -->
<script>
(function() {
    // Embedded several levels deep (Payroll Output → this partial), so move the
    // modals to be direct children of <body> — makes them immune to any
    // ancestor's display/overflow/stacking-context quirks.
    document.addEventListener('DOMContentLoaded', function () {
        ['payslipViewerModal', 'payslipActivityModal', 'relScheduleModal', 'relConfirmModal'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) document.body.appendChild(el);
        });
    });

    // Exposed on window (not just const-scoped to this IIFE) so other scripts
    // on the page — e.g. the Register's payslip views in payroll_register.php —
    // can look up a payslip's stored data without a second round-trip.
    window.PAYSLIP_DATA = {
        <?php foreach ($payslips as $ps): ?>
        <?php
            $d = json_decode((string)($ps['generated_data'] ?? ''), true);
            if (!is_array($d)) continue;
            // JSON_HEX_TAG+AMP: escape </script> sequences.
            // No JSON_UNESCAPED_UNICODE: escapes U+2028/U+2029 (JS line terminators).
            // JSON_INVALID_UTF8_SUBSTITUTE: prevent false return on bad UTF-8.
            $encoded = json_encode($d, JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($encoded === false) continue;
        ?>
        <?= (int)($ps['id'] ?? 0) ?>: <?= $encoded ?>,
        <?php endforeach; ?>
    };

    const ACTIVITY_URL = '<?= site_url('client_portal/payroll/getPayslipActivity') ?>';
    const REL_URL = {
        send:     '<?= site_url('client_portal/payroll/release/send') ?>',
        schedule: '<?= site_url('client_portal/payroll/release/schedule') ?>',
        cancel:   '<?= site_url('client_portal/payroll/release/cancel') ?>',
        mode:     '<?= site_url('client_portal/payroll/release/mode') ?>',
        markPaid: '<?= site_url('client_portal/payroll/release/markPaid') ?>',
    };
    const REL_TODAY = '<?= $today ?>';
    const PAYSLIP_DATA_URL = '<?= site_url('client_portal/payroll/getPayslipData') ?>';
    window.RELEASE_MODE = '<?= $releaseMode ?>';
    window.CAN_RELEASE  = <?= $canRelease ? 'true' : 'false' ?>;

    function relToast(msg, type) { if (typeof showToast === 'function') showToast(msg, type); else alert(msg); }

    // The Payslip sub-tab (payroll_register.php) provides card info and a
    // refresh hook, so actions update the loaded cards in place instead of
    // reloading the page (which would lose the loaded month/period).
    function relInfo(id) { return (typeof window.getPayslipCardInfo === 'function' && window.getPayslipCardInfo(id)) || {}; }
    function relName(id) { return relInfo(id).employee_name || ('payslip #' + id); }
    function relRefresh() {
        if (typeof window.onPayslipReleaseChanged === 'function') window.onPayslipReleaseChanged();
        else location.reload();
    }
    function fmtLongDate(ymd) { return ymd ? new Date(ymd + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''; }

    async function relPost(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
            body: JSON.stringify(body)
        });
        let json = {};
        try { json = await res.json(); } catch (e) { json = { success: false, error: 'Server error ' + res.status }; }
        if (!res.ok && json.success === undefined) json.success = false;
        if (!res.ok && !json.error) json.error = json.message || ('Server error ' + res.status);
        return json;
    }

    // ── Confirm dialog (replaces window.confirm) ──
    // Returns a Promise<boolean> instead of blocking the thread like confirm()
    // did, so callers just add `await` in front of the old confirm(...) call.
    function relConfirm(message, okLabel) {
        return new Promise(function (resolve) {
            const modal = document.getElementById('relConfirmModal');
            document.getElementById('relConfirmMessage').textContent = message;
            document.getElementById('relConfirmOk').textContent = okLabel || 'Send';
            modal.style.display = 'flex';

            function settle(result) {
                modal.style.display = 'none';
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                resolve(result);
            }
            const okBtn = document.getElementById('relConfirmOk');
            const cancelBtn = document.getElementById('relConfirmCancel');
            function onOk() { settle(true); }
            function onCancel() { settle(false); }
            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
        });
    }

    // ── Send ──
    async function sendOne(id, force, method) {
        return relPost(REL_URL.send, { id, force: !!force, method: method || 'individual' });
    }

    window.releaseSendOne = async function (id, btn) {
        const isResend = relInfo(id).send_status === 'sent';
        const okLabel = isResend ? 'Resend' : 'Send';
        if (!(await relConfirm(`${okLabel} ${relName(id)}'s payslip to their email now?`, okLabel))) return;
        const orig = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
        try {
            let json = await sendOne(id, false);
            if (!json.success && json.needs_confirm) {
                if (await relConfirm(json.error + ' Send the saved figures anyway?', 'Send anyway')) json = await sendOne(id, true);
            }
            if (json.success) relToast(json.warning || `${relName(id)}'s payslip was sent.`, json.warning ? 'warning' : 'success');
            else relToast(json.error || 'Send failed.', 'error');
            relRefresh();
            return;
        } catch (e) {
            relToast('Network error — nothing was sent. ' + e.message, 'error');
        }
        if (btn) { btn.disabled = false; btn.textContent = orig; }
    };

    /** Sends several payslips one by one; onProgress(done, total) for a live counter. */
    window.releaseSendMany = async function (ids, onProgress) {
        if (!ids.length) { relToast('Nothing to send in this cutoff.', 'warning'); return; }
        if (!(await relConfirm(`Send ${ids.length} payslip(s) to the employees' email now?`, 'Send'))) return;
        let sent = 0, held = 0, failed = 0, firstErr = '';
        for (let i = 0; i < ids.length; i++) {
            if (onProgress) onProgress(i, ids.length);
            try {
                const json = await sendOne(ids[i], false, 'bulk');
                if (json.success) sent++;
                else if (json.status === 'needs_review') { held++; firstErr ||= `${relName(ids[i])}: ${json.error}`; }
                else { failed++; firstErr ||= `${relName(ids[i])}: ${json.error}`; }
            } catch (e) { failed++; firstErr ||= e.message; }
        }
        if (onProgress) onProgress(ids.length, ids.length);
        const parts = [`${sent} sent`];
        if (held) parts.push(`${held} held for review`);
        if (failed) parts.push(`${failed} failed`);
        relToast(parts.join(', ') + '.' + (firstErr ? ' First problem — ' + firstErr : ''), (held || failed) ? 'warning' : 'success');
        relRefresh();
    };

    // ── Schedule ──
    let _scheduleIds = [];
    window.openScheduleDialog = function (ids) {
        _scheduleIds = (ids || []).slice();
        if (!_scheduleIds.length) { relToast('Nothing to schedule in this cutoff.', 'warning'); return; }
        const first = relInfo(_scheduleIds[0]);
        const salaryDay = first.salary_day || '';
        const current = _scheduleIds.length === 1 ? (first.scheduled_send_date || '') : '';
        let date = current || salaryDay || REL_TODAY;
        if (date < REL_TODAY) date = REL_TODAY;
        document.getElementById('relScheduleDate').value = date;
        document.getElementById('relScheduleInfo').textContent = _scheduleIds.length === 1
            ? `${relName(_scheduleIds[0])}'s payslip will be emailed automatically on this date.`
            : `${_scheduleIds.length} payslips will be emailed automatically on this date.`;
        document.getElementById('relScheduleHint').textContent = salaryDay
            ? `Salary day for this cutoff: ${fmtLongDate(salaryDay)}${salaryDay < REL_TODAY ? ' (already passed — defaulting to today)' : ''}.`
            : 'No salary day is set for this cutoff in Payroll Settings — pick a date.';
        document.getElementById('relScheduleConfirm').disabled = false;
        document.getElementById('relScheduleModal').style.display = 'flex';
    };
    window.closeScheduleDialog = function () { document.getElementById('relScheduleModal').style.display = 'none'; };
    window.confirmSchedule = async function () {
        const date = document.getElementById('relScheduleDate').value;
        if (!date) { relToast('Pick a date.', 'warning'); return; }
        if (date < REL_TODAY) { relToast('The send date can\'t be in the past.', 'warning'); return; }
        const btn = document.getElementById('relScheduleConfirm');
        btn.disabled = true;
        try {
            const json = await relPost(REL_URL.schedule, { ids: _scheduleIds, date });
            if (!json.success) throw new Error(json.error || 'Could not schedule.');
            const skipped = (json.skipped || []).length;
            relToast(`${json.scheduled} payslip(s) scheduled for ${fmtLongDate(date)}.` + (skipped ? ` ${skipped} skipped (already sent or sending).` : ''), skipped ? 'warning' : 'success');
            closeScheduleDialog();
            relRefresh();
        } catch (e) {
            relToast(e.message, 'error');
            btn.disabled = false;
        }
    };

    window.releaseCancel = async function (ids) {
        if (!ids.length || !confirm(`Cancel the scheduled send for ${ids.length === 1 ? relName(ids[0]) : ids.length + ' payslips'}? They go back to draft.`)) return;
        const json = await relPost(REL_URL.cancel, { ids });
        if (!json.success) { relToast(json.error || 'Could not cancel.', 'error'); return; }
        relToast(`${json.cancelled} scheduled send(s) cancelled.`, 'success');
        relRefresh();
    };

    // ── Mark as paid (one-way) ──
    window.markPaid = async function (ids) {
        if (!ids.length) { relToast('Nothing left to mark as paid in this cutoff.', 'warning'); return; }
        if (!confirm(`Mark ${ids.length === 1 ? relName(ids[0]) + "'s payslip" : ids.length + ' payslips'} as paid? This can't be undone.`)) return;
        const json = await relPost(REL_URL.markPaid, { ids });
        if (!json.success) { relToast(json.error || 'Could not mark as paid.', 'error'); return; }
        relToast(`${json.marked} payslip(s) marked as paid.`, 'success');
        relRefresh();
    };

    // ── Release mode ──
    window.setReleaseMode = async function (mode) {
        if (mode === window.RELEASE_MODE) return;
        const msg = mode === 'automatic'
            ? 'Switch to Automatic?\n\nPayslips saved from now on will be scheduled for the salary day automatically. Payslips already saved won\'t change.'
            : 'Switch to Manual?\n\nPayslips saved from now on will stay as drafts until someone sends or schedules them. Payslips already scheduled won\'t change.';
        if (!confirm(msg)) return;
        const json = await relPost(REL_URL.mode, { mode });
        if (!json.success) { relToast(json.error || 'Could not change the release mode.', 'error'); return; }
        window.RELEASE_MODE = mode;
        if (typeof window.onReleaseModeChanged === 'function') window.onReleaseModeChanged(mode);
        relToast(`Release mode set to ${mode === 'automatic' ? 'Automatic' : 'Manual'}.`, 'success');
    };
    const companyLogoUrl = <?= json_encode($companyLogoUrl) ?>;

    const ACTION_ICON = { created: '&#10133;', updated: '&#9998;', deleted: '&#128465;' };

    function fmtDateTime(s) {
        if (!s) return '';
        const d = new Date(s.replace(' ', 'T'));
        return isNaN(d) ? s : d.toLocaleString('en-US', { month:'short', day:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function renderValueDiff(oldValues, newValues) {
        if (!oldValues && !newValues) return '';
        const keys = Array.from(new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})]));
        if (!keys.length) return '';
        return '<div style="margin-top:6px;font-size:12px;color:#4b5563;">' + keys.map(k => {
            const ov = oldValues && oldValues[k] !== undefined ? oldValues[k] : '—';
            const nv = newValues && newValues[k] !== undefined ? newValues[k] : '—';
            return `<div><strong>${esc(k)}:</strong> ${esc(ov)} &rarr; ${esc(nv)}</div>`;
        }).join('') + '</div>';
    }

    window.openPayslipActivity = async function(id) {
        const modal   = document.getElementById('payslipActivityModal');
        const content = document.getElementById('payslipActivityContent');
        content.innerHTML = '<p style="text-align:center; color:#888; padding:20px 0;">Loading…</p>';
        modal.style.display = 'block';

        try {
            const res  = await fetch(ACTIVITY_URL + '/' + id);
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.error || 'Could not load activity.');
            if (!json.timeline.length) {
                content.innerHTML = '<p style="text-align:center; color:#888; padding:20px 0;">No activity recorded for this payslip.</p>';
                return;
            }
            content.innerHTML = '<div style="border-left:2px solid #e5e7eb;margin-left:8px;">' +
                json.timeline.map(ev => `
                    <div style="position:relative;padding:0 0 18px 20px;">
                        <span style="position:absolute;left:-9px;top:2px;width:16px;height:16px;border-radius:50%;background:#7b5cff;color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center;">${ACTION_ICON[ev.action] || '&bull;'}</span>
                        <div style="font-size:13px;font-weight:600;color:#374151;">${esc(ev.description)}</div>
                        <div style="font-size:12px;color:#9ca3af;">${fmtDateTime(ev.created_at)} &middot; ${esc(ev.username || 'Unknown')}</div>
                        ${renderValueDiff(ev.old_values, ev.new_values)}
                    </div>
                `).join('') + '</div>';
        } catch (e) {
            content.innerHTML = `<p style="text-align:center; color:#dc2626; padding:20px 0;">${esc(e.message)}</p>`;
        }
    };

    let _viewerId = null;

    // Fetches the payslip's CURRENT saved figures (the Payslip cards refresh in
    // place, so the copy embedded at page load can be out of date); falls back
    // to that embedded copy if the request fails.
    window.openPayslipViewer = async function(id) {
        const modal   = document.getElementById('payslipViewerModal');
        const content = document.getElementById('payslipViewerContent');
        const sendBtn = document.getElementById('payslipViewerSendBtn');
        const pdfLink = document.getElementById('payslipViewerPdfLink');
        const actBtn  = document.getElementById('payslipViewerActivityBtn');
        _viewerId = null;
        content.innerHTML = '<p style="text-align:center; color:#888; padding:40px 0;">Loading…</p>';
        if (sendBtn) sendBtn.style.display = 'none';
        modal.style.display = 'block';

        let data = null;
        try {
            const res = await fetch(PAYSLIP_DATA_URL + '/' + id, { headers: { 'Accept': 'application/json' } });
            if (res.ok) data = (await res.json()).generated_data || null;
        } catch (e) { /* fall back below */ }
        if (!data || !Object.keys(data).length) data = PAYSLIP_DATA[id] || null;

        if (pdfLink) pdfLink.href = '<?= site_url('client_portal/payroll/downloadPayslip') ?>/' + id;
        if (actBtn) actBtn.onclick = () => openPayslipActivity(id);

        if (!data) {
            content.innerHTML = '<p style="color:red;text-align:center;padding:40px;">No payslip data stored for this record.</p>';
            return;
        }

        _viewerId = id;
        content.innerHTML = renderStoredPayslip(data);
        if (sendBtn) {
            const status = relInfo(id).send_status || '';
            const canSend = window.CAN_RELEASE && status !== 'sending';
            sendBtn.style.display = canSend ? '' : 'none';
            sendBtn.textContent = status === 'sent' ? 'Resend to Employee Email' : 'Send Now to Employee Email';
            sendBtn.disabled = false;
        }
    };

    // Kept for any older caller — sends via the release endpoint.
    window.resendPayslipEmail = function(id, btn) { return releaseSendOne(id, btn); };

    window.sendFromViewer = async function() {
        if (!_viewerId) { relToast('No payslip loaded.', 'warning'); return; }
        document.getElementById('payslipViewerModal').style.display = 'none';
        await releaseSendOne(_viewerId, null);
    };

    // ---- Render stored payslip from generated_data (snake_case keys) ----
    function n(v)  { return parseFloat(v || 0); }
    function fmt(v){ return n(v).toFixed(2); }
    function esc(s){
        return String(s || '').replace(/[&<>"]/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
        });
    }
    function fmtDate(s){
        if (!s) return '';
        const d = new Date(s + 'T00:00:00');
        return d.toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'});
    }

    // Mirrors generatePayslipHTML() in payroll_management.php (Bulk Send / Generate
    // Payslip) and buildPayslipHtmlForPdf() in PayrollController — same layout, same
    // 12-row OT breakdown, same rowspan grouping — so the stored-payslip viewer looks
    // identical to the "official" payslip regardless of where it's opened from.
    function renderStoredPayslip(d) {
        const logoHtml = companyLogoUrl ? `<div style="text-align:center;margin-bottom:20px;"><img src="${companyLogoUrl}" style="max-height:80px;margin:0 auto;"></div>` : '';
        const b = 'border:1px solid #000;', p = 'padding:4px 6px;';
        const bp = b + p;
        const hr = n(d.rate_per_hour);

        /* [label, amount, OT multiplier used to back-calculate displayed hours] */
        const otDefs = [
            { label: 'Reg. OT &le;10 hrs',     raw: n(d.reg_ot),        mult: 1.25 },
            { label: 'Reg. OT &gt;10 hrs',     raw: n(d.reg_ot_gt10),   mult: 1.25 },
            { label: 'Reg. Night Premium',      raw: n(d.reg_ot_night),  mult: 0.10 },
            { label: 'Sat/Rest OT &le;10 hrs',  raw: n(d.sat_ot),        mult: 1.30 },
            { label: 'Sat/Rest OT &gt;10 hrs',  raw: n(d.sat_ot_gt10),   mult: 1.69 },
            { label: 'Sat/Rest Night Prem',     raw: n(d.sat_night_prem),mult: 0.10 },
            { label: 'Sun/Spl OT &le;10 hrs',   raw: n(d.sun_ot),        mult: 1.30 },
            { label: 'Sun/Spl OT &gt;10 hrs',   raw: n(d.sun_ot_gt10),   mult: 1.69 },
            { label: 'Sun/Spl Night Prem',      raw: n(d.sun_night_prem),mult: 0.10 },
            { label: 'Hol. Pay &le;8 hrs',     raw: n(d.hol_ot),        mult: 2.00 },
            { label: 'Hol. OT &gt;8 hrs',      raw: n(d.hol_ot_gt10),   mult: 2.60 },
            { label: 'Hol. Night Premium',      raw: n(d.hol_night_prem),mult: 0.10 },
        ];
        const otRowsHtml = otDefs.map(r => {
            const hrs = hr > 0 && r.mult > 0 && r.raw > 0 ? Math.round(r.raw / (hr * r.mult)) : 0;
            return `<tr><td style="${bp}">${r.label}</td><td style="${bp}text-align:center;">${hrs > 0 ? hrs : '-'}</td><td style="${bp}text-align:right;">${r.raw > 0 ? r.raw.toFixed(2) : '-'}</td></tr>`;
        }).join('');
        const totalOt = otDefs.reduce((sum, r) => sum + r.raw, 0);

        const rowspan   = 19;
        const nightDiff = n(d.night_diff_pay);
        const absentAmt = n(d.absent_amt);
        const utAmt     = n(d.undertime_amt);
        const nightHrs  = hr > 0 && nightDiff > 0 ? Math.round(nightDiff / (hr * 0.10)) : 0;
        const absDays   = hr > 0 && absentAmt > 0 ? Math.round(absentAmt / (hr * 8))    : 0;
        const utHrs     = hr > 0 && utAmt     > 0 ? Math.round(utAmt     / hr)          : 0;

        const taxableOther = n(d.other_earnings_taxable);
        const taxCell = taxableOther !== 0
            ? `<strong>Salary adjustment:</strong><br>${taxableOther.toFixed(2)}`
            : '&mdash;';
        const deMinimis       = n(d.de_minimis);
        const vlCashConv      = n(d.vl_cash_conversion);
        const ntParts = [];
        if (deMinimis  > 0) ntParts.push(`<strong>De Minimis Benefit</strong> &nbsp; ${deMinimis.toFixed(2)}`);
        if (vlCashConv > 0) ntParts.push(`<strong>VL Cash Conversion</strong> &nbsp; ${vlCashConv.toFixed(2)}`);
        const nonTaxCell = ntParts.length > 0 ? ntParts.join('<br>') : '&mdash;';

        const netBasicPay = n(d.net_basic_pay);
        const grossPay    = netBasicPay + totalOt + taxableOther + deMinimis + vlCashConv;

        const wthRaw  = n(d.withholding_tax);
        const regDeds = [
            ['SSS',             n(d.sss_ee)],
            ['PhilHealth',      n(d.phic_ee)],
            ['HDMF / Pag-IBIG', n(d.hdmf_ee)],
            ['Withholding Tax', Math.max(0, wthRaw)],
            ...(wthRaw < 0 ? [['Tax Refund (Credit)', Math.abs(wthRaw)]] : []),
        ];
        const loans = [
            ['SSS Salary Loan',   n(d.sss_loan)],
            ['SSS Calamity Loan', n(d.calamity_loan)],
            ['HDMF Loan',         n(d.hdmf_loan)],
            ['Salary Advance',    n(d.salary_advance)],
            ['Cal. Advance',      n(d.cal_adv)],
            // Loans-module installments due this cutoff — already taken out of
            // net pay when the payslip was saved; listed like the emailed PDF.
            ...(Array.isArray(d.loan_deductions) ? d.loan_deductions : [])
                .map(l => [esc(l.loan_type || 'Loan'), n(l.amount)]),
        ].filter(([, v]) => v > 0);
        const totalDeductions = regDeds.reduce((s, [, v]) => s + v, 0) + loans.reduce((s, [, v]) => s + v, 0);
        const maxRows  = Math.max(regDeds.length, loans.length);
        let dedRows = '';
        for (let i = 0; i < maxRows; i++) {
            const l = regDeds[i];
            const r = loans[i];
            dedRows += `<tr>
                ${l ? `<td style="${bp}">${l[0]}</td><td style="${bp}text-align:right;">${l[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
                ${r ? `<td style="${bp}">${r[0]}</td><td style="${bp}text-align:right;">${r[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
            </tr>`;
        }

        return `
        <div style="font-family:Arial,sans-serif;background:#fff;border:1px solid #000;padding:20px;width:100%;box-sizing:border-box;font-size:12px;">
            ${logoHtml}
            <h2 style="text-align:center;margin:5px 0 10px;">PAY SLIP</h2>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr>
                    <td style="${bp}"><strong>Employee:</strong></td>
                    <td style="${bp}">${esc(d.employee_name)}</td>
                    <td style="${bp}"></td>
                    <td style="${bp}"><strong>Payroll period:</strong></td>
                    <td style="${bp}" colspan="2">${fmtDate(d.period_start)} &ndash; ${fmtDate(d.period_end)}</td>
                </tr>
                <tr><td style="${bp}"><strong>Position:</strong></td><td colspan="5" style="${bp}">${esc(d.position)}</td></tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <colgroup>
                    <col style="width:36%">
                    <col style="width:9%">
                    <col style="width:13%">
                    <col style="width:21%">
                    <col style="width:21%">
                </colgroup>
                <tr>
                    <th colspan="3" style="${bp}background:#eee;text-align:center;">REGULAR EARNINGS</th>
                    <th style="${bp}background:#eee;text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="${bp}background:#eee;text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <th style="${bp}background:#f5f5f5;text-align:left;">Description</th>
                    <th style="${bp}background:#f5f5f5;text-align:center;">Days / Hours</th>
                    <th style="${bp}background:#f5f5f5;text-align:right;">Amount</th>
                    <th style="${bp}background:#f5f5f5;"></th>
                    <th style="${bp}background:#f5f5f5;"></th>
                </tr>
                <tr>
                    <td style="${bp}">Basic pay:</td>
                    <td style="${bp}text-align:center;"></td>
                    <td style="${bp}text-align:right;">${n(d.basic_pay).toFixed(2)}</td>
                    <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${taxCell}</td>
                    <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${nonTaxCell}</td>
                </tr>
                <tr><td style="${bp}">Rate per hour</td><td style="${bp}text-align:center;">${hr.toFixed(2)}</td><td style="${bp}"></td></tr>
                <tr><td style="${bp}">No. of hours worked:</td><td style="${bp}text-align:center;">${Math.round(n(d.hours_worked))}</td><td style="${bp}"></td></tr>
                <tr><td style="${bp}">Night premium</td><td style="${bp}text-align:center;">${nightHrs > 0 ? nightHrs : '-'}</td><td style="${bp}text-align:right;">${nightDiff > 0 ? nightDiff.toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}">Less: Absences</td><td style="${bp}text-align:center;">${absDays > 0 ? absDays : '-'}</td><td style="${bp}text-align:right;">${absentAmt > 0 ? absentAmt.toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}">&nbsp;&nbsp;Late / Undertime</td><td style="${bp}text-align:center;">${utHrs > 0 ? utHrs : '-'}</td><td style="${bp}text-align:right;">${utAmt > 0 ? utAmt.toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}"><strong>Net basic pay</strong></td><td style="${bp}"></td><td style="${bp}text-align:right;"><strong>${netBasicPay.toFixed(2)}</strong></td></tr>
                ${otRowsHtml}
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr>
                    <td colspan="3" style="${bp}text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="${bp}text-align:right;"><strong>${grossPay.toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <tr><th colspan="2" style="${bp}background:#eee;text-align:center;">REGULAR DEDUCTIONS</th><th colspan="2" style="${bp}background:#eee;text-align:center;">LOANS / ADVANCES</th></tr>
                ${dedRows}
                <tr><td colspan="2" style="${bp}"></td><td colspan="2" style="${bp}text-align:right;"><strong>TOTAL DEDUCTIONS &nbsp; ${totalDeductions.toFixed(2)}</strong></td></tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <tr>
                    <td style="${bp}">Employee signature:</td>
                    <td style="${bp}height:35px;"></td>
                    <td style="${bp}text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="${bp}text-align:right;background:#f8d7da;"><strong>${n(d.net_pay).toFixed(2)}</strong></td>
                </tr>
            </table>
        </div>`;
    }
})();
</script>
