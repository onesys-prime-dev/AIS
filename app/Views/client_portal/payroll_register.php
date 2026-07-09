<div class="h-full flex flex-col min-h-0 gap-4">

    <!-- ── Header ───────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Payroll Register</h1>
            <p class="text-sm text-gray-500 mt-0.5">Matches client Excel register format. SSS/PHIC/HDMF are 0 for 1st half per DOLE practice.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Month picker -->
            <input type="month" id="regMonthPicker" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">

            <!-- Period selector -->
            <select id="regPeriodPicker" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="1st_half">1st Half</option>
                <option value="2nd_half">2nd Half</option>
                <option value="">Full Month</option>
            </select>

            <!-- Load button -->
            <button onclick="loadRegister()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Load
            </button>

            <!-- Export CSV -->
            <button onclick="exportRegisterCsv()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Export CSV
            </button>
        </div>
    </div>

    <!-- ── Table wrapper ─────────────────────────────────────────────── -->
    <div class="flex-1 min-h-0 overflow-auto bg-white border border-gray-300 rounded-2xl shadow-sm">

        <div id="regLoading" class="hidden py-16 text-center text-gray-400 text-sm">Loading…</div>
        <div id="regEmpty"   class="py-16 text-center text-gray-400 text-sm">Select a month and period, then click Load.</div>

        <table id="regTable" class="reg-table hidden">
            <thead>
                <!-- Row 1: Group headers -->
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">Name of Employees</th>
                    <th rowspan="2">Monthly<br>Basic Salary</th>
                    <th rowspan="2">Semi-Monthly<br>Basic Salary</th>
                    <th colspan="6" class="group-hdr">DE MINIMIS BENEFIT</th>
                    <th rowspan="2">Other Non-Taxable<br>Compensation</th>
                    <th rowspan="2">Salary<br>Adjustment</th>
                    <th rowspan="2">Overtime</th>
                    <th rowspan="2">NSD</th>
                    <th rowspan="2">Absences /<br>Tardiness</th>
                    <th rowspan="2">Holiday<br>Pay</th>
                    <th rowspan="2">Gross<br>Compensation</th>
                    <th rowspan="2">Gross Taxable<br>Compensation</th>
                    <th rowspan="2">Non-Taxable<br>Compensation</th>
                    <th colspan="3" class="group-hdr">EMPLOYEE'S SHARE</th>
                    <th rowspan="2">Regular Net<br>Taxable Comp.</th>
                    <th rowspan="2">Withholding<br>Tax Due</th>
                    <th rowspan="2">Tax<br>Refund/Adj</th>
                    <th colspan="3" class="group-hdr">LOANS</th>
                    <th rowspan="2">Advances to<br>Employees</th>
                    <th rowspan="2">NET PAY</th>
                </tr>
                <tr>
                    <!-- De Minimis sub-headers -->
                    <th class="sub-hdr">Year End<br>Gifts</th>
                    <th class="sub-hdr">Rice<br>Subsidy</th>
                    <th class="sub-hdr">Uniform /<br>Clothing</th>
                    <th class="sub-hdr">Laundry</th>
                    <th class="sub-hdr">Meal</th>
                    <th class="sub-hdr">Medical</th>
                    <!-- Employee Share sub-headers -->
                    <th class="sub-hdr">SSS</th>
                    <th class="sub-hdr">PhilHealth</th>
                    <th class="sub-hdr">Pag-IBIG</th>
                    <!-- Loan sub-headers -->
                    <th class="sub-hdr">SSS Loan</th>
                    <th class="sub-hdr">Calamity</th>
                    <th class="sub-hdr">HDMF Loan</th>
                </tr>
            </thead>
            <tbody id="regBody">
            </tbody>
            <tfoot id="regFoot">
            </tfoot>
        </table>
    </div>
</div>

