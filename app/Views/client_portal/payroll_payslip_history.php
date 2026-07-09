<div class="bg-white rounded-2xl shadow-lg border border-gray-400 p-5 h-full flex flex-col gap-3">

    <h2 class="text-xl font-bold text-gray-700">Payslip History</h2>

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
    <div style="background:#fff; margin:2% auto; padding:24px; max-width:860px; border-radius:12px; position:relative; max-height:90vh; overflow-y:auto;">
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

<!-- Embed payslip data server-side so JS doesn't need to call getPayslipData (avoids permission issues) -->
<script>
(function() {
    const PAYSLIP_DATA = {
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

    function renderStoredPayslip(d) {
        const grossPay = n(d.net_basic_pay) + n(d.reg_ot) + n(d.sat_ot) + n(d.sun_ot) + n(d.hol_ot)
                       + n(d.other_earnings_taxable) + n(d.de_minimis);
        const govDed   = n(d.sss_ee) + n(d.phic_ee) + n(d.hdmf_ee) + n(d.withholding_tax);
        const loanDed  = n(d.sss_loan) + n(d.calamity_loan) + n(d.salary_advance);
        const totalDed = govDed + loanDed;

        return `
        <div style="font-family:Arial,sans-serif;background:#fff;border:1px solid #000;padding:20px;box-sizing:border-box;">
            <h2 style="text-align:center;margin:5px 0 10px;">PAY SLIP</h2>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr>
                    <td style="border:1px solid #000;padding:5px;"><strong>Employee:</strong></td>
                    <td style="border:1px solid #000;padding:5px;">${esc(d.employee_name)}</td>
                    <td style="border:1px solid #000;padding:5px;"></td>
                    <td style="border:1px solid #000;padding:5px;"><strong>Payroll period:</strong></td>
                    <td style="border:1px solid #000;padding:5px;" colspan="2">${fmtDate(d.period_start)} &ndash; ${fmtDate(d.period_end)}</td>
                </tr>
                <tr>
                    <td style="border:1px solid #000;padding:5px;"><strong>Position:</strong></td>
                    <td colspan="5" style="border:1px solid #000;padding:5px;">${esc(d.position)}</td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">REGULAR EARNINGS</th>
                    <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                        <table style="width:100%;font-size:12px;">
                            <tr><td>Basic pay:</td><td style="text-align:right;">${fmt(d.basic_pay)}</td></tr>
                            <tr><td>Rate per hour:</td><td style="text-align:right;">${fmt(d.rate_per_hour)}</td></tr>
                            <tr><td>No. of hours worked:</td><td style="text-align:right;">${fmt(d.hours_worked)}</td></tr>
                            <tr><td>Night premium:</td><td style="text-align:right;">${fmt(d.night_diff_pay)}</td></tr>
                            <tr><td>Less: Absences</td><td style="text-align:right;">${fmt(d.absent_amt)}</td></tr>
                            <tr><td>Late/Undertime</td><td style="text-align:right;">${fmt(d.undertime_amt)}</td></tr>
                            <tr><td><strong>Net basic pay</strong></td><td style="text-align:right;"><strong>${fmt(d.net_basic_pay)}</strong></td></tr>
                            <tr><td>Reg. OT</td><td style="text-align:right;">${fmt(d.reg_ot)}</td></tr>
                            <tr><td>Sat OT</td><td style="text-align:right;">${fmt(d.sat_ot)}</td></tr>
                            <tr><td>Sun OT</td><td style="text-align:right;">${fmt(d.sun_ot)}</td></tr>
                            <tr><td>Holiday OT</td><td style="text-align:right;">${fmt(d.hol_ot)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                        <table style="width:100%;font-size:12px;">
                            <tr><td>Salary adjustment:</td><td style="text-align:right;">${fmt(d.other_earnings_taxable)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                        <table style="width:100%;font-size:12px;">
                            <tr><td>De Minimis Benefit:</td><td style="text-align:right;">${fmt(d.de_minimis)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000;padding:5px;text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="border:1px solid #000;padding:5px;text-align:right;"><strong>${grossPay.toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">REGULAR DEDUCTIONS</th>
                    <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">LOANS/ADVANCES</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                        <table style="width:100%;font-size:12px;">
                            <tr><td>SSS</td><td style="text-align:right;">${fmt(d.sss_ee)}</td></tr>
                            <tr><td>PhilHealth</td><td style="text-align:right;">${fmt(d.phic_ee)}</td></tr>
                            <tr><td>Pag-IBIG</td><td style="text-align:right;">${fmt(d.hdmf_ee)}</td></tr>
                            <tr><td>Withholding tax</td><td style="text-align:right;">${fmt(d.withholding_tax)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                        <table style="width:100%;font-size:12px;">
                            <tr><td>Salary Loan</td><td style="text-align:right;">${fmt(d.sss_loan)}</td></tr>
                            <tr><td>Calamity Loan</td><td style="text-align:right;">${fmt(d.calamity_loan)}</td></tr>
                            <tr><td>Salary Advance</td><td style="text-align:right;">${fmt(d.salary_advance)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000;padding:5px;text-align:right;"><strong>TOTAL DEDUCTIONS</strong></td>
                    <td style="border:1px solid #000;padding:5px;text-align:right;"><strong>${totalDed.toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px;">
                <tr>
                    <td style="border:1px solid #000;padding:5px;">Employee signature:</td>
                    <td style="border:1px solid #000;padding:5px;height:40px;"></td>
                    <td style="border:1px solid #000;padding:5px;text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="border:1px solid #000;padding:5px;text-align:right;background:#f8d7da;"><strong>${fmt(d.net_pay)}</strong></td>
                </tr>
            </table>
        </div>`;
    }
})();
</script>
