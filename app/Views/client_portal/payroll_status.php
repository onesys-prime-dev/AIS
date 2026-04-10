<div class="h-full flex flex-col min-h-0 overflow-hidden">

    <div class="flex justify-between items-center my-4">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-3">

            <!-- Month Picker -->
            <div class="month-picker flex items-center h-12 bg-white border border-gray-200 rounded-lg shadow-sm px-3" id="monthPickerContainer">
                <button type="button" class="month-picker-icon mr-2" aria-label="Open month picker" onclick="openMonthPicker()">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                    </svg>
                </button>
                <button type="button" class="month-picker-label" onclick="openMonthPicker()" aria-label="Select month">
                    <?= date('F Y', strtotime($selected_month . '-01')) ?>
                </button>
                <input type="hidden" id="monthPicker" value="<?= $selected_month ?>">

                <div class="month-picker-modal" id="monthPickerModal" role="dialog" aria-label="Select month">
                    <div class="month-picker-modal-header">
                        <button type="button" class="month-picker-nav" aria-label="Previous year" onclick="shiftPickerYear(-1)">‹</button>
                        <strong id="monthPickerYearLabel"></strong>
                        <button type="button" class="month-picker-nav" aria-label="Next year" onclick="shiftPickerYear(1)">›</button>
                    </div>
                    <div class="month-picker-grid" id="monthPickerGrid"></div>
                </div>
            </div>

            <!-- New Payroll Button -->
            <button 
                data-modal-open="payrollModal"
                class="h-12 bg-[#FF2D2D] hover:bg-[#dc2626] text-white px-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(239,68,68,0.3)] flex items-center gap-2 transition hover:translate-y-[-2px]">

                <img src="<?php echo base_url('icons/add.png'); ?>" class="w-5 h-5" alt="Add"/>
                <span class="font-medium">New Payroll Entry</span>
            </button>

        </div>
    </div>

    <?= view('components/crud_table', [
        'title' => date('F Y', strtotime($selected_month . '-01')) . ' Payroll',
        'tableId' => 'payrollTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr]',
        'columns' => [
            'Employee Name',
            'Phone',
            'Daily Rate',
            'DST',
            'Currency',
            'Status',
            'Action'
        ]
    ]) ?>

</div>

<style>
    .month-picker {
        position: relative;
    }

    .month-picker-icon {
        background: transparent;
        border: 0;
        padding: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .month-picker-label {
        background: transparent;
        border: 0;
        padding: 0;
        color: #374151;
        font-size: 0.95rem;
        line-height: 1.5rem;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        min-width: 110px;
        text-align: center;
    }

    .month-picker-modal {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        width: 250px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);
        padding: 10px;
        z-index: 40;
        display: none;
    }

    .month-picker-modal.open {
        display: block;
    }

    .month-picker-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        padding: 2px 2px 8px;
        border-bottom: 1px solid #f1f5f9;
    }

    .month-picker-modal-header strong {
        color: #111827;
        font-size: 0.95rem;
    }

    .month-picker-nav {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 8px;
        background: #f3f4f6;
        color: #374151;
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
    }

    .month-picker-nav:hover {
        background: #e5e7eb;
    }

    .month-picker-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .month-picker-cell {
        border: 0;
        border-radius: 10px;
        padding: 8px 6px;
        background: #f3f4f6;
        color: #374151;
        font-size: 0.82rem;
        cursor: pointer;
        text-align: center;
    }

    .month-picker-cell:hover {
        background: #e2e8f0;
    }

    .month-picker-cell.active {
        background: #2563eb;
        color: #ffffff;
    }
</style>

<script>
    const MONTH_NAMES_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let pickerYear = null;

    function formatMonthLabel(value) {
        const [year, month] = (value || '').split('-');
        if (!year || !month) return '';
        const date = new Date(Number(year), Number(month) - 1, 1);
        return date.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    }

    function setPickerLabel(value) {
        const label = document.querySelector('.month-picker-label');
        if (!label) return;
        label.textContent = formatMonthLabel(value);
    }

    function renderMonthGrid() {
        const grid = document.getElementById('monthPickerGrid');
        const yearLabel = document.getElementById('monthPickerYearLabel');
        const input = document.getElementById('monthPicker');
        if (!grid || !yearLabel || !input || pickerYear == null) return;

        const currentValue = input.value || '';
        const [selectedYear, selectedMonth] = currentValue.split('-');
        yearLabel.textContent = String(pickerYear);

        grid.innerHTML = MONTH_NAMES_SHORT.map((name, idx) => {
            const monthNum = String(idx + 1).padStart(2, '0');
            const isActive = Number(selectedYear) === pickerYear && selectedMonth === monthNum;
            return `<button type="button" class="month-picker-cell${isActive ? ' active' : ''}" onclick="selectPickerMonth(${idx + 1})">${name}</button>`;
        }).join('');
    }

    function openMonthPicker() {
        const modal = document.getElementById('monthPickerModal');
        const input = document.getElementById('monthPicker');
        if (!modal || !input) return;

        const [year] = (input.value || '').split('-');
        pickerYear = Number(year) || new Date().getFullYear();
        renderMonthGrid();
        modal.classList.add('open');
    }

    function closeMonthPicker() {
        const modal = document.getElementById('monthPickerModal');
        if (!modal) return;
        modal.classList.remove('open');
    }

    function shiftPickerYear(offset) {
        if (pickerYear == null) {
            pickerYear = new Date().getFullYear();
        }
        pickerYear += offset;
        renderMonthGrid();
    }

    function selectPickerMonth(monthNumber) {
        const input = document.getElementById('monthPicker');
        if (!input || pickerYear == null) return;

        const month = String(monthNumber).padStart(2, '0');
        input.value = `${pickerYear}-${month}`;
        setPickerLabel(input.value);

        input.dispatchEvent(new Event('change', { bubbles: true }));
        closeMonthPicker();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('monthPicker');
        const container = document.getElementById('monthPickerContainer');
        if (!input || !container) return;

        setPickerLabel(input.value);

        document.addEventListener('click', function (event) {
            if (!container.contains(event.target)) {
                closeMonthPicker();
            }
        });
    });
</script>