<style>
.reg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    min-width: 2200px;
}
.reg-table th {
    background: #1B2A4A;
    color: #fff;
    padding: 7px 8px;
    border: 1px solid #374151;
    text-align: center;
    white-space: nowrap;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1;
}
.reg-table th.group-hdr {
    background: #2E75B6;
    border-color: #1d5a9a;
}
.reg-table th.sub-hdr {
    background: #3B82F6;
    border-color: #2563EB;
    font-weight: 500;
    font-size: 11px;
}
.reg-table td {
    padding: 6px 8px;
    border: 1px solid #e5e7eb;
    text-align: right;
    white-space: nowrap;
}
.reg-table td.left  { text-align: left; }
.reg-table td.center{ text-align: center; }
.reg-table tbody tr:nth-child(even) { background: #f9fafb; }
.reg-table tbody tr:hover { background: #eff6ff; }
.reg-table tfoot tr { background: #1B2A4A; color: #fff; font-weight: 700; }
.reg-table tfoot td { border-color: #374151; padding: 7px 8px; }
.net-pay-cell { font-weight: 700; color: #166534; }
.zero-cell    { color: #9ca3af; }
</style>

<script>
(function () {
    const BASE = window.baseUrl ?? (window.location.origin + '/');

    // Init month/period to current month, 1st half
    const now = new Date();
    document.getElementById('regMonthPicker').value =
        now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

    const fmt = n => parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const zeroOrFmt = n => parseFloat(n || 0) === 0
        ? '<span class="zero-cell">—</span>'
        : fmt(n);

    window.loadRegister = async function () {
        const month  = document.getElementById('regMonthPicker').value;
        const period = document.getElementById('regPeriodPicker').value;
        if (!month) { showToast('Please select a month.', 'warning'); return; }

        document.getElementById('regLoading').classList.remove('hidden');
        document.getElementById('regEmpty').classList.add('hidden');
        document.getElementById('regTable').classList.add('hidden');

        try {
            const url = BASE + 'payroll/getRegisterRows?month=' + month + (period ? '&period=' + period : '');
            const res  = await fetch(url);
            const rows = await res.json();

            if (!rows || !rows.length) {
                document.getElementById('regEmpty').textContent = 'No payroll records found for this period.';
                document.getElementById('regEmpty').classList.remove('hidden');
                document.getElementById('regLoading').classList.add('hidden');
                return;
            }

            // Build totals
            const totals = {};
            const numCols = ['monthly_salary','semi_monthly','year_end_gifts','rice_subsidy',
                'uniform_allow','laundry_allow','meal_allow','medical_allow','other_non_tax',
                'salary_adj','overtime','nsd','absences','holiday_pay','gross_comp',
                'gross_taxable','non_taxable','sss','phic','hdmf','net_taxable','wth',
                'tax_refund','sss_loan','calamity_loan','hdmf_loan','advances','net_pay'];
            numCols.forEach(k => { totals[k] = 0; });

            const tbody = document.getElementById('regBody');
            tbody.innerHTML = '';
            rows.forEach(r => {
                numCols.forEach(k => { totals[k] += parseFloat(r[k] || 0); });
                tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td class="center">${r.no}</td>
                    <td class="left">${r.employee_name}</td>
                    <td>${fmt(r.monthly_salary)}</td>
                    <td>${fmt(r.semi_monthly)}</td>
                    <td>${zeroOrFmt(r.year_end_gifts)}</td>
                    <td>${zeroOrFmt(r.rice_subsidy)}</td>
                    <td>${zeroOrFmt(r.uniform_allow)}</td>
                    <td>${zeroOrFmt(r.laundry_allow)}</td>
                    <td>${zeroOrFmt(r.meal_allow)}</td>
                    <td>${zeroOrFmt(r.medical_allow)}</td>
                    <td>${zeroOrFmt(r.other_non_tax)}</td>
                    <td>${zeroOrFmt(r.salary_adj)}</td>
                    <td>${zeroOrFmt(r.overtime)}</td>
                    <td>${zeroOrFmt(r.nsd)}</td>
                    <td>${zeroOrFmt(r.absences)}</td>
                    <td>${zeroOrFmt(r.holiday_pay)}</td>
                    <td>${fmt(r.gross_comp)}</td>
                    <td>${fmt(r.gross_taxable)}</td>
                    <td>${fmt(r.non_taxable)}</td>
                    <td>${zeroOrFmt(r.sss)}</td>
                    <td>${zeroOrFmt(r.phic)}</td>
                    <td>${zeroOrFmt(r.hdmf)}</td>
                    <td>${fmt(r.net_taxable)}</td>
                    <td>${fmt(r.wth)}</td>
                    <td>${zeroOrFmt(r.tax_refund)}</td>
                    <td>${zeroOrFmt(r.sss_loan)}</td>
                    <td>${zeroOrFmt(r.calamity_loan)}</td>
                    <td>${zeroOrFmt(r.hdmf_loan)}</td>
                    <td>${zeroOrFmt(r.advances)}</td>
                    <td class="net-pay-cell">${fmt(r.net_pay)}</td>
                </tr>`);
            });

            // Totals row
            const tfoot = document.getElementById('regFoot');
            tfoot.innerHTML = `
            <tr>
                <td colspan="2" style="text-align:left;">TOTAL (${rows.length} employees)</td>
                <td>${fmt(totals.monthly_salary)}</td>
                <td>${fmt(totals.semi_monthly)}</td>
                <td>${fmt(totals.year_end_gifts)}</td>
                <td>${fmt(totals.rice_subsidy)}</td>
                <td>${fmt(totals.uniform_allow)}</td>
                <td>${fmt(totals.laundry_allow)}</td>
                <td>${fmt(totals.meal_allow)}</td>
                <td>${fmt(totals.medical_allow)}</td>
                <td>${fmt(totals.other_non_tax)}</td>
                <td>${fmt(totals.salary_adj)}</td>
                <td>${fmt(totals.overtime)}</td>
                <td>${fmt(totals.nsd)}</td>
                <td>${fmt(totals.absences)}</td>
                <td>${fmt(totals.holiday_pay)}</td>
                <td>${fmt(totals.gross_comp)}</td>
                <td>${fmt(totals.gross_taxable)}</td>
                <td>${fmt(totals.non_taxable)}</td>
                <td>${fmt(totals.sss)}</td>
                <td>${fmt(totals.phic)}</td>
                <td>${fmt(totals.hdmf)}</td>
                <td>${fmt(totals.net_taxable)}</td>
                <td>${fmt(totals.wth)}</td>
                <td>${fmt(totals.tax_refund)}</td>
                <td>${fmt(totals.sss_loan)}</td>
                <td>${fmt(totals.calamity_loan)}</td>
                <td>${fmt(totals.hdmf_loan)}</td>
                <td>${fmt(totals.advances)}</td>
                <td>${fmt(totals.net_pay)}</td>
            </tr>`;

            document.getElementById('regTable').classList.remove('hidden');
        } catch (e) {
            document.getElementById('regEmpty').textContent = 'Error loading data: ' + e.message;
            document.getElementById('regEmpty').classList.remove('hidden');
        } finally {
            document.getElementById('regLoading').classList.add('hidden');
        }
    };

    window.exportRegisterCsv = function () {
        const month  = document.getElementById('regMonthPicker').value;
        const period = document.getElementById('regPeriodPicker').value;
        if (!month) { showToast('Please select a month first.', 'warning'); return; }
        const url = (BASE + 'payroll/exportRegister?month=' + month + (period ? '&period=' + period : ''));
        window.open(url, '_blank');
    };

    // Auto-load when tab is activated
    document.addEventListener('DOMContentLoaded', function () {
        const regTab = document.querySelector('.tab[data-tab="payroll-register"]');
        if (regTab) {
            let loaded = false;
            regTab.addEventListener('click', function () {
                if (!loaded) { loaded = true; loadRegister(); }
            });
        }
    });
})();
</script>
