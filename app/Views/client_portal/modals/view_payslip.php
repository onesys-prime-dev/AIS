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
       rather than squeezing financial figures into unreadable columns.
       Scoped to the top-level tables only (direct children of the injected
       wrapper div) — renderPayslipHtml() nests small 2-column label/value
       tables several levels deep for layout, and a plain descendant
       selector here was forcing every one of those onto the same 480px
       min-width as the outer document, blowing them far wider than their
       containing cell and squeezing/overlapping the label columns. */
    .payslip-preview > div > table {
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
let payslipRateSettings = null;

// The OT-hours breakdown below needs the same rate constants Generate
// Payslip uses (night diff / OT multipliers) — fetched once and cached,
// same endpoint payroll_management.php uses for window._payrollSettings.
async function ensurePayslipRateSettings() {
    if (payslipRateSettings) return payslipRateSettings;
    try {
        const res = await fetch('<?= site_url('client_portal/payroll/getSettings') ?>');
        payslipRateSettings = res.ok ? await res.json() : {};
    } catch (e) {
        payslipRateSettings = {};
    }
    return payslipRateSettings;
}

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
        await ensurePayslipRateSettings();
        const html = renderPayslipHtml(data.generated_data);
        previewDiv.innerHTML = html;
    } catch (err) {
        console.error('Preview error:', err);
        previewDiv.innerHTML = `<div style="text-align:center;padding:40px;color:red;">Error loading preview: ${err.message}</div>`;
    }
}

