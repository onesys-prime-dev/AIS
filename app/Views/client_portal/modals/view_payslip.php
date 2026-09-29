<div class="payslip-sheet">
    <div class="payslip-toolbar">
        <div class="payslip-toolbar-left">
            <h2 class="payslip-title">Payslip</h2>
        </div>

        <div class="payslip-controls">
            <select class="payslip-range" id="payslipPeriodSelect" aria-label="Payroll date range">
                <option value="">Loading payslips...</option>
            </select>

            <button type="button" class="payslip-btn" id="printPayslipBtn">
                <span aria-hidden="true">&#128424;</span>
                <span>Print Payslip</span>
            </button>

            <button type="button" class="payslip-btn" id="downloadPdfBtn">
                <span aria-hidden="true">&#11015;</span>
                <span>Download PDF</span>
            </button>

            <button type="button" class="payslip-back modal-close" aria-label="Close payslip modal">
                ✕
            </button>
        </div>
    </div>

    <div class="payslip-card">
        <div class="payslip-company">
            <div class="payslip-date-holder">
                <div class="payslip-meta" id="payslipMeta">
                    <p><span class="payroll-period">Payroll Period</span><br><span id="payrollPeriod">—</span></p>
                    <p><span class="payment-date">Payment Date</span><br><span id="paymentDate">—</span></p>
                </div>

                <div class="payslip-brand">
                    <h3>ECOVIS Fintax Philippines</h3>
                    <p>
                        Unit 1802, The Peak Tower, Salcedo Village, 107 L.P. Leviste Street, Makati City, 1227 Metro Manila
                    </p>
                </div>
            </div>

            <div class="payslip-logo-wrap">
                <?php
                    $__branding = model(\App\Models\Client\CompanyBrandingModel::class)
                        ->where('company_id', 1)->first();
                    $__logoSrc = (!empty($__branding['logo_path']))
                        ? base_url($__branding['logo_path'])
                        : base_url('icons/ecovis_logo.png');
                ?>
                <img id="portalPayslipLogo" src="<?= esc($__logoSrc) ?>" alt="Company logo" class="payslip-logo">
            </div>
        </div>

        <hr class="payslip-divider">

        <div class="payslip-preview hide-scroll-bar" id="payslipPreview">
            <div style="text-align: center; padding: 40px;">Select a payslip from the dropdown</div>
        </div>
    </div>
</div>

