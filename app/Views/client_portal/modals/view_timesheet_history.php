<div class="view-timesheet-modal" data-modal="viewTimesheetHistoryModal" >
    <div class="history-search-wrap">
        <span class="search-icon-prefix">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd"
                d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5
                6.75 6.75 0 0 0 0-13.5ZM2.25 10.5
                a8.25 8.25 0 1 1 14.59 5.28l4.69
                4.69a.75.75 0 1 1-1.06 1.06l-4.69
                -4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                clip-rule="evenodd"/>
            </svg>
        </span>

        <input
            type="text"
            placeholder="Search..."
            class="crud-search history-search-input"
        >
    </div>

    <?= view('components/table', [
        'title' => 'Timesheet History',
        'tableId' => 'timesheetHistoryTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1fr]',
        'columns' => [
            'Date Range',
            'Total Hours',
            'Action'
        ]
    ]) ?>
</div>

<style>
    .history-search-wrap {
        position: relative;
    }

    .history-search-input {
        width: 100%;
        background: #e5e7eb;
        color: #374151;
        font-size: 0.875rem;
        padding: 0.75rem 1.25rem 0.75rem 2.5rem;
        border-radius: 0.5rem;
        border: 1px solid #9E9E9E;
        outline: none;
    }

    .search-icon-prefix {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        display: flex;
        align-items: center;
        padding-left: 0.75rem;
        color: #475569;
        pointer-events: none;
    }

    .view-timesheet-modal{
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
</style>