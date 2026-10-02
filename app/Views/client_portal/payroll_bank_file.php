<?php
// Distinct periods present in this list, newest first. A bank disbursement
// file must never mix two pay periods together, so this tab is always
// scoped to exactly one selected period — never "everything on screen".
$periodOptions = [];
foreach ($payslips ?? [] as $ps) {
    $key = ($ps['period_start'] ?? '') . '|' . ($ps['period_end'] ?? '');
    if ($key === '|' || isset($periodOptions[$key])) continue;
    $periodOptions[$key] = $key;
}
krsort($periodOptions);
?>
<style>
    /* No disabled: variant exists in this app's precompiled Tailwind build,
       so the disabled look is handled with a plain native selector instead. */
    #bfExportBtn:disabled { background: #d1d5db; cursor: not-allowed; }
</style>
<div class="flex flex-col h-full gap-4">

    <div class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 shrink-0 rounded-full bg-green-600 text-white flex items-center justify-center">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10 12 4l9 6"/><path d="M4 10h16v2H4z"/><path d="M5 12v7"/><path d="M9 12v7"/><path d="M15 12v7"/><path d="M19 12v7"/><path d="M3 21h18"/></svg>
            </div>
            <div>
                <div class="font-semibold text-gray-800">Bank Disbursement File</div>
                <div class="text-xs text-gray-500">Review the payout list below, then export it to send to the bank.</div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <select id="bfPeriod" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="">Select a pay period…</option>
                <?php foreach ($periodOptions as $key): [$s, $e] = explode('|', $key); ?>
                <option value="<?= esc($key) ?>">
                    <?= !empty($s) ? date('M d, Y', strtotime($s)) : '?' ?> &ndash; <?= !empty($e) ? date('M d, Y', strtotime($e)) : '?' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button id="bfExportBtn" onclick="exportBankFile()" disabled
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap inline-flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19.5h16"/></svg>
                Export Bank File
            </button>
        </div>
    </div>

    <div id="bfSummary" class="text-sm text-gray-500 hidden"></div>

    <!-- Header row — grid-template-columns set via inline style, not a
         Tailwind arbitrary class: public/css/main.css is a precompiled
         build (no Node/npm available to rebuild it here), so a brand-new
         grid-cols-[...] value would never actually apply. -->
    <div class="grid bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-bold"
        style="grid-template-columns: 0.5fr 2fr 1.6fr 1.6fr 1fr 1.2fr 1fr;">
        <div class="p-4">#</div>
        <div class="p-4">Employee</div>
        <div class="p-4">Bank</div>
        <div class="p-4">Account Number</div>
        <div class="p-4">SWIFT</div>
        <div class="p-4 text-right">Net Pay</div>
        <div class="p-4 text-center">Status</div>
    </div>

    <!-- Body -->
    <div id="bfBody" class="flex-1 min-h-0 overflow-y-auto text-gray-700 text-sm scroll-bar">
        <div class="flex items-center justify-center py-16 text-gray-400 text-sm">
            Select a pay period above to review its payout list.
        </div>
    </div>

</div>

<script>
(function () {
    // Same $payslips rows the History tab uses (see PayrollController::payrollView()),
    // scoped down to exactly one period at a time — never trusted as "everything
    // currently rendered", since that's how a bank file could end up mixing periods.
    const ALL_PAYSLIPS = <?= json_encode(array_map(function ($ps) {
        return [
            'period_key'    => ($ps['period_start'] ?? '') . '|' . ($ps['period_end'] ?? ''),
            'employee_name' => (string) ($ps['employee_name'] ?? ''),
            'bank'          => (string) ($ps['bank'] ?? ''),
            'account_number'=> (string) ($ps['account_number'] ?? ''),
            'swift_code'    => (string) ($ps['swift_code'] ?? ''),
            'net_pay'       => (float) ($ps['net_pay'] ?? 0),
        ];
    }, $payslips ?? []), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    const periodSelect = document.getElementById('bfPeriod');
    const body = document.getElementById('bfBody');
    const summary = document.getElementById('bfSummary');
    const exportBtn = document.getElementById('bfExportBtn');
    const esc = s => String(s || '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const money = v => '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let currentRows = [];

    function renderRows() {
        const period = periodSelect.value;
        if (!period) {
            body.innerHTML = '<div class="flex items-center justify-center py-16 text-gray-400 text-sm">Select a pay period above to review its payout list.</div>';
            summary.classList.add('hidden');
            exportBtn.disabled = true;
            currentRows = [];
            return;
        }

        currentRows = ALL_PAYSLIPS.filter(r => r.period_key === period);

        if (!currentRows.length) {
            body.innerHTML = '<div class="flex items-center justify-center py-16 text-gray-400 text-sm">No payslips found for that period.</div>';
            summary.classList.add('hidden');
            exportBtn.disabled = true;
            return;
        }

        let total = 0, missing = 0;
        body.innerHTML = currentRows.map((r, i) => {
            const ready = !!(r.bank && r.account_number);
            if (!ready) missing++;
            total += r.net_pay;
            const statusBadge = ready
                ? '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-semibold">&#10003; Ready</span>'
                : '<span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-semibold">&#9888; Missing Info</span>';
            return `<div class="grid items-center border-b ${ready ? '' : 'bg-red-50'} hover:bg-gray-50 transition-colors" style="grid-template-columns: 0.5fr 2fr 1.6fr 1.6fr 1fr 1.2fr 1fr;">
                <div class="p-4 text-gray-400">${i + 1}</div>
                <div class="p-4 font-medium truncate">${esc(r.employee_name) || '<span class="text-gray-300">—</span>'}</div>
                <div class="p-4 truncate">${esc(r.bank) || '<span class="text-red-400">Missing</span>'}</div>
                <div class="p-4 truncate">${esc(r.account_number) || '<span class="text-red-400">Missing</span>'}</div>
                <div class="p-4 truncate text-gray-500">${esc(r.swift_code) || '—'}</div>
                <div class="p-4 text-right font-semibold">${money(r.net_pay)}</div>
                <div class="p-4 text-center">${statusBadge}</div>
            </div>`;
        }).join('');

        summary.classList.remove('hidden');
        summary.innerHTML = `<strong>${currentRows.length}</strong> employee(s) &middot; total <strong>${money(total)}</strong>`
            + (missing ? ` &middot; <span class="text-red-600 font-semibold">${missing} missing bank details</span>` : '');
        exportBtn.disabled = false;
    }

    periodSelect.addEventListener('change', renderRows);

    window.exportBankFile = function () {
        if (!currentRows.length) return;
        const escCsv = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
        const headers = ['Employee Name', 'Bank', 'Account Number', 'SWIFT Code', 'Net Pay'];
        const missingBank = [];
        const csvRows = currentRows.map(r => {
            if (!r.bank || !r.account_number) missingBank.push(r.employee_name || 'Unknown employee');
            return [r.employee_name, r.bank, r.account_number, r.swift_code, r.net_pay.toFixed(2)];
        });
        const csv = [headers.join(','), ...csvRows.map(r => r.map(escCsv).join(','))].join('\n');
        const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const [pStart, pEnd] = periodSelect.value.split('|');
        a.href = url;
        a.download = `bank_file_${pStart}_to_${pEnd}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        if (missingBank.length && typeof showToast === 'function') {
            showToast(missingBank.length + ' employee(s) have no bank details on file — check the exported file.', 'warning');
        } else if (typeof showToast === 'function') {
            showToast('Bank file exported for ' + currentRows.length + ' employee(s).', 'success');
        }
    };
})();
</script>
