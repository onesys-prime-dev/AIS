<div class="flex flex-col gap-6 h-full">

    <!-- ── Card ─────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-400 p-6 flex flex-col gap-5 flex-1 min-h-0">

        <!-- ── Top bar ───────────────────────────────────────────────── -->
        <div class="flex items-end gap-4 flex-wrap">

            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest block mb-1">Reference Year</label>
                <input type="number" id="yearEndYear" value="<?= date('Y') ?>"
                       min="2000" max="2100"
                       class="bg-gray-100 border border-gray-300 rounded-lg px-4 py-2.5 text-sm w-32 outline-none focus:border-[#7b5cff]">
            </div>

            <button id="btnComputeYearEnd" onclick="computeYearEnd()"
                    class="bg-[#7b5cff] hover:bg-[#6a4de0] text-white px-6 py-2.5 rounded-lg text-sm font-semibold shadow-sm flex items-center gap-2 transition">
                <span id="yearEndBtnText">Compute</span>
                <span id="yearEndSpinner" class="hidden">&#8987;</span>
            </button>

            <span id="yearEndStatus" class="text-sm text-gray-400 pb-0.5"></span>

        </div>

        <!-- ── Inner tabs ────────────────────────────────────────────── -->
        <div class="flex gap-1 border-b border-gray-200">
            <button class="ye-tab px-5 py-2.5 text-sm font-semibold border-b-[3px] border-[#7b5cff] text-[#7b5cff] -mb-px transition"
                    data-ye-tab="thirteenth" onclick="switchYeTab('thirteenth')">
                13th Month Pay
            </button>
            <button class="ye-tab px-5 py-2.5 text-sm font-semibold border-b-[3px] border-transparent text-gray-400 -mb-px transition"
                    data-ye-tab="annualization" onclick="switchYeTab('annualization')">
                Annualization
            </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             13TH MONTH TAB
        ════════════════════════════════════════════════════════════ -->
        <div id="yeTab-thirteenth" class="ye-tab-content flex flex-col gap-3 flex-1 min-h-0">

            <div class="flex justify-between items-center">
                <p class="text-xs text-gray-400">
                    Formula: <strong class="text-gray-600">Total Net Basic Salary ÷ 12</strong>
                    &nbsp;|&nbsp; Tax-exempt up to
                    <strong id="exemptLimitDisplay" class="text-gray-600">₱90,000</strong>
                    (TRAIN Law)
                </p>
                <button onclick="export13thMonthCSV()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition">
                    Export CSV
                </button>
            </div>

            <!-- Header row -->
            <div class="grid grid-cols-[2fr_0.6fr_1.4fr_1.4fr_1.4fr_1.4fr_1.1fr_1.1fr_1fr]
                        bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg
                        text-sm font-bold overflow-hidden">
                <div class="p-4">Employee</div>
                <div class="p-4 text-right">Periods</div>
                <div class="p-4 text-right">Basic Salary</div>
                <div class="p-4 text-right text-red-500">Less: Absences/<br>Tardiness</div>
                <div class="p-4 text-right">Net Total Basic</div>
                <div class="p-4 text-right text-[#7b5cff]">13th Month Pay<br><span class="font-normal text-[11px] text-gray-400">Net Basic ÷ 12</span></div>
                <div class="p-4 text-right">Tax-Exempt</div>
                <div class="p-4 text-right">Taxable Excess</div>
                <div class="p-4 text-center">Action</div>
            </div>

            <!-- Body -->
            <div id="tbody13th" class="flex-1 min-h-0 overflow-y-auto text-gray-700 text-sm scroll-bar">
                <div class="flex items-center justify-center py-16 text-gray-400 text-sm">
                    Select a year and click <strong class="mx-1">Compute</strong> to generate results.
                </div>
            </div>

            <!-- Footer totals -->
            <div id="tfoot13th" class="hidden grid grid-cols-[2fr_0.6fr_1.4fr_1.4fr_1.4fr_1.4fr_1.1fr_1.1fr_1fr]
                                       bg-gray-50 border-t-2 border-gray-200 rounded-b-lg text-sm font-bold text-gray-700">
                <div class="p-4">Total</div>
                <div id="tot13Periods" class="p-4 text-right"></div>
                <div id="tot13Gross"   class="p-4 text-right"></div>
                <div id="tot13Deduct"  class="p-4 text-right text-red-500"></div>
                <div id="tot13Basic"   class="p-4 text-right"></div>
                <div id="tot13Amount"  class="p-4 text-right text-[#7b5cff]"></div>
                <div id="tot13Exempt"  class="p-4 text-right text-green-600"></div>
                <div id="tot13Taxable" class="p-4 text-right"></div>
                <div class="p-4"></div>
            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════
             ANNUALIZATION TAB
        ════════════════════════════════════════════════════════════ -->
        <div id="yeTab-annualization" class="ye-tab-content hidden flex flex-col gap-3 flex-1 min-h-0">

            <div class="flex justify-between items-center">
                <p class="text-xs text-gray-400 flex items-center gap-2">
                    Year-end BIR annualization.
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block w-3 h-3 bg-red-100 rounded-sm border border-red-200"></span>
                        Additional withholding
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block w-3 h-3 bg-green-100 rounded-sm border border-green-200"></span>
                        Refund to employee
                    </span>
                </p>
                <button onclick="exportAnnualizationCSV()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition">
                    Export CSV
                </button>
            </div>

            <!-- Header row -->
            <div class="grid grid-cols-[2fr_1.4fr_1.4fr_1.4fr_1.2fr_1.2fr_1.8fr_1fr]
                        bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg
                        text-sm font-bold overflow-hidden">
                <div class="p-4">Employee</div>
                <div class="p-4 text-right">Annual Taxable<br>Compensation</div>
                <div class="p-4 text-right">13th Mo.<br>Taxable Excess</div>
                <div class="p-4 text-right">Total Annual<br>Taxable</div>
                <div class="p-4 text-right">Annual<br>Tax Due</div>
                <div class="p-4 text-right">Tax Withheld<br>YTD</div>
                <div class="p-4 text-right">Year-End Adjustment</div>
                <div class="p-4 text-center">Action</div>
            </div>

            <!-- Body -->
            <div id="tbodyAnn" class="flex-1 min-h-0 overflow-y-auto text-gray-700 text-sm scroll-bar">
                <div class="flex items-center justify-center py-16 text-gray-400 text-sm">
                    Select a year and click <strong class="mx-1">Compute</strong> to generate results.
                </div>
            </div>

            <!-- Footer totals -->
            <div id="tfootAnn" class="hidden grid grid-cols-[2fr_1.4fr_1.4fr_1.4fr_1.2fr_1.2fr_1.8fr_1fr]
                                       bg-gray-50 border-t-2 border-gray-200 rounded-b-lg text-sm font-bold text-gray-700">
                <div class="p-4">Total</div>
                <div id="totAnnTaxComp"  class="p-4 text-right"></div>
                <div id="totAnn13thTax"  class="p-4 text-right"></div>
                <div id="totAnnAnnual"   class="p-4 text-right"></div>
                <div id="totAnnTaxDue"   class="p-4 text-right"></div>
                <div id="totAnnWithheld" class="p-4 text-right"></div>
                <div id="totAnnAdj"      class="p-4 text-right"></div>
                <div class="p-4"></div>
            </div>

        </div>

    </div><!-- /card -->

