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
                    <p><span>Payroll Period</span><br><span id="payrollPeriod">—</span></p>
                    <p><span>Payment Date</span><br><span id="paymentDate">—</span></p>
                </div>

                <div class="payslip-brand">
                    <h3>ECOVIS Eintax Philippines</h3>
                    <p>
                        Unit 1802, The Peak Tower, Salcedo Village, 107 L.P. Leviste Street, Makati City, 1227 Metro Manila
                    </p>
                </div>
            </div>

            <div class="payslip-logo-wrap">
                <img src="<?= base_url('icons/ecovis_logo.png'); ?>" alt="Ecovis logo" class="payslip-logo">
            </div>
        </div>

        <hr class="payslip-divider">

        <div class="payslip-preview scroll-bar" id="payslipPreview">
            <div style="text-align: center; padding: 40px;">Select a payslip from the dropdown</div>
        </div>
    </div>
</div>

<style>
    #viewPayslipModal .modal-card {
        max-width: 1080px;
        padding: 22px;
        border-radius: 16px;
    }

    #viewPayslipModal > .modal-card > .modal-close,
    #viewPayslipModal > .modal-card > h2 {
        display: none;
    }

    .payslip-sheet {
        width: 100%;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #1f2937;
    }

    .payslip-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
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
    }

    .payslip-title {
        margin: 0;
        font-size: 30px;
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
    }

    .payslip-range {
        min-width: 305px;
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
    }

    .payslip-card {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        background: #f8f8f8;
        padding: 18px 24px 22px;
    }

    .payslip-company {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
    }

    .payslip-date-holder{
        display: flex;
        flex-direction: column;
    }

    .payslip-meta {
        display: flex;
        gap: 44px;
    }

    .payslip-meta p {
        margin: 0;
        font-size: 22px;
        line-height: 1.25;
        color: #18181b;
    }

    .payslip-meta p span {
        color: #6b7280;
        font-size: 18px;
    }

    .payslip-brand {
        align-self: end;
    }

    .payslip-brand h3 {
        margin: 12px 0 6px;
        font-size: 38px;
        line-height: 1.15;
        font-weight: 700;
        color: #1f2937;
    }

    .payslip-brand p {
        margin: 0;
        font-size: 17px;
        line-height: 1.45;
        color: #6b7280;
        max-width: 1000px;
    }

    .payslip-logo-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        justify-self: center;
        align-self: center;
        overflow: hidden;
    }

    .payslip-logo {
        width: 100px;
        height: 100px;
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
        overflow: auto;
        padding: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
    }

    .payslip-preview table {
        width: 100%;
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

    @media (max-width: 1024px) {
        .payslip-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .payslip-controls {
            justify-content: flex-start;
        }

        .payslip-company {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .payslip-meta {
            gap: 24px;
            flex-wrap: wrap;
        }

        .payslip-brand h3 {
            margin-top: 0;
        }

        .payslip-logo-wrap {
            width: 92px;
            height: 92px;
        }
    }

    @media (max-width: 640px) {
        #viewPayslipModal .modal-card {
            padding: 14px;
        }

        .payslip-title {
            font-size: 22px;
        }

        .payslip-range {
            min-width: 100%;
        }

        .payslip-btn {
            width: 100%;
            justify-content: center;
        }

        .payslip-meta p {
            font-size: 20px;
        }

        .payslip-meta p span {
            font-size: 14px;
        }

        .payslip-brand h3 {
            font-size: 24px;
        }

        .payslip-brand p {
            font-size: 14px;
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
            select.innerHTML = '<option value="">No payslips found</option>';
            document.getElementById('payslipPreview').innerHTML = '<div style="text-align:center;padding:40px;">No payslips available</div>';
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
    const formatMoney = (val) => parseFloat(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    const basicPay = parseFloat(d.basic_pay) || 0;
    const nightDiff = parseFloat(d.night_diff_pay) || 0;
    const overtime = parseFloat(d.overtime_pay) || 0;
    const deMinimis = parseFloat(d.de_minimis) || 0;
    const taxableOther = parseFloat(d.other_earnings_taxable) || 0;
    const grossPay = basicPay + nightDiff + overtime + deMinimis + taxableOther;
    
    const sss = parseFloat(d.sss_ee) || 0;
    const phic = parseFloat(d.phic_ee) || 0;
    const hdmf = parseFloat(d.hdmf_ee) || 0;
    const wtax = parseFloat(d.withholding_tax) || 0;
    const sssLoan = parseFloat(d.sss_loan) || 0;
    const calamityLoan = parseFloat(d.calamity_loan) || 0;
    const salaryAdv = parseFloat(d.salary_advance) || 0;
    const otherDed = parseFloat(d.other_deductions) || 0;
    const totalDed = sss + phic + hdmf + wtax + sssLoan + calamityLoan + salaryAdv + otherDed;
    const netPay = grossPay - totalDed;
    
    return `
        <div style="padding: 12px;">
            <table style="width:100%; border-collapse: collapse; margin-bottom:15px;">
                <tr><td colspan="4"><strong>EARNINGS</strong></td></tr>
                <tr><td style="width:25%">Basic Pay</td><td class="right" style="width:25%">${formatMoney(basicPay)}</td><td style="width:25%">Night Differential</td><td class="right" style="width:25%">${formatMoney(nightDiff)}</td></tr>
                <tr><td>Overtime Pay</td><td class="right">${formatMoney(overtime)}</td><td>De Minimis</td><td class="right">${formatMoney(deMinimis)}</td></tr>
                <tr><td>Other Taxable Earnings</td><td class="right">${formatMoney(taxableOther)}</td><td></td><td></td></tr>
                <tr style="background:#f9f9f9;"><td><strong>GROSS PAY</strong></td><td class="right"><strong>${formatMoney(grossPay)}</strong></td><td colspan="2"></td></tr>
            </table>
            <table style="width:100%; border-collapse: collapse; margin-bottom:15px;">
                <tr><td colspan="4"><strong>DEDUCTIONS</strong></td></tr>
                <tr><td>SSS</td><td class="right">${formatMoney(sss)}</td><td>PhilHealth</td><td class="right">${formatMoney(phic)}</td></tr>
                <tr><td>Pag-IBIG</td><td class="right">${formatMoney(hdmf)}</td><td>Withholding Tax</td><td class="right">${formatMoney(wtax)}</td></tr>
                <tr><td>SSS Loan</td><td class="right">${formatMoney(sssLoan)}</td><td>Calamity Loan</td><td class="right">${formatMoney(calamityLoan)}</td></tr>
                <tr><td>Salary Advance</td><td class="right">${formatMoney(salaryAdv)}</td><td>Other Deductions</td><td class="right">${formatMoney(otherDed)}</td></tr>
                <tr style="background:#f9f9f9;"><td><strong>TOTAL DEDUCTIONS</strong></td><td class="right"><strong>${formatMoney(totalDed)}</strong></td><td colspan="2"></td></tr>
            </table>
            <div style="text-align:right; font-size:18px; font-weight:bold; margin-top:20px;">
                NET PAY: ${formatMoney(netPay)}
            </div>
            <div style="margin-top:30px; font-style:italic; text-align:right;">Employee signature: ___________________________</div>
        </div>
    `;
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
                alert('No payslip selected');
            }
        });
    }
    
    const printBtn = document.getElementById('printPayslipBtn');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            const previewContent = document.getElementById('payslipPreview').innerHTML;
            const originalTitle = document.title;
            document.title = 'Payslip';
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html><head><title>Payslip</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ccc; padding: 6px; }
                    .right { text-align: right; }
                </style>
                </head><body>${previewContent}</body></html>
            `);
            printWindow.document.close();
            printWindow.print();
            document.title = originalTitle;
        });
    }
    
    // Load payslips when modal is opened
    const modal = document.getElementById('viewPayslipModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', loadPayslipList);
        const openButtons = document.querySelectorAll('[data-modal-open="viewPayslipModal"]');
        openButtons.forEach(btn => {
            btn.addEventListener('click', () => setTimeout(loadPayslipList, 200));
        });
    }
});
</script>