// Mirrors payroll_management.php's generatePayslipHTML(d) — same column
// layout, headers, and OT/deduction breakdown — but reads the snake_case
// field names actually stored in employee_payslips.generated_data (the
// Generate Payslip page's own save payload uses these same keys) instead of
// that page's camelCase live-form variable names.
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
        return dt.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
    }

    const bp = 'border:1px solid #000;padding:4px 6px;';
    const s      = payslipRateSettings || {};
    const hr     = n(d.rate_per_hour);
    const ndR    = parseFloat(s.night_diff_rate) || 0.10;
    const regOtR = parseFloat(s.ot_regular_rate) || 1.25;
    const restR  = parseFloat(s.ot_rest_rate)    || 1.69;
    const holOtR = parseFloat(s.ot_holiday_rate) || 2.60;

    /* All 12 OT rows — always shown, zero values display as '-' — same set
       generatePayslipHTML() uses. */
    const otDefs = [
        { label: 'Reg. OT &le;10 hrs',     raw: n(d.reg_ot),        rate: hr * regOtR },
        { label: 'Reg. OT &gt;10 hrs',     raw: n(d.reg_ot_gt10),   rate: hr * regOtR },
        { label: 'Reg. Night Premium',      raw: n(d.reg_ot_night),  rate: hr * ndR    },
        { label: 'Sat/Rest OT &le;10 hrs',  raw: n(d.sat_ot),        rate: hr * restR  },
        { label: 'Sat/Rest OT &gt;10 hrs',  raw: n(d.sat_ot_gt10),   rate: hr * restR  },
        { label: 'Sat/Rest Night Prem',     raw: n(d.sat_night_prem),rate: hr * ndR    },
        { label: 'Sun/Spl OT &le;10 hrs',   raw: n(d.sun_ot),        rate: hr * restR  },
        { label: 'Sun/Spl OT &gt;10 hrs',   raw: n(d.sun_ot_gt10),   rate: hr * restR  },
        { label: 'Sun/Spl Night Prem',      raw: n(d.sun_night_prem),rate: hr * ndR    },
        { label: 'Hol. Pay &le;8 hrs',     raw: n(d.hol_ot),        rate: hr * ((parseFloat(s.reg_holiday_rate) || 2.00) - 1.0) },
        { label: 'Hol. OT &gt;8 hrs',      raw: n(d.hol_ot_gt10),   rate: hr * holOtR },
        { label: 'Hol. Night Premium',      raw: n(d.hol_night_prem),rate: hr * ndR    },
    ];
    const otRowsHtml = otDefs.map(r => {
        const hrs = r.rate > 0 && r.raw > 0 ? Math.round(r.raw / r.rate) : 0;
        return `<tr><td style="${bp}">${r.label}</td><td style="${bp}text-align:center;">${hrs > 0 ? hrs : '-'}</td><td style="${bp}text-align:right;">${r.raw > 0 ? r.raw.toFixed(2) : '-'}</td></tr>`;
    }).join('');

    const rowspan  = 19;
    const nightHrs = hr > 0 && n(d.night_diff_pay) > 0 ? Math.round(n(d.night_diff_pay) / (hr * ndR)) : 0;
    const absDays  = hr > 0 && n(d.absent_amt)    > 0 ? Math.round(n(d.absent_amt)    / (hr * 8))   : 0;
    const utHrs    = hr > 0 && n(d.undertime_amt) > 0 ? Math.round(n(d.undertime_amt) / hr)          : 0;

    const taxCell = n(d.other_earnings_taxable) !== 0
        ? `<strong>Salary adjustment:</strong><br>${fmt(d.other_earnings_taxable)}`
        : '&mdash;';
    const ntParts = [];
    const salNonTaxAdj = n(d.sal_non_tax_adj);
    const deMinBase = n(d.de_minimis) - salNonTaxAdj;
    if (deMinBase > 0) ntParts.push(`<strong>De Minimis Benefit</strong> &nbsp; ${deMinBase.toFixed(2)}`);
    if (salNonTaxAdj > 0) ntParts.push(`<strong>Salary Adjustment (Non-Taxable)</strong> &nbsp; ${salNonTaxAdj.toFixed(2)}`);
    if (n(d.vl_cash_conversion) > 0) ntParts.push(`<strong>VL Cash Conversion</strong> &nbsp; ${fmt(d.vl_cash_conversion)}`);
    const nonTaxCell = ntParts.length > 0 ? ntParts.join('<br>') : '&mdash;';

    const grossPay = n(d.gross_pay) || (n(d.net_basic_pay) + otDefs.reduce((sum, r) => sum + r.raw, 0)
        + n(d.other_earnings_taxable) + n(d.de_minimis));

    const _wthRaw = n(d.withholding_tax);
    const regDeds = [
        ['SSS',             n(d.sss_ee)],
        ['PhilHealth',      n(d.phic_ee)],
        ['HDMF / Pag-IBIG', n(d.hdmf_ee)],
        ['Withholding Tax', Math.max(0, _wthRaw)],
        ...(_wthRaw < 0 ? [['Tax Refund (Credit)', Math.abs(_wthRaw)]] : []),
    ];
    const loans = [
        ['SSS Salary Loan', n(d.sss_loan)],
        ['SSS Calamity Loan', n(d.calamity_loan)],
        ['HDMF Loan',       n(d.hdmf_loan)],
        ['HDMF Multi-Purpose Loan', n(d.hdmf_mpl_loan)],
        ['HDMF Calamity Loan', n(d.hdmf_calamity_loan)],
        ['Salary Advance',  n(d.salary_advance || d.sal_adv)],
        ['Cash Advance',    n(d.cash_advance)],
        ['Emergency Advance', n(d.emergency_advance || d.emergency_loan)],
        ['Cal. Advance',    n(d.cal_adv)],
    ].filter(([, v]) => v > 0);
    const totalDeductions = regDeds.reduce((sum, [, v]) => sum + v, 0) + loans.reduce((sum, [, v]) => sum + v, 0);

    const maxRows = Math.max(regDeds.length, loans.length);
    let dedLoanRows = '';
    for (let i = 0; i < maxRows; i++) {
        const l = regDeds[i];
        const r = loans[i];
        dedLoanRows += `<tr>
            ${l ? `<td style="${bp}">${l[0]}</td><td style="${bp}text-align:right;">${l[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
            ${r ? `<td style="${bp}">${r[0]}</td><td style="${bp}text-align:right;">${r[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
        </tr>`;
    }

    return `
    <div style="font-family:Arial,sans-serif;background:#fff;border:1px solid #000;padding:20px;width:100%;box-sizing:border-box;font-size:12px;">
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
                <td style="${bp}text-align:right;">${fmt(d.basic_pay)}</td>
                <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${taxCell}</td>
                <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${nonTaxCell}</td>
            </tr>
            <tr><td style="${bp}">Rate per hour</td><td style="${bp}text-align:center;">${fmt(d.rate_per_hour)}</td><td style="${bp}"></td></tr>
            <tr><td style="${bp}">No. of hours worked:</td><td style="${bp}text-align:center;">${Math.round(n(d.hours_worked))}</td><td style="${bp}"></td></tr>
            <tr><td style="${bp}">Night premium</td><td style="${bp}text-align:center;">${nightHrs > 0 ? nightHrs : '-'}</td><td style="${bp}text-align:right;">${n(d.night_diff_pay) > 0 ? fmt(d.night_diff_pay) : '-'}</td></tr>
            <tr><td style="${bp}">Less: Absences</td><td style="${bp}text-align:center;">${absDays > 0 ? absDays : '-'}</td><td style="${bp}text-align:right;">${n(d.absent_amt) > 0 ? fmt(d.absent_amt) : '-'}</td></tr>
            <tr><td style="${bp}">&nbsp;&nbsp;Late / Undertime</td><td style="${bp}text-align:center;">${utHrs > 0 ? utHrs : '-'}</td><td style="${bp}text-align:right;">${n(d.undertime_amt) > 0 ? fmt(d.undertime_amt) : '-'}</td></tr>
            <tr><td style="${bp}"><strong>Net basic pay</strong></td><td style="${bp}"></td><td style="${bp}text-align:right;"><strong>${fmt(d.net_basic_pay)}</strong></td></tr>
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
            ${dedLoanRows}
            <tr><td colspan="2" style="${bp}"></td><td colspan="2" style="${bp}text-align:right;"><strong>TOTAL DEDUCTIONS &nbsp; ${totalDeductions.toFixed(2)}</strong></td></tr>
        </table>
        <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
            <tr>
                <td style="${bp}">Employee signature:</td>
                <td style="${bp}height:35px;"></td>
                <td style="${bp}text-align:right;"><strong>NET PAY:</strong></td>
                <td style="${bp}text-align:right;background:#f8d7da;"><strong>${fmt(d.net_pay)}</strong></td>
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