<div id="yearEndPanel" style="font-family:'Segoe UI',Arial,sans-serif;">

    <!-- ── Header / Controls ───────────────────────────────────────── -->
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:4px;">Reference Year</label>
            <input type="number" id="yearEndYear" value="<?= date('Y') ?>"
                   min="2000" max="2100"
                   style="background:#e9e9e9;border:1px solid #cfcfcf;height:40px;border-radius:8px;
                          padding:8px 12px;font-size:14px;width:120px;">
        </div>
        <div style="padding-top:18px;">
            <button id="btnComputeYearEnd" onclick="computeYearEnd()"
                    style="background:#7b5cff;color:#fff;border:none;border-radius:8px;
                           height:40px;padding:0 24px;font-size:14px;font-weight:600;
                           cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="yearEndBtnText">Compute</span>
                <span id="yearEndSpinner" style="display:none;">&#8987;</span>
            </button>
        </div>
        <div id="yearEndStatus" style="padding-top:18px;font-size:13px;color:#888;"></div>
    </div>

    <!-- ── Inner tabs ──────────────────────────────────────────────── -->
    <div style="display:flex;border-bottom:2px solid #e5e7eb;margin-bottom:20px;gap:4px;">
        <button class="ye-tab active" data-ye-tab="thirteenth"
                onclick="switchYeTab('thirteenth')"
                style="padding:10px 20px;font-size:13px;font-weight:600;border:none;
                       background:none;cursor:pointer;color:#7b5cff;
                       border-bottom:3px solid #7b5cff;margin-bottom:-2px;">
            13th Month Pay
        </button>
        <button class="ye-tab" data-ye-tab="annualization"
                onclick="switchYeTab('annualization')"
                style="padding:10px 20px;font-size:13px;font-weight:600;border:none;
                       background:none;cursor:pointer;color:#888;border-bottom:3px solid transparent;
                       margin-bottom:-2px;">
            Annualization
        </button>
    </div>

    <!-- ── 13th Month Tab ──────────────────────────────────────────── -->
    <div id="yeTab-thirteenth" class="ye-tab-content">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <p style="font-size:12px;color:#888;margin:0;">
                Formula: <strong>Total Basic Salary Earned ÷ 12</strong> &nbsp;|&nbsp;
                Tax-exempt up to <strong id="exemptLimitDisplay">₱90,000</strong> (TRAIN Law).
            </p>
            <button onclick="export13thMonthCSV()"
                    style="background:#28a745;color:#fff;border:none;border-radius:6px;
                           padding:6px 16px;font-size:12px;font-weight:600;cursor:pointer;">
                Export CSV
            </button>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;" id="table13th">
                <thead>
                    <tr style="background:#f3f4f6;text-align:left;">
                        <th style="padding:10px 12px;font-weight:600;color:#374151;white-space:nowrap;">Employee</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Payroll Periods</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Total Basic Earned</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">13th Month Pay</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Tax-Exempt</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Taxable Excess</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;white-space:nowrap;"></th>
                    </tr>
                </thead>
                <tbody id="tbody13th">
                    <tr>
                        <td colspan="7" style="padding:32px;text-align:center;color:#aaa;font-size:13px;">
                            Select a year and click <strong>Compute</strong> to generate results.
                        </td>
                    </tr>
                </tbody>
                <tfoot id="tfoot13th" style="display:none;">
                    <tr style="background:#f9fafb;font-weight:700;border-top:2px solid #e5e7eb;">
                        <td style="padding:10px 12px;">Total</td>
                        <td id="tot13Periods"  style="padding:10px 12px;text-align:right;"></td>
                        <td id="tot13Basic"    style="padding:10px 12px;text-align:right;"></td>
                        <td id="tot13Amount"   style="padding:10px 12px;text-align:right;"></td>
                        <td id="tot13Exempt"   style="padding:10px 12px;text-align:right;"></td>
                        <td id="tot13Taxable"  style="padding:10px 12px;text-align:right;"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ── Annualization Tab ───────────────────────────────────────── -->
    <div id="yeTab-annualization" class="ye-tab-content" style="display:none;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <p style="font-size:12px;color:#888;margin:0;">
                Year-end BIR annualization.
                <span style="display:inline-block;width:10px;height:10px;background:#fee2e2;border-radius:2px;margin-left:8px;vertical-align:middle;"></span>
                Additional withholding &nbsp;
                <span style="display:inline-block;width:10px;height:10px;background:#dcfce7;border-radius:2px;vertical-align:middle;"></span>
                Refund to employee
            </p>
            <button onclick="exportAnnualizationCSV()"
                    style="background:#28a745;color:#fff;border:none;border-radius:6px;
                           padding:6px 16px;font-size:12px;font-weight:600;cursor:pointer;">
                Export CSV
            </button>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;" id="tableAnn">
                <thead>
                    <tr style="background:#f3f4f6;text-align:left;">
                        <th style="padding:10px 12px;font-weight:600;color:#374151;white-space:nowrap;">Employee</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Annual Taxable Compensation</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">13th Mo. Taxable Excess</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Total Annual Taxable</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Annual Tax Due</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Tax Withheld YTD</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;text-align:right;white-space:nowrap;">Year-End Adjustment</th>
                        <th style="padding:10px 12px;font-weight:600;color:#374151;white-space:nowrap;"></th>
                    </tr>
                </thead>
                <tbody id="tbodyAnn">
                    <tr>
                        <td colspan="8" style="padding:32px;text-align:center;color:#aaa;font-size:13px;">
                            Select a year and click <strong>Compute</strong> to generate results.
                        </td>
                    </tr>
                </tbody>
                <tfoot id="tfootAnn" style="display:none;">
                    <tr style="background:#f9fafb;font-weight:700;border-top:2px solid #e5e7eb;">
                        <td style="padding:10px 12px;">Total</td>
                        <td id="totAnnTaxComp"   style="padding:10px 12px;text-align:right;"></td>
                        <td id="totAnn13thTax"   style="padding:10px 12px;text-align:right;"></td>
                        <td id="totAnnAnnual"    style="padding:10px 12px;text-align:right;"></td>
                        <td id="totAnnTaxDue"    style="padding:10px 12px;text-align:right;"></td>
                        <td id="totAnnWithheld"  style="padding:10px 12px;text-align:right;"></td>
                        <td id="totAnnAdj"       style="padding:10px 12px;text-align:right;"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Helpers ──────────────────────────────────────────────────────
    function money(v) {
        return Number(v || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function sum(arr, key) {
        return arr.reduce((a, r) => a + Number(r[key] || 0), 0);
    }

    // ── Tab switching ─────────────────────────────────────────────────
    window.switchYeTab = function (tab) {
        document.querySelectorAll('.ye-tab').forEach(btn => {
            const active = btn.dataset.yeTab === tab;
            btn.style.color       = active ? '#7b5cff' : '#888';
            btn.style.borderBottom = active ? '3px solid #7b5cff' : '3px solid transparent';
        });
        document.querySelectorAll('.ye-tab-content').forEach(el => {
            el.style.display = el.id === `yeTab-${tab}` ? 'block' : 'none';
        });
    };

    // ── Compute ───────────────────────────────────────────────────────
    window.computeYearEnd = function () {
        const year = parseInt(document.getElementById('yearEndYear').value);
        if (!year || year < 2000 || year > 2100) {
            alert('Please enter a valid year (2000–2100).');
            return;
        }

        const btn     = document.getElementById('btnComputeYearEnd');
        const spinner = document.getElementById('yearEndSpinner');
        const status  = document.getElementById('yearEndStatus');

        btn.disabled         = true;
        spinner.style.display = 'inline';
        status.textContent   = 'Computing…';

        fetch('<?= site_url('client_portal/payroll/computeYearEnd') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ year })
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }

            if (data.exempt_limit) {
                document.getElementById('exemptLimitDisplay').textContent =
                    '₱' + money(data.exempt_limit);
            }

            render13thTable(data.thirteenth_month || []);
            renderAnnTable(data.annualization || []);

            const msg = data.message || `Computed ${data.thirteenth_month?.length ?? 0} employees for ${year}.`;
            status.textContent = '✓ ' + msg;
        })
        .catch(err => {
            console.error(err);
            status.textContent = 'Error — check console.';
        })
        .finally(() => {
            btn.disabled          = false;
            spinner.style.display = 'none';
        });
    };

    // ── Render 13th month table ───────────────────────────────────────
    function render13thTable(rows) {
        const tbody = document.getElementById('tbody13th');
        const tfoot = document.getElementById('tfoot13th');

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="padding:32px;text-align:center;color:#aaa;">
                No payroll transactions found for this year.</td></tr>`;
            tfoot.style.display = 'none';
            return;
        }

        const year13 = document.getElementById('yearEndYear').value;
        tbody.innerHTML = rows.map(r => `
            <tr style="border-bottom:1px solid #f3f4f6;transition:background 0.15s;"
                onmouseover="this.style.background='#f9fafb'"
                onmouseout="this.style.background=''">
                <td style="padding:10px 12px;font-weight:500;">${htmlEsc(r.employee_name)}</td>
                <td style="padding:10px 12px;text-align:right;">${r.periods_count}</td>
                <td style="padding:10px 12px;text-align:right;">₱${money(r.total_basic_earned)}</td>
                <td style="padding:10px 12px;text-align:right;font-weight:600;color:#7b5cff;">₱${money(r.thirteenth_month_amount)}</td>
                <td style="padding:10px 12px;text-align:right;color:#16a34a;">₱${money(r.tax_exempt_amount)}</td>
                <td style="padding:10px 12px;text-align:right;color:${Number(r.taxable_amount) > 0 ? '#dc2626' : '#888'};">
                    ${Number(r.taxable_amount) > 0 ? '₱' + money(r.taxable_amount) : '—'}
                </td>
                <td style="padding:10px 12px;">
                    <button onclick="applyToPayroll('13th',${r.employee_id},${JSON.stringify(htmlEsc(r.employee_name))},${year13},${r.thirteenth_month_amount},false)"
                            style="background:#7b5cff;color:#fff;border:none;border-radius:6px;
                                   padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;
                                   white-space:nowrap;">
                        Apply to Payroll
                    </button>
                </td>
            </tr>`).join('');

        document.getElementById('tot13Periods').textContent = sum(rows, 'periods_count');
        document.getElementById('tot13Basic').textContent   = '₱' + money(sum(rows, 'total_basic_earned'));
        document.getElementById('tot13Amount').textContent  = '₱' + money(sum(rows, 'thirteenth_month_amount'));
        document.getElementById('tot13Exempt').textContent  = '₱' + money(sum(rows, 'tax_exempt_amount'));
        document.getElementById('tot13Taxable').textContent = '₱' + money(sum(rows, 'taxable_amount'));
        tfoot.style.display = '';
    }

    // ── Render annualization table ────────────────────────────────────
    function renderAnnTable(rows) {
        const tbody = document.getElementById('tbodyAnn');
        const tfoot = document.getElementById('tfootAnn');

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="7" style="padding:32px;text-align:center;color:#aaa;">
                No payroll transactions found for this year.</td></tr>`;
            tfoot.style.display = 'none';
            return;
        }

        const yearAnn = document.getElementById('yearEndYear').value;
        tbody.innerHTML = rows.map(r => {
            const adj   = Number(r.year_end_adjustment);
            const isPos = adj > 0;
            const isNeg = adj < 0;
            const adjBg = isPos ? '#fee2e2' : (isNeg ? '#dcfce7' : '');
            const adjColor = isPos ? '#dc2626' : (isNeg ? '#16a34a' : '#888');
            const adjLabel = isPos
                ? `+₱${money(adj)} (additional withholding)`
                : (isNeg ? `−₱${money(Math.abs(adj))} (refund)` : '₱0.00 (balanced)');

            return `
            <tr style="border-bottom:1px solid #f3f4f6;transition:background 0.15s;"
                onmouseover="this.style.background='#f9fafb'"
                onmouseout="this.style.background=''">
                <td style="padding:10px 12px;font-weight:500;">${htmlEsc(r.employee_name)}</td>
                <td style="padding:10px 12px;text-align:right;">₱${money(r.total_taxable_compensation)}</td>
                <td style="padding:10px 12px;text-align:right;color:${Number(r.thirteenth_month_taxable) > 0 ? '#dc2626' : '#888'};">
                    ${Number(r.thirteenth_month_taxable) > 0 ? '₱' + money(r.thirteenth_month_taxable) : '—'}
                </td>
                <td style="padding:10px 12px;text-align:right;">₱${money(r.annual_taxable_income)}</td>
                <td style="padding:10px 12px;text-align:right;">₱${money(r.annual_tax_due)}</td>
                <td style="padding:10px 12px;text-align:right;">₱${money(r.total_tax_withheld)}</td>
                <td style="padding:10px 12px;text-align:right;background:${adjBg};color:${adjColor};font-weight:600;border-radius:4px;">
                    ${adjLabel}
                </td>
                <td style="padding:10px 12px;">
                    ${adj !== 0 ? `
                    <button onclick="applyToPayroll('annualization',${r.employee_id},${JSON.stringify(htmlEsc(r.employee_name))},${yearAnn},${Math.abs(adj)},${isNeg})"
                            style="background:${isNeg ? '#16a34a' : '#dc2626'};color:#fff;border:none;
                                   border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;
                                   cursor:pointer;white-space:nowrap;">
                        Apply Adjustment
                    </button>` : '<span style="color:#bbb;font-size:12px;">Balanced</span>'}
                </td>
            </tr>`;
        }).join('');

        document.getElementById('totAnnTaxComp').textContent  = '₱' + money(sum(rows, 'total_taxable_compensation'));
        document.getElementById('totAnn13thTax').textContent  = '₱' + money(sum(rows, 'thirteenth_month_taxable'));
        document.getElementById('totAnnAnnual').textContent   = '₱' + money(sum(rows, 'annual_taxable_income'));
        document.getElementById('totAnnTaxDue').textContent   = '₱' + money(sum(rows, 'annual_tax_due'));
        document.getElementById('totAnnWithheld').textContent = '₱' + money(sum(rows, 'total_tax_withheld'));
        const totalAdj = sum(rows, 'year_end_adjustment');
        document.getElementById('totAnnAdj').textContent = (totalAdj >= 0 ? '+' : '−') + '₱' + money(Math.abs(totalAdj));
        tfoot.style.display = '';
    }

    // ── CSV exports ───────────────────────────────────────────────────
    window.export13thMonthCSV = function () {
        const rows = collectTableRows('tbody13th', 6);
        if (!rows.length) { alert('Nothing to export. Click Compute first.'); return; }
        const year = document.getElementById('yearEndYear').value;
        const header = ['Employee','Payroll Periods','Total Basic Earned','13th Month Pay','Tax-Exempt','Taxable Excess'];
        downloadCSV(`13th_month_${year}.csv`, [header, ...rows]);
    };

    window.exportAnnualizationCSV = function () {
        const rows = collectTableRows('tbodyAnn', 7);
        if (!rows.length) { alert('Nothing to export. Click Compute first.'); return; }
        const year = document.getElementById('yearEndYear').value;
        const header = ['Employee','Annual Taxable Comp','13th Mo. Taxable Excess','Total Annual Taxable','Annual Tax Due','Tax Withheld YTD','Year-End Adjustment'];
        downloadCSV(`annualization_${year}.csv`, [header, ...rows]);
    };

    function collectTableRows(tbodyId, cols) {
        const tbody = document.getElementById(tbodyId);
        const out = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (tds.length < cols) return;
            out.push(Array.from(tds).map(td => td.textContent.trim().replace(/,/g, '')));
        });
        return out;
    }

    function downloadCSV(filename, rows) {
        const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = filename;
        a.click();
    }

    function htmlEsc(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Auto-load saved records for current year on panel reveal
    window._loadYearEndRecords = function (year) {
        fetch(`<?= site_url('client_portal/payroll/getYearEndRecords') ?>?year=${year}`)
            .then(r => r.json())
            .then(data => {
                if (data.thirteenth_month?.length) render13thTable(data.thirteenth_month);
                if (data.annualization?.length)    renderAnnTable(data.annualization);
            })
            .catch(() => {});
    };

    // Trigger load when this tab becomes visible (handled by payroll_main.php tab switch)

    // ── Apply to Payroll ──────────────────────────────────────────────
    // Switches the outer tab to Generate Payslip and pre-fills the relevant field.
    window.applyToPayroll = async function (type, empId, empName, year, amount, isRefund) {
        // Switch the outer payroll tab to "generate-payslip"
        if (typeof window.activatePayrollTab === 'function') {
            window.activatePayrollTab('generate-payslip');
        }

        // Small delay so the generate-payslip panel becomes visible before we write to its fields
        await new Promise(r => setTimeout(r, 80));

        if (typeof window.applyYearEndToPayroll === 'function') {
            await window.applyYearEndToPayroll(type, { empId, empName, year, amount, isRefund });
        } else {
            alert('Could not reach the payroll form. Please switch to the Generate Payslip tab manually.');
        }
    };
})();
</script>