<style>
    /*
     * Sizing here is fluid (clamp(min, preferred-vw, max)) rather than fixed
     * pixels + a couple of breakpoints — the old version had its full desktop
     * font sizes (up to 38px) all the way down to 641px viewport width, then
     * jumped abruptly at the 640px breakpoint. That left the whole tablet /
     * small-laptop range oversized and cramped, and any width the two
     * breakpoints didn't anticipate could look off. Fluid values scale
     * continuously with the viewport instead, so this looks right at any
     * window/screen size, not just the two widths that were explicitly
     * tested.
     */
    #viewPayslipModal .civm-box {
        max-width: min(1080px, 96vw);
        width: 100%;
        padding: clamp(14px, 2.5vw, 22px);
        border-radius: 16px;
        box-sizing: border-box;
    }

    #viewPayslipModal .civm-box > .civm-close,
    #viewPayslipModal .civm-box > .civm-title {
        display: none;
    }

    .payslip-sheet {
        width: 100%;
        max-width: 100%;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #1f2937;
        box-sizing: border-box;
    }

    .payslip-sheet * {
        box-sizing: border-box;
        min-width: 0; /* let flex/grid children shrink instead of overflowing */
    }

    .payslip-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .payslip-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .payslip-back {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 8px;
        background: transparent;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        color: #111827;
        flex-shrink: 0;
    }

    .payslip-title {
        margin: 0;
        font-size: clamp(18px, 3vw, 30px);
        font-weight: 700;
        color: #1f2937;
        padding-left: 5px;
    }

    .payslip-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
        flex: 1 1 auto;
        min-width: 0;
    }

    .payslip-range {
        flex: 1 1 220px;
        min-width: 0;
        max-width: 100%;
        height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        color: #374151;
        background: #ffffff;
    }

    .payslip-btn {
        height: 40px;
        border: none;
        border-radius: 8px;
        padding: 0 16px;
        background: #ff2d2d;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .payslip-card {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        background: #f8f8f8;
        padding: clamp(12px, 2.5vw, 22px) clamp(12px, 3vw, 24px);
        max-width: 100%;
        overflow: hidden;
    }

    .payslip-company {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: clamp(10px, 2vw, 20px);
    }

    .payslip-date-holder{
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .payslip-meta {
        display: flex;
        flex-wrap: wrap;
        gap: clamp(16px, 3vw, 44px);
    }

    .payslip-meta p {
        margin: 0;
        font-size: clamp(14px, 1.8vw, 22px);
        line-height: 1.25;
        color: #6b7280;
    }

    .payslip-meta .payroll-period, .payslip-meta .payment-date{
        color: #18181b;
        font-weight: 600;
        font-size: clamp(16px, 2.4vw, 28px);
        overflow-wrap: anywhere;
    }

    .payslip-brand {
        align-self: end;
        min-width: 0;
    }

    .payslip-brand h3 {
        margin: 12px 0 6px;
        font-size: clamp(20px, 3.6vw, 38px);
        line-height: 1.15;
        font-weight: 700;
        color: #1f2937;
        overflow-wrap: anywhere;
    }

    .payslip-brand p {
        margin: 0;
        font-size: clamp(12px, 1.4vw, 17px);
        line-height: 1.45;
        color: #6b7280;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .payslip-logo-wrap {
        width: clamp(70px, 9vw, 120px);
        height: clamp(70px, 9vw, 120px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        justify-self: center;
        align-self: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .payslip-logo {
        width: 84%;
        height: 84%;
        object-fit: contain;
    }

    .payslip-divider {
        border: none;
        border-top: 1px solid #d4d4d8;
        margin: 16px 0 18px;
    }

    .payslip-preview {
        display: block;
        height: 56vh;
        max-width: 100%;
        overflow: auto;
        padding: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        -webkit-overflow-scrolling: touch;
    }

    /* The payslip table itself is generated JS-side (renderPayslipHtml) as a
       fixed, print-style document — it's allowed to be wider than the
       preview pane on very narrow screens and scroll horizontally there,
       rather than squeezing financial figures into unreadable columns. */
    .payslip-preview table {
        width: 100%;
        min-width: 480px;
        border-collapse: collapse;
        font-size: 13px;
    }

    .payslip-preview th, .payslip-preview td {
        border: 1px solid #ccc;
        padding: 6px;
        vertical-align: top;
    }

    .payslip-preview .right {
        text-align: right;
    }

    /* Structural layout changes (column stacking) still need discrete steps —
       fluid sizing above handles everything in between so this breakpoint
       is no longer the only thing standing between "fits" and "broken". */
    @media (max-width: 1024px) {
        .payslip-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .payslip-controls {
            justify-content: flex-start;
        }

        .payslip-company {
            grid-template-columns: 1fr;
        }

        .payslip-brand h3 {
            margin-top: 0;
        }

        .payslip-logo-wrap {
            justify-self: start;
        }
    }

    @media (max-width: 640px) {
        .payslip-range {
            flex-basis: 100%;
        }

        .payslip-btn {
            flex: 1 1 auto;
            justify-content: center;
        }

        .payslip-preview {
            height: 46vh;
        }
    }
</style>

<script>
let currentPayslipId = null;
let currentDownloadUrl = null;

async function loadPayslipList() {
    const select = document.getElementById('payslipPeriodSelect');
    if (!select) return;
    
    try {
        const response = await fetch('<?= site_url('client_portal/payroll/getMyPayslips') ?>');
        if (!response.ok) throw new Error('Failed to load payslips');
        const payslips = await response.json();
        
        if (payslips.length === 0) {
            select.innerHTML = '<option value="">No released payslips yet</option>';
            document.getElementById('payslipPreview').innerHTML = '<div style="text-align:center;padding:40px;color:#6b7280;">No released payslips yet. Your payslip appears here (and in your email) once payroll sends it.</div>';
            return;
        }
        
        let options = '';
        payslips.forEach(ps => {
            const period = `${ps.period_start} to ${ps.period_end}`;
            options += `<option value="${ps.id}" data-download-url="${ps.download_url}">${period}</option>`;
        });
        select.innerHTML = options;
        
        // Auto-select first
        if (payslips.length > 0) {
            select.value = payslips[0].id;
            const selectedOption = select.options[select.selectedIndex];
            currentDownloadUrl = selectedOption.dataset.downloadUrl;
            currentPayslipId = payslips[0].id;
            await loadPayslipPreview(currentPayslipId);
        }
    } catch (err) {
        console.error(err);
        select.innerHTML = '<option value="">Error loading payslips</option>';
        document.getElementById('payslipPreview').innerHTML = '<div style="text-align:center;padding:40px;color:red;">Error loading payslips. Please try again later.</div>';
    }
}

async function loadPayslipPreview(payslipId) {
    const previewDiv = document.getElementById('payslipPreview');
    previewDiv.innerHTML = '<div style="text-align:center;padding:40px;">Loading payslip...</div>';
    
    try {
        const response = await fetch(`<?= site_url('client_portal/payroll/getPayslipData') ?>/${payslipId}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server returned ${response.status}: ${errorText}`);
        }
        const data = await response.json();
        
        // Update meta info
        document.getElementById('payrollPeriod').innerText = `${data.period_start} to ${data.period_end}`;
        document.getElementById('paymentDate').innerText = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        
        // Render HTML preview
        const html = renderPayslipHtml(data.generated_data);
        previewDiv.innerHTML = html;
    } catch (err) {
        console.error('Preview error:', err);
        previewDiv.innerHTML = `<div style="text-align:center;padding:40px;color:red;">Error loading preview: ${err.message}</div>`;
    }
}

function renderPayslipHtml(d) {
    function n(v)   { return parseFloat(v || 0); }
    function fmt(v) { return n(v).toFixed(2); }
    function esc(s) {
        return String(s || '').replace(/[&<>"]/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
        });
    }
    function fmtDate(s) {
        if (!s) return '';
        const dt = new Date(s + 'T00:00:00');
        return dt.toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'});
    }

    const grossPay = n(d.net_basic_pay) + n(d.reg_ot) + n(d.sat_ot) + n(d.sun_ot) + n(d.hol_ot)
                   + n(d.other_earnings_taxable) + n(d.additional_de_minimis_tax) + n(d.de_minimis);
    const govDed   = n(d.sss_ee) + n(d.phic_ee) + n(d.hdmf_ee) + n(d.withholding_tax);
    const loanDed =
        n(d.sss_loan) +
        n(d.calamity_loan) +
        n(d.hdmf_loan) +
        n(d.hdmf_mpl_loan) +
        n(d.hdmf_calamity_loan) +
        n(d.salary_loan) +
        n(d.salary_advance || d.sal_adv) +
        n(d.cash_advance) +
        n(d.emergency_advance || d.emergency_loan);
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
            <colgroup>
                <col style="width:58%">
                <col style="width:21%">
                <col style="width:21%">
            </colgroup>
            <tr>
                <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">REGULAR EARNINGS</th>
                <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">TAXABLE OTHER EARNINGS</th>
                <th style="border:1px solid #000;padding:5px;background:#eee;text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
            </tr>
            <tr>
                <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        <tr><td style="border:none;padding:2px 4px;">Basic pay:</td>                   <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.basic_pay)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Rate per hour:</td>               <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.rate_per_hour)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">No. of hours worked:</td>         <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.hours_worked)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Night premium:</td>               <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.night_diff_pay)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Less: Absences</td>               <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.absent_amt)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Late/Undertime</td>               <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.undertime_amt)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;"><strong>Net basic pay</strong></td><td style="border:none;padding:2px 4px;text-align:right;"><strong>${fmt(d.net_basic_pay)}</strong></td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Reg. OT</td>                      <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.reg_ot)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Sat OT</td>                       <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.sat_ot)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Sun OT</td>                       <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.sun_ot)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Holiday Pay</td>                  <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.hol_ot)}</td></tr>
                    </table>
                </td>
                <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        <tr><td style="border:none;padding:2px 4px;">Salary adjustment:</td><td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.other_earnings_taxable)}</td></tr>
                        ${n(d.additional_de_minimis_tax) > 0 ? `<tr><td style="border:none;padding:2px 4px;">De Minimis (Taxable Excess):</td><td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.additional_de_minimis_tax)}</td></tr>` : ''}
                    </table>
                </td>
                <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        <tr><td style="border:none;padding:2px 4px;">De Minimis Benefit:</td><td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.de_minimis)}</td></tr>
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
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        <tr><td style="border:none;padding:2px 4px;">SSS</td>             <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.sss_ee)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">PhilHealth</td>      <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.phic_ee)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Pag-IBIG</td>        <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.hdmf_ee)}</td></tr>
                        <tr><td style="border:none;padding:2px 4px;">Withholding tax</td> <td style="border:none;padding:2px 4px;text-align:right;">${fmt(d.withholding_tax)}</td></tr>
                    </table>
                </td>
                <td style="border:1px solid #000;padding:5px;vertical-align:top;">
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        ${n(d.sss_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">SSS Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.sss_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.calamity_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">Calamity Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.calamity_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.hdmf_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">HDMF Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.hdmf_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.hdmf_mpl_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">HDMF Multi-Purpose Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.hdmf_mpl_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.hdmf_calamity_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">HDMF Calamity Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.hdmf_calamity_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.salary_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">Salary Loan</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.salary_loan)}
                            </td>
                        </tr>` : ''}

                        ${n(d.salary_advance || d.sal_adv) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">Salary Advance</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.salary_advance || d.sal_adv)}
                            </td>
                        </tr>` : ''}

                        ${n(d.cash_advance) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">Cash Advance</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.cash_advance)}
                            </td>
                        </tr>` : ''}

                        ${n(d.emergency_advance || d.emergency_loan) > 0 ? `
                        <tr>
                            <td style="border:none;padding:2px 4px;">Emergency Advance</td>
                            <td style="border:none;padding:2px 4px;text-align:right;">
                                ${fmt(d.emergency_advance || d.emergency_loan)}
                            </td>
                        </tr>` : ''}
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

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('payslipPeriodSelect');
    if (select) {
        select.addEventListener('change', async function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                currentPayslipId = selectedOption.value;
                currentDownloadUrl = selectedOption.dataset.downloadUrl;
                await loadPayslipPreview(currentPayslipId);
            }
        });
    }
    
    const downloadBtn = document.getElementById('downloadPdfBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (currentDownloadUrl) {
                window.location.href = currentDownloadUrl;
            } else {
                showToast('No payslip selected.', 'warning');
            }
        });
    }
    
    const printBtn = document.getElementById('printPayslipBtn');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            // Printing must go through the same password-protected PDF as
            // "Download PDF" (see PayrollController::downloadPayslip(), which
            // encrypts the file with the employee's payslip password) —
            // this used to build a fresh, unencrypted copy from the on-screen
            // preview HTML and hand it straight to window.print(), which let
            // "Save as PDF" produce a completely unprotected file with the
            // same salary/deduction data. Opening the real PDF means the
            // browser's own viewer enforces the password before anyone can
            // view or print it.
            //
            // mode=inline asks the server for "Content-Disposition: inline"
            // instead of "attachment" — same encrypted file, but it opens in
            // the browser's PDF viewer (so its print button / Ctrl+P works)
            // instead of just saving to disk, which is what Download does.
            if (!currentDownloadUrl) {
                showToast('No payslip selected.', 'warning');
                return;
            }
            const printUrl = currentDownloadUrl
                + (currentDownloadUrl.includes('?') ? '&' : '?')
                + 'mode=inline';
            window.open(printUrl, '_blank');
        });
    }
    
    // Load payslips when modal is opened
    const modal = document.getElementById('viewPayslipModal');
    if (modal) {
        // Bootstrap's `shown.bs.modal` may not be available; use the modal-open buttons instead.
        const openButtons = document.querySelectorAll('[data-modal-open="viewPayslipModal"]');
        openButtons.forEach(btn => {
            btn.addEventListener('click', () => setTimeout(loadPayslipList, 200));
        });
    }
});
</script>