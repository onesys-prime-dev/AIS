<?php
// Fetch company logo from database (mirrors payroll_management.php) so the
// stored-payslip viewer's document preview looks identical to Generate Payslip.
$companyBranding = model(\App\Models\Client\CompanyBrandingModel::class)
    ->where('company_id', 1)
    ->first();
$companyLogoUrl = $companyBranding && !empty($companyBranding['logo_path'])
    ? base_url($companyBranding['logo_path'])
    : '';
?>
<div class="flex flex-col h-full gap-4">

    <h3 class="font-semibold text-xl mb-1">Send &amp; Edit History</h3>

    <!-- Header row -->
    <div class="grid grid-cols-[2fr_2fr_1.2fr_1.5fr_1.2fr_1.5fr]
                bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-bold">
        <div class="p-4">Employee</div>
        <div class="p-4">Period</div>
        <div class="p-4 text-right">Net Pay</div>
        <div class="p-4">Date Sent</div>
        <div class="p-4">Sent By</div>
        <div class="p-4 text-center">Actions</div>
    </div>

    <!-- Body -->
    <div class="flex-1 min-h-0 overflow-y-auto text-gray-700 text-sm scroll-bar">

        <?php if (!empty($payslips)): ?>
            <?php foreach ($payslips as $ps): ?>
            <div class="grid grid-cols-[2fr_2fr_1.2fr_1.5fr_1.2fr_1.5fr] items-center border-b hover:bg-gray-50 transition-colors">

                <div class="p-4 font-medium truncate">
                    <?= esc((string) ($ps['employee_name'] ?? '---')) ?>
                </div>

                <div class="p-4 whitespace-nowrap">
                    <?= !empty($ps['period_start']) ? date('M d, Y', strtotime($ps['period_start'])) : '---' ?>
                    &ndash;
                    <?= !empty($ps['period_end'])   ? date('M d, Y', strtotime($ps['period_end']))   : '---' ?>
                </div>

                <div class="p-4 text-right font-semibold">
                    ₱<?= number_format((float) ($ps['net_pay'] ?? 0), 2) ?>
                </div>

                <div class="p-4 whitespace-nowrap text-sm">
                    <?php if (!empty($ps['sent_at'])): ?>
                        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-semibold">
                            &#10003; <?= date('M d, Y', strtotime($ps['sent_at'])) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-400 text-xs">Not sent</span>
                    <?php endif; ?>
                </div>

                <div class="p-4 text-sm">
                    <?php if (!empty($ps['sent_by'])): ?>
                        <span class="font-medium text-gray-700"><?= esc($ps['sent_by']) ?></span>
                    <?php else: ?>
                        <span class="text-gray-400">—</span>
                    <?php endif; ?>
                </div>

                <div class="p-4 flex justify-center items-center gap-2 flex-wrap">
                    <button onclick="openPayslipViewer(<?= (int)($ps['id'] ?? 0) ?>)"
                            class="px-3 py-1.5 bg-[#7b5cff] hover:bg-[#6a4de0] text-white rounded-lg text-xs font-semibold transition">
                        View
                    </button>
                    <a href="<?= site_url('client_portal/payroll/downloadPayslip/' . ($ps['id'] ?? 0)) ?>"
                       class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-xs font-semibold transition">
                        PDF
                    </a>
                    <button onclick="resendPayslipEmail(<?= (int)($ps['id'] ?? 0) ?>, this)"
                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                        <?= !empty($ps['sent_at']) ? 'Resend' : 'Send' ?>
                    </button>
                    <button onclick="openPayslipActivity(<?= (int)($ps['id'] ?? 0) ?>)"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-lg text-xs font-semibold transition">
                        Activity
                    </button>
                </div>

            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="flex items-center justify-center py-16 text-gray-400 text-sm">
                No payslips generated yet. Use <strong class="mx-1">Generate Payslip</strong> to create records.
            </div>
        <?php endif; ?>

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
        <div style="text-align:center; margin-top:16px; padding-top:16px; border-top:1px solid #e5e7eb; display:flex; gap:10px; justify-content:center;">
            <button id="payslipViewerSendBtn"
                style="background:#16a34a; color:#fff; border:none; padding:10px 28px; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px;"
                onclick="sendFromViewer()">Send to Employee Email</button>
            <button onclick="document.getElementById('payslipViewerModal').style.display='none'"
                style="background:#6b7280; color:#fff; border:none; padding:10px 28px; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px;">Close</button>
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
    // This page is now embedded several levels deep (Payroll Register & History
    // → History sub-tab → this partial), so move the modals to be direct
    // children of <body> — makes them immune to any ancestor's display/overflow/
    // stacking-context quirks instead of depending on every wrapper in between
    // behaving perfectly.
    document.addEventListener('DOMContentLoaded', function () {
        ['payslipViewerModal', 'payslipActivityModal'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) document.body.appendChild(el);
        });
    });

    // Exposed on window (not just const-scoped to this IIFE) so other scripts
    // on the page — e.g. the Payslip tab's "Send All" in payroll_register.php —
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

    const SEND_PDF_URL = '<?= site_url('client_portal/payroll/sendPdf') ?>';
    const ACTIVITY_URL = '<?= site_url('client_portal/payroll/getPayslipActivity') ?>';
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

    let _viewerData = null;

    window.openPayslipViewer = function(id) {
        const data = PAYSLIP_DATA[id];
        const modal   = document.getElementById('payslipViewerModal');
        const content = document.getElementById('payslipViewerContent');
        _viewerData   = null;

        if (!data) {
            content.innerHTML = '<p style="color:red;text-align:center;padding:40px;">No payslip data stored for this record.</p>';
            modal.style.display = 'block';
            return;
        }

        _viewerData = data;
        content.innerHTML = renderStoredPayslip(data);
        modal.style.display = 'block';
    };

    window.resendPayslipEmail = async function(id, btn) {
        const data = PAYSLIP_DATA[id];
        if (!data) { showToast('No stored payslip data for this record.', 'warning'); return; }
        if (!confirm('Send payslip PDF to the employee\'s email?')) return;

        const origText  = btn.textContent;
        btn.disabled    = true;
        btn.textContent = 'Sending…';
        try {
            const res  = await fetch(SEND_PDF_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.error || 'Send failed');
            showToast('Payslip sent successfully to employee email.', 'success');
            setTimeout(() => location.reload(), 1200);
        } catch (e) {
            showToast('Error: ' + e.message, 'error');
            btn.disabled    = false;
            btn.textContent = origText;
        }
    };

    window.sendFromViewer = async function() {
        if (!_viewerData) { showToast('No payslip loaded.', 'warning'); return; }
        if (!confirm('Send this payslip PDF to the employee\'s email?')) return;
        const btn = document.getElementById('payslipViewerSendBtn');
        btn.disabled    = true;
        btn.textContent = 'Sending…';
        try {
            const res  = await fetch(SEND_PDF_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(_viewerData)
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.error || 'Send failed');
            showToast('Payslip sent successfully to employee email.', 'success');
            document.getElementById('payslipViewerModal').style.display = 'none';
            setTimeout(() => location.reload(), 1200);
        } catch (e) {
            showToast('Error: ' + e.message, 'error');
            btn.disabled    = false;
            btn.textContent = 'Send to Employee Email';
        }
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