</div>

<script>
(function () {

    function money(v) {
        return Number(v || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function sum(arr, key) {
        return arr.reduce((a, r) => a + Number(r[key] || 0), 0);
    }

    function htmlEsc(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Tab switching ─────────────────────────────────────────────────
    window.switchYeTab = function (tab) {
        document.querySelectorAll('.ye-tab').forEach(btn => {
            const active = btn.dataset.yeTab === tab;
            btn.classList.toggle('border-[#7b5cff]', active);
            btn.classList.toggle('text-[#7b5cff]', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-400', !active);
        });
        document.querySelectorAll('.ye-tab-content').forEach(el => {
            el.classList.toggle('hidden', el.id !== `yeTab-${tab}`);
            el.classList.toggle('flex', el.id === `yeTab-${tab}`);
        });
    };

    // ── Compute ───────────────────────────────────────────────────────
    window.computeYearEnd = function () {
        const year = parseInt(document.getElementById('yearEndYear').value);
        if (!year || year < 2000 || year > 2100) {
            showToast('Please enter a valid year (2000–2100).', 'warning');
            return;
        }

        const btn     = document.getElementById('btnComputeYearEnd');
        const spinner = document.getElementById('yearEndSpinner');
        const status  = document.getElementById('yearEndStatus');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        status.textContent = 'Computing…';

        fetch('<?= site_url('client_portal/payroll/computeYearEnd') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
            body: JSON.stringify({ year })
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showToast(data.error, 'error'); return; }

            if (data.exempt_limit) {
                document.getElementById('exemptLimitDisplay').textContent =
                    '₱' + money(data.exempt_limit);
            }

            render13thTable(data.thirteenth_month || []);
            renderAnnTable(data.annualization || []);

            status.textContent = '✓ ' + (data.message || `Computed ${data.thirteenth_month?.length ?? 0} employees for ${year}.`);
        })
        .catch(err => {
            console.error(err);
            status.textContent = 'Error — check console.';
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('hidden');
        });
    };

    // ── Render 13th month table ───────────────────────────────────────
    const COLS_13 = 'grid-cols-[2fr_0.6fr_1.4fr_1.4fr_1.4fr_1.4fr_1.1fr_1.1fr_1fr]';

    function render13thTable(rows) {
        const tbody = document.getElementById('tbody13th');
        const tfoot = document.getElementById('tfoot13th');

        if (!rows.length) {
            tbody.innerHTML = `<div class="flex items-center justify-center py-16 text-gray-400 text-sm">No payroll transactions found for this year.</div>`;
            tfoot.classList.add('hidden');
            return;
        }

        const year13 = document.getElementById('yearEndYear').value;

        tbody.innerHTML = rows.map(r => {
            const hasDeduct = Number(r.total_absent_undertime) > 0;
            return `
            <div class="grid ${COLS_13} items-center border-b hover:bg-gray-50 transition-colors">
                <div class="p-4 font-medium truncate">${htmlEsc(r.employee_name)}</div>
                <div class="p-4 text-right text-gray-500">${r.periods_count}</div>
                <div class="p-4 text-right">₱${money(r.total_gross_basic)}</div>
                <div class="p-4 text-right ${hasDeduct ? 'text-red-500 font-medium' : 'text-gray-400'}">
                    ${hasDeduct ? '(₱' + money(r.total_absent_undertime) + ')' : '—'}
                </div>
                <div class="p-4 text-right font-semibold">₱${money(r.total_basic_earned)}</div>
                <div class="p-4 text-right font-bold text-[#7b5cff]">₱${money(r.thirteenth_month_amount)}</div>
                <div class="p-4 text-right text-green-600">₱${money(r.tax_exempt_amount)}</div>
                <div class="p-4 text-right ${Number(r.taxable_amount) > 0 ? 'text-red-500 font-medium' : 'text-gray-400'}">
                    ${Number(r.taxable_amount) > 0 ? '₱' + money(r.taxable_amount) : '—'}
                </div>
                <div class="p-4 flex justify-center">
                    <button onclick="applyToPayroll('13th',${r.employee_id},${JSON.stringify(htmlEsc(r.employee_name))},${year13},${r.thirteenth_month_amount},false)"
                            class="bg-[#7b5cff] hover:bg-[#6a4de0] text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap">
                        Apply
                    </button>
                </div>
            </div>`;
        }).join('');

        document.getElementById('tot13Periods').textContent = sum(rows, 'periods_count');
        document.getElementById('tot13Gross').textContent   = '₱' + money(sum(rows, 'total_gross_basic'));
        const totalDeduct = sum(rows, 'total_absent_undertime');
        document.getElementById('tot13Deduct').textContent  = totalDeduct > 0 ? '(₱' + money(totalDeduct) + ')' : '—';
        document.getElementById('tot13Basic').textContent   = '₱' + money(sum(rows, 'total_basic_earned'));
        document.getElementById('tot13Amount').textContent  = '₱' + money(sum(rows, 'thirteenth_month_amount'));
        document.getElementById('tot13Exempt').textContent  = '₱' + money(sum(rows, 'tax_exempt_amount'));
        document.getElementById('tot13Taxable').textContent = '₱' + money(sum(rows, 'taxable_amount'));
        tfoot.classList.remove('hidden');
    }

    // ── Render annualization table ────────────────────────────────────
    const COLS_ANN = 'grid-cols-[2fr_1.4fr_1.4fr_1.4fr_1.2fr_1.2fr_1.8fr_1fr]';

    function renderAnnTable(rows) {
        const tbody = document.getElementById('tbodyAnn');
        const tfoot = document.getElementById('tfootAnn');

        if (!rows.length) {
            tbody.innerHTML = `<div class="flex items-center justify-center py-16 text-gray-400 text-sm">No payroll transactions found for this year.</div>`;
            tfoot.classList.add('hidden');
            return;
        }

        const yearAnn = document.getElementById('yearEndYear').value;

        tbody.innerHTML = rows.map(r => {
            const adj   = Number(r.year_end_adjustment);
            const isPos = adj > 0;
            const isNeg = adj < 0;

            const adjBg    = isPos ? 'bg-red-50'   : (isNeg ? 'bg-green-50'  : '');
            const adjColor = isPos ? 'text-red-600' : (isNeg ? 'text-green-600' : 'text-gray-400');
            const adjLabel = isPos
                ? `+₱${money(adj)} (additional withholding)`
                : (isNeg ? `−₱${money(Math.abs(adj))} (refund)` : '₱0.00 (balanced)');

            const actionBtn = adj !== 0
                ? `<button onclick="applyToPayroll('annualization',${r.employee_id},${JSON.stringify(htmlEsc(r.employee_name))},${yearAnn},${Math.abs(adj)},${isNeg})"
                           class="${isNeg ? 'bg-green-600 hover:bg-green-700' : 'bg-red-500 hover:bg-red-600'} text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap">
                       Apply
                   </button>`
                : `<span class="text-gray-300 text-xs">Balanced</span>`;

            return `
            <div class="grid ${COLS_ANN} items-center border-b hover:bg-gray-50 transition-colors">
                <div class="p-4 font-medium truncate">${htmlEsc(r.employee_name)}</div>
                <div class="p-4 text-right">₱${money(r.total_taxable_compensation)}</div>
                <div class="p-4 text-right ${Number(r.thirteenth_month_taxable) > 0 ? 'text-red-500 font-medium' : 'text-gray-400'}">
                    ${Number(r.thirteenth_month_taxable) > 0 ? '₱' + money(r.thirteenth_month_taxable) : '—'}
                </div>
                <div class="p-4 text-right font-semibold">₱${money(r.annual_taxable_income)}</div>
                <div class="p-4 text-right">₱${money(r.annual_tax_due)}</div>
                <div class="p-4 text-right">₱${money(r.total_tax_withheld)}</div>
                <div class="p-4 text-right font-semibold ${adjColor} ${adjBg} rounded">${adjLabel}</div>
                <div class="p-4 flex justify-center">${actionBtn}</div>
            </div>`;
        }).join('');

        document.getElementById('totAnnTaxComp').textContent  = '₱' + money(sum(rows, 'total_taxable_compensation'));
        document.getElementById('totAnn13thTax').textContent  = '₱' + money(sum(rows, 'thirteenth_month_taxable'));
        document.getElementById('totAnnAnnual').textContent   = '₱' + money(sum(rows, 'annual_taxable_income'));
        document.getElementById('totAnnTaxDue').textContent   = '₱' + money(sum(rows, 'annual_tax_due'));
        document.getElementById('totAnnWithheld').textContent = '₱' + money(sum(rows, 'total_tax_withheld'));
        const totalAdj = sum(rows, 'year_end_adjustment');
        document.getElementById('totAnnAdj').textContent = (totalAdj >= 0 ? '+' : '−') + '₱' + money(Math.abs(totalAdj));
        tfoot.classList.remove('hidden');
    }

    // ── CSV exports ───────────────────────────────────────────────────
    window.export13thMonthCSV = function () {
        const rows = collectTableRows('tbody13th', 8);
        if (!rows.length) { showToast('Nothing to export. Click Compute first.', 'warning'); return; }
        const year   = document.getElementById('yearEndYear').value;
        const header = ['Employee','Periods','Basic Salary','Less: Absences/Tardiness','Net Total Basic','13th Month Pay','Tax-Exempt','Taxable Excess'];
        downloadCSV(`13th_month_${year}.csv`, [header, ...rows]);
    };

    window.exportAnnualizationCSV = function () {
        const rows = collectTableRows('tbodyAnn', 7);
        if (!rows.length) { showToast('Nothing to export. Click Compute first.', 'warning'); return; }
        const year   = document.getElementById('yearEndYear').value;
        const header = ['Employee','Annual Taxable Comp','13th Mo. Taxable Excess','Total Annual Taxable','Annual Tax Due','Tax Withheld YTD','Year-End Adjustment'];
        downloadCSV(`annualization_${year}.csv`, [header, ...rows]);
    };

    function collectTableRows(containerId, cols) {
        const container = document.getElementById(containerId);
        const out = [];
        container.querySelectorAll('[class*="grid"]').forEach(row => {
            const cells = row.querySelectorAll(':scope > div');
            if (cells.length < cols) return;
            out.push(Array.from(cells).slice(0, cols).map(td => td.textContent.trim().replace(/,/g, '')));
        });
        return out;
    }

    function downloadCSV(filename, rows) {
        const csv  = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = filename;
        a.click();
    }

    // ── Auto-load saved records for current year on panel reveal ──────
    window._loadYearEndRecords = function (year) {
        fetch(`<?= site_url('client_portal/payroll/getYearEndRecords') ?>?year=${year}`)
            .then(r => r.json())
            .then(data => {
                if (data.thirteenth_month?.length) render13thTable(data.thirteenth_month);
                if (data.annualization?.length)    renderAnnTable(data.annualization);
                if (data.exempt_limit) {
                    document.getElementById('exemptLimitDisplay').textContent =
                        '₱' + money(data.exempt_limit);
                }
            })
            .catch(() => {});
    };

    // ── Apply to Payroll ──────────────────────────────────────────────
    window.applyToPayroll = async function (type, empId, empName, year, amount, isRefund) {
        if (typeof window.activatePayrollTab === 'function') {
            window.activatePayrollTab('generate-payslip');
        }
        await new Promise(r => setTimeout(r, 80));
        if (typeof window.applyYearEndToPayroll === 'function') {
            await window.applyYearEndToPayroll(type, { empId, empName, year, amount, isRefund });
        } else {
            showToast('Could not reach the payroll form. Please switch to the Generate Payslip tab manually.', 'error');
        }
    };

})();
</script>
