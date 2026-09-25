<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
    $timesheet = $timesheet ?? [];
    $dailyTimesheet = $dailyTimesheet ?? [];
    $totals = $totals ?? [
        'regular_hours' => 0,
        'ot_hours' => 0,
        'rdot_hours' => 0,
        'tardy_minutes' => 0,
        'night_diff' => 0,
        'unapproved_ot_hours' => 0,
        'undertime_hours' => 0,
        'unapproved_undertime_hours' => 0,
    ];
    $isPayrollAdmin = $isPayrollAdmin ?? false;
?>

<div id="timesheetPageRoot" class="h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8">

    <!-- Page Header (unchanged) -->
    <div class="flex justify-between items-center my-4 flex-wrap gap-3">
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Timesheets</h1>
            <p class="text-gray-500 text-sm">Manage timesheets here.</p>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
            <button type="button"
                data-modal-open="viewDailyTimeLog"
                onclick="openModalById('viewDailyTimeLog')"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.3)] flex items-center gap-2 transition hover:translate-y-[-2px]">
                <img src="<?= base_url('icons/activity.png'); ?>" class="w-7 h-7" alt="Activity Log">
            </button>

            <button type="button"
                data-modal-open="viewTimesheetHistoryModal"
                onclick="openModalById('viewTimesheetHistoryModal')"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.2)] flex items-center gap-2 transition hover:translate-y-[-2px] text-gray-700 text-sm font-medium">
                <img src="<?= base_url('icons/alarms.png'); ?>" class="w-7 h-7" alt="History">
                Past Timesheets
            </button>

            <?php if ($isPayrollAdmin): ?>
            <button type="button"
                data-modal-open="viewEmployeeTimesheetModal"
                onclick="openModalById('viewEmployeeTimesheetModal')"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.2)] flex items-center gap-2 transition hover:translate-y-[-2px] text-gray-700 text-sm font-medium">
                <img src="<?= base_url('icons/activity.png'); ?>" class="w-7 h-7" alt="Review Employee Timesheets">
                Review Employee Timesheets
            </button>

            <button type="button" id="attendanceIssuesBtn" onclick="openAttendanceIssues()"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.2)] flex items-center gap-2 transition hover:translate-y-[-2px] text-gray-700 text-sm font-medium">
                <!-- Flat black line icon, same size/style as the other buttons' icons; badge = records needing attention -->
                <span style="position:relative;display:inline-flex;width:28px;height:28px;flex:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#000" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4.5" y="4" width="15" height="17.5" rx="2.5"/>
                        <path d="M9 4V3.2A1.2 1.2 0 0 1 10.2 2h3.6A1.2 1.2 0 0 1 15 3.2V4"/>
                        <path d="M8.5 13.2l2.6 2.6 4.6-5"/>
                    </svg>
                    <span id="attendanceIssuesBadge" hidden
                        style="position:absolute;top:-9px;right:-7px;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:#dc2626;color:#fff;font-size:11px;font-weight:700;line-height:18px;text-align:center;box-shadow:0 0 0 2px #fff;">0</span>
                </span>
                Attendance Corrections
            </button>
            <?php endif; ?>

            <button type="button"
                data-modal-open="viewPayslipModal"
                onclick="openModalById('viewPayslipModal')"
                class="bg-[#FF2D2D] hover:bg-[#dc2626] text-white px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(239,68,68,0.3)] flex items-center gap-2 transition hover:translate-y-[-2px]">
                <img src="<?= base_url('icons/payslip.png'); ?>" class="w-7 h-7" alt="Payslip">
                View Payslip
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white h-full rounded-2xl shadow-lg border border-gray-400 flex flex-col overflow-hidden">

        <!-- Filter bar -->
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center gap-4 flex-wrap">
            <div class="relative w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search date, remarks…"
                    class="w-full bg-gray-100 text-gray-700 text-sm pl-10 pr-4 py-2.5 rounded-lg outline-none focus:ring-2 focus:ring-purple-400 border border-transparent focus:border-purple-300">
            </div>
            <div class="flex gap-3">
                <select id="sortBy" class="bg-gray-100 text-gray-700 text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="date">Sort: Date</option>
                    <option value="regular_hours">Sort: Regular Hrs</option>
                    <option value="ot_hours">Sort: OT Hrs</option>
                </select>
                <select id="departmentFilter" class="bg-gray-100 text-gray-700 text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="">All Departments</option>
                    <option value="Information Technology">IT</option>
                    <option value="Human Resource">HR</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Marketing">Marketing</option>
                </select>
            </div>
        </div>

        <!-- TABLE — the header/body/footer tables below are all a fixed
             1370px min-width (16 columns). Previously this container just
             clipped anything past the viewport with no way to reach it at
             all on a phone; now it scrolls horizontally as one unit (all
             three tables move together since they're stacked in the same
             scroll container), while the body's own vertical scroll (for
             rows) stays independent, exactly as before. -->
        <div class="flex-1 flex flex-col overflow-x-auto" style="overflow-y:hidden;">

            <!-- HEADER -->
            <table class="min-w-[1370px] w-full text-md border-collapse table-fixed">
                <colgroup>
                    <col class="w-[120px]">
                    <col class="w-[100px]">
                    <col class="w-[100px]">
                    <col class="w-[90px]">
                    <col class="w-[110px]">
                    <col class="w-[90px]">
                    <col class="w-[110px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[100px]">
                    <col class="w-[130px]">
                    <col class="w-[110px]">
                    <col class="w-[150px]">
                    <col class="w-[130px]">
                    <col class="w-[170px]">
                    <col class="w-[90px]">
                </colgroup>

                <thead>
                    <tr class="bg-[#DCDCDC] text-[#737373] border-y border-[#C5C5C5] h-[52px]">

                        <th class="px-4 text-left font-bold">Date</th>
                        <th class="px-4 text-left font-bold">Time In</th>
                        <th class="px-4 text-left font-bold">Time Out</th>
                        <th class="px-4 text-right font-bold" title="Unpaid break deducted from the day">Break (hrs)</th>

                        <th class="px-4 text-right font-bold">Regular Hrs</th>
                        <th class="px-4 text-right font-bold">OT Hrs</th>
                        <th class="px-4 text-right font-bold" title="Worked but not approved — not counted toward Regular or OT">Unapproved OT</th>
                        <th class="px-4 text-right font-bold">RDOT Hrs</th>
                        <th class="px-4 text-right font-bold">Tardy (min)</th>
                        <th class="px-4 text-right font-bold" title="Approved undertime — excused, no pay impact">Undertime</th>
                        <th class="px-4 text-right font-bold" title="Time missed that wasn't approved — counted against you">Unapproved UT</th>
                        <th class="px-4 text-right font-bold">Night Diff</th>

                        <th class="px-4 text-left font-bold">Remarks</th>

                        <th class="px-4 text-left font-bold">
                            Client
                            <span class="ml-1 text-[10px] text-purple-500">✎</span>
                        </th>

                        <th class="px-4 text-left font-bold">
                            Task Performed
                            <span class="ml-1 text-[10px] text-purple-500">✎</span>
                        </th>

                        <th class="px-4 text-center font-bold">Action</th>

                    </tr>
                </thead>
            </table>

            <!-- BODY -->
            <div class="overflow-y-auto hide-scroll-bar flex-1">

                <table class="min-w-[1370px] w-full text-sm border-collapse table-fixed">

                    <colgroup>
                        <col class="w-[120px]">
                        <col class="w-[100px]">
                        <col class="w-[100px]">
                        <col class="w-[90px]">
                        <col class="w-[110px]">
                        <col class="w-[90px]">
                        <col class="w-[110px]">
                        <col class="w-[100px]">
                        <col class="w-[110px]">
                        <col class="w-[100px]">
                        <!-- Unapproved UT — this col was missing here, shifting every
                             column from here on (Night Diff through Action) one slot
                             out of alignment with the header/footer tables above and
                             below, which both have the full 16. That's what showed up
                             as the columns drifting apart while scrolling sideways. -->
                        <col class="w-[130px]">
                        <col class="w-[110px]">
                        <col class="w-[150px]">
                        <col class="w-[130px]">
                        <col class="w-[170px]">
                        <col class="w-[90px]">
                    </colgroup>

                    <tbody id="tableBody">
                        <!-- DATA ROWS -->
                    </tbody>

                </table>

            </div>

            <!-- FOOTER -->
            <table class="min-w-[1370px] w-full text-sm border-collapse table-fixed">

                <colgroup>
                    <col class="w-[120px]">
                    <col class="w-[100px]">
                    <col class="w-[100px]">
                    <col class="w-[90px]">
                    <col class="w-[110px]">
                    <col class="w-[90px]">
                    <col class="w-[110px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[100px]">
                    <col class="w-[130px]">
                    <col class="w-[110px]">
                    <col class="w-[150px]">
                    <col class="w-[130px]">
                    <col class="w-[170px]">
                    <col class="w-[90px]">
                </colgroup>

                <tfoot>

                    <tr class="bg-[#DCDCDC] text-[#666] border-t border-[#C5C5C5] h-[52px] font-semibold">

                        <!-- DATE -->
                        <td></td>

                        <!-- TIME IN -->
                        <td></td>

                        <!-- TIME OUT -->
                        <td class="text-center border-r border-[#C5C5C5] tracking-wide">
                            TOTALS
                        </td>

                        <!-- BREAK -->
                        <td></td>

                        <!-- REGULAR -->
                        <td class="px-4 text-right">
                            <span id="totalRegular">0.00</span>
                        </td>

                        <!-- OT -->
                        <td class="px-4 text-right">
                            <span id="totalOt">0.00</span>
                        </td>

                        <!-- UNAPPROVED OT -->
                        <td class="px-4 text-right">
                            <span id="totalUnapprovedOt">0.00</span>
                        </td>

                        <!-- RDOT -->
                        <td class="px-4 text-right">
                            <span id="totalRdot">0.00</span>
                        </td>

                        <!-- TARDY -->
                        <td class="px-4 text-right">
                            <span id="totalTardy">0</span>
                        </td>

                        <!-- UNDERTIME -->
                        <td class="px-4 text-right">
                            <span id="totalUndertime">0.00</span>
                        </td>

                        <!-- UNAPPROVED UNDERTIME -->
                        <td class="px-4 text-right">
                            <span id="totalUnapprovedUndertime">0.00</span>
                        </td>

                        <!-- NIGHT DIFF -->
                        <td class="px-4 text-right">
                            <span id="totalNightDiff">0.00</span>
                        </td>

                        <!-- REMARKS -->
                        <td></td>

                        <!-- CLIENT -->
                        <td></td>

                        <!-- TASK -->
                        <td></td>

                        <!-- ACTION -->
                        <td></td>

                    </tr>

                </tfoot>

            </table>

        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
    <!-- Daily Time Log Modal -->
    <?php
        $content = view('client_portal/modals/view_daily_time_log', ['dailyTimeLog' => $dailyTimeLog ?? []]);
        echo view('components/view_modal', [
            'id'    => 'viewDailyTimeLog',
            'title' => 'Daily Time Log',
            'content' => $content
        ]);
    ?>

    <!-- Timesheet History Modal -->
    <?php
        $content = view('client_portal/modals/view_timesheet_history', ['timesheetHistory' => $timesheetHistory ?? []]);
        echo view('components/view_modal', [
            'id'    => 'viewTimesheetHistoryModal',
            'title' => 'Timesheets History',
            'content' => $content
        ]);
    ?>

    <!-- Payslip Modal -->
    <?php
        $content = view('client_portal/modals/view_payslip');
        echo view('components/view_modal', [
            'id'      => 'viewPayslipModal',
            'content' => $content
        ]);
    ?>

    <!-- Review Employee Timesheets Modal (client_payroll_staff / admin only) -->
    <?php if ($isPayrollAdmin): ?>
    <?php
        $content = view('client_portal/modals/view_employee_timesheet_review');
        echo view('components/view_modal', [
            'id'       => 'viewEmployeeTimesheetModal',
            'title'    => 'Review Employee Timesheets',
            'maxWidth' => '1320px',
            'content'  => $content,
        ]);
    ?>
    <?php endif; ?>

    <!-- Attendance Issues Modal (client_payroll_staff / admin only) -->
    <?php if ($isPayrollAdmin): ?>
    <?php
        $tabBtn = 'style="padding:8px 14px;border:none;border-bottom:3px solid transparent;background:none;font-size:14px;font-weight:600;color:#6b7280;cursor:pointer;"';
        $field  = 'style="display:block;border:1px solid #d1d5db;border-radius:6px;padding:6px 8px;font-size:13px;"';
        $content = '<p class="text-sm text-gray-500 -mt-2 mb-3">Fix a wrong time, a session that was never closed, or an impossible total. Every fix is recomputed the same way a live clock-out is, and is logged.</p>'
                 . '<div role="tablist" style="display:flex;gap:4px;border-bottom:1px solid #e5e7eb;margin-bottom:12px;">'
                 .   '<button type="button" role="tab" id="attTabIssues" onclick="switchAttendanceTab(\'issues\')" ' . $tabBtn . '>Needs attention <span id="attTabIssuesCount" style="margin-left:4px;background:#fee2e2;color:#b91c1c;border-radius:9px;padding:1px 7px;font-size:11px;">0</span></button>'
                 .   '<button type="button" role="tab" id="attTabAll" onclick="switchAttendanceTab(\'all\')" ' . $tabBtn . '>All records</button>'
                 . '</div>'
                 . '<div id="attPanelIssues"><div id="attendanceIssuesList" style="max-height:58vh;overflow:auto;"><p class="text-gray-400 text-sm">Loading…</p></div></div>'
                 . '<div id="attPanelAll" hidden>'
                 .   '<div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:10px;">'
                 .     '<label style="font-size:12px;color:#555;">Employee<select id="arEmployee" style="display:block;border:1px solid #d1d5db;border-radius:6px;padding:6px 8px;font-size:13px;min-width:220px;"><option value="">Loading…</option></select></label>'
                 .     '<label style="font-size:12px;color:#555;">From<input type="date" id="arFrom" ' . $field . '></label>'
                 .     '<label style="font-size:12px;color:#555;">To<input type="date" id="arTo" ' . $field . '></label>'
                 .     '<button type="button" onclick="searchAttendanceRecords(1)" style="background:#007bff;color:#fff;border:none;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Show records</button>'
                 .   '</div>'
                 .   '<div id="arList" style="max-height:52vh;overflow:auto;"><p class="text-gray-400 text-sm">Choose an employee and a date range.</p></div>'
                 .   '<div id="arPager" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;font-size:12px;color:#666;"></div>'
                 . '</div>';
        echo view('components/view_modal', [
            'id'       => 'attendanceIssuesModal',
            'title'    => 'Attendance Corrections',
            'maxWidth' => '900px',
            'content'  => $content,
        ]);
    ?>
    <?php endif; ?>

    <!-- View Timesheet Details Modal -->
    <?php
        $content = '<p id="historyDetailsSubtitle" class="text-sm text-gray-500 -mt-3 mb-4"></p>'
                 . '<div id="historyDetailsContainer"></div>';
        echo view('components/view_modal', [
            'id' => 'viewHistoryDetailsModal',
            'title' => 'Timesheet Details',
            'maxWidth' => '1320px',
            'content' => $content,
        ]);
    ?>

    <!-- Task Log Modal — chrome matches the Dashboard's Task Log modal
         (rgba(0,0,0,.45) backdrop, no blur, 14px radius, modalPop animation)
         for a consistent look between the two entry points. -->
    <div id="taskLogModal"
        class="fixed inset-0 z-[9999] hidden items-center justify-center">

        <!-- BACKDROP -->
        <div class="absolute inset-0" style="background: rgba(0, 0, 0, 0.45);"></div>

        <!-- MODAL -->
        <div class="relative bg-white w-full mx-4 flex flex-col overflow-hidden" style="max-width:640px;max-height:85vh;border-radius:14px;box-shadow:0 25px 50px rgba(0,0,0,0.25);animation:tlModalPop 0.2s ease;">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        Task Log
                    </h3>

                    <p id="task_att_date"
                    class="text-xs text-gray-400 mt-0.5">
                    </p>
                </div>

                <button onclick="closeTaskLogModal()" class="tl-modal-close">
                    &times;
                </button>
            </div>

            <!-- BODY (scrollable) -->
            <div class="px-5 py-4 overflow-y-auto">

                <!-- TIMELINE -->
                <div class="mb-4">
                    <div class="flex items-center justify-between" style="margin-bottom:6px;">
                        <label class="text-xs font-semibold text-gray-500">Your Clocked-In Time</label>
                        <span id="tl_window_label" class="text-xs text-gray-400"></span>
                    </div>
                    <div id="tl_timeline" class="relative w-full bg-gray-100 rounded-lg overflow-hidden border border-gray-200" style="height:32px;cursor:pointer;"></div>
                    <p class="text-[11px] text-gray-400 mt-1">Colored blocks are already-logged tasks — click a free (empty) spot to start a new one there.</p>
                </div>

                <!-- TASK LIST -->
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 block" style="margin-bottom:6px;">Logged Tasks</label>
                    <div id="tl_task_list"></div>
                    <p id="tl_empty_msg" class="text-xs text-gray-400 text-center py-3 hidden">No tasks logged yet for this session.</p>
                </div>

                <!-- ADD/EDIT FORM -->
                <div class="border-t border-gray-100" style="padding-top:12px;">
                    <div class="flex items-center justify-between" style="margin-bottom:6px;">
                        <label id="tl_form_title" class="text-xs font-semibold text-gray-700">Add a Task</label>
                        <button type="button" id="tl_cancel_edit_btn" onclick="cancelTaskEdit()" class="hidden text-xs text-purple-600 hover:text-purple-700 font-semibold">Cancel edit</button>
                    </div>

                    <label class="block text-[11px] text-gray-400 mb-0.5">Select Preset Task</label>
                    <select id="savedTasksDropdown" class="tl-field mb-2">
                        <option value="">-- Write your task manually --</option>
                    </select>

                    <label class="block text-[11px] text-gray-400 mb-0.5">Or write your task *</label>
                    <textarea id="task_att_text" rows="2" placeholder="e.g. Coding Session" class="tl-field resize-none mb-2"></textarea>

                    <div class="grid grid-cols-2 mb-2" style="gap:10px;">
                        <div>
                            <label class="block text-[11px] text-gray-400 mb-0.5">Start Time *</label>
                            <input type="hidden" id="task_start_time">
                            <div class="tl-time-row">
                                <input type="number" id="task_start_hour" class="tl-time-select" min="1" max="12" inputmode="numeric" placeholder="hh">
                                <span class="tl-time-colon">:</span>
                                <input type="number" id="task_start_minute" class="tl-time-select" min="0" max="59" inputmode="numeric" placeholder="mm">
                                <select id="task_start_ampm" class="tl-time-select tl-time-ampm"></select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-400 mb-0.5">End Time *</label>
                            <input type="hidden" id="task_end_time">
                            <div class="tl-time-row">
                                <input type="number" id="task_end_hour" class="tl-time-select" min="1" max="12" inputmode="numeric" placeholder="hh">
                                <span class="tl-time-colon">:</span>
                                <input type="number" id="task_end_minute" class="tl-time-select" min="0" max="59" inputmode="numeric" placeholder="mm">
                                <select id="task_end_ampm" class="tl-time-select tl-time-ampm"></select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between" style="margin-bottom:10px;">
                        <div class="flex" style="gap:6px;">
                            <button type="button" onclick="applyDurationPreset(15)" class="tl-duration-chip">15m</button>
                            <button type="button" onclick="applyDurationPreset(30)" class="tl-duration-chip">30m</button>
                            <button type="button" onclick="applyDurationPreset(60)" class="tl-duration-chip">1h</button>
                            <button type="button" onclick="applyDurationPreset(120)" class="tl-duration-chip">2h</button>
                        </div>
                        <span id="tl_duration_label" class="text-[11px] font-semibold text-gray-500"></span>
                    </div>

                    <label class="block text-[11px] text-gray-400 mb-0.5">Select Preset Client</label>
                    <select id="savedClientsDropdown" class="tl-field mb-2">
                        <option value="">-- Type a client manually --</option>
                    </select>

                    <label class="block text-[11px] text-gray-400 mb-0.5">Or type the client</label>
                    <input type="text" id="task_att_client" placeholder="e.g. Acme Corp" class="tl-field mb-2">

                    <label class="text-xs text-gray-600" style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <input type="checkbox" id="saveTaskToggle"> Save this task as a preset
                    </label>
                    <label class="text-xs text-gray-600" style="display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="saveClientToggle"> Save this client as a preset
                    </label>

                    <p id="tl_form_error" class="text-xs text-red-600 hidden" style="margin-top:6px;"></p>

                    <input type="hidden" id="task_att_id">
                    <input type="hidden" id="task_editing_id" value="">
                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end px-5 border-t border-gray-200 flex-shrink-0" style="padding-top:14px;padding-bottom:14px;gap:10px;">

                <button
                    type="button"
                    onclick="closeTaskLogModal()"
                    class="tl-cancel-btn">
                    Close
                </button>

                <button
                    type="button"
                    id="tl_save_btn"
                    onclick="saveTask()"
                    class="tl-submit-btn">
                    Add Task
                </button>

            </div>

        </div>
    </div>

    <style>
        @media (max-width: 640px){
            #timesheetPageRoot{
                padding-left:12px;
                padding-right:12px;
            }
            #timesheetPageRoot h1{
                font-size:22px;
            }
        }

        @keyframes tlModalPop{ from{ transform:scale(0.95); opacity:0; } to{ transform:scale(1); opacity:1; } }

        .tl-field{
            width:100%; padding:8px 10px; font-size:14px; color:#374151;
            background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;
            outline:none; transition:border-color .15s;
        }
        .tl-field:focus{ border-color:#a78bfa; box-shadow:0 0 0 2px rgba(167,139,250,0.35); }

        .tl-modal-close{
            border:none; background:transparent; cursor:pointer;
            font-size:24px; line-height:1; color:#111827; padding:0;
        }

        .tl-cancel-btn{
            border:none; background:#e5e7eb; color:#111827;
            padding:8px 14px; border-radius:6px; font-size:14px; font-weight:500;
            cursor:pointer; transition:all 0.2s ease;
        }
        .tl-cancel-btn:hover{ background:#d1d5db; transform:translateY(-2px); }

        .tl-submit-btn{
            border:none; background:#8b5cf6; color:#fff;
            padding:8px 16px; border-radius:6px; font-size:14px; font-weight:600;
            cursor:pointer; transition:all 0.2s ease;
        }
        .tl-submit-btn:hover{ background:#7c3aed; transform:translateY(-2px); box-shadow:0 4px 12px rgba(139,92,246,0.3); }

        .tl-duration-chip{
            border:1px solid #e5e7eb; background:#f9fafb; color:#4b5563;
            padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;
            cursor:pointer; transition:.15s ease;
        }
        .tl-duration-chip:hover{ background:#ede9fe; border-color:#c4b5fd; color:#6d28d9; }

        .tl-time-row{ display:flex; align-items:center; gap:4px; }
        .tl-time-select{
            flex:1; min-width:0; padding:8px 4px; font-size:13px; color:#374151;
            background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;
            outline:none; text-align:center; transition:border-color .15s;
        }
        .tl-time-select:focus{ border-color:#a78bfa; box-shadow:0 0 0 2px rgba(167,139,250,0.35); }
        .tl-time-select.tl-time-ampm{ flex:0 0 58px; }
        .tl-time-colon{ color:#9ca3af; font-weight:700; font-size:13px; }
    </style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/modal-handler.js') ?>?v=2"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>?v=<?= is_file(FCPATH . 'js/components/crud-table.js') ? filemtime(FCPATH . 'js/components/crud-table.js') : time() ?>"></script>

<script>
const DAILY_DATA = <?= json_encode($dailyTimesheet) ?>;
const TOTALS = <?= json_encode($totals) ?>;
const TASKS_BASE_URL  = "<?= site_url('client_portal/timesheet/tasks') ?>";
const UPDATE_TASK_URL = "<?= site_url('client_portal/timesheet/tasks/edit') ?>";
const DELETE_TASK_URL = "<?= site_url('client_portal/timesheet/tasks/delete') ?>";
const SAVED_TASKS_URL   = "<?= site_url('client_portal/tasks/saved') ?>";
const SAVED_CLIENTS_URL = "<?= site_url('client_portal/tasks/saved-clients') ?>";

let currentData = [...DAILY_DATA];

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}


function badge(value, formatted, type) {
    if (!value || value === 0) return `<span class="text-gray-400">—</span>`;
    const styles = {
        ot:    'bg-green-100 text-green-700',
        rdot:  'bg-blue-100 text-blue-700',
        tardy: 'bg-yellow-100 text-yellow-700',
        nd:    'bg-purple-100 text-purple-700',
        reg:   'text-gray-800 font-medium',
        unot:  'bg-red-100 text-red-700',
        ut:    'bg-orange-100 text-orange-700',
        uut:   'bg-red-100 text-red-700',
    };
    const cls = styles[type] || 'text-gray-800';
    const pill = ['ot','rdot','tardy','nd','unot','ut','uut'].includes(type);
    return pill
        ? `<span class="inline-block ${cls} px-2 py-0.5 rounded-full text-xs font-semibold">${formatted}</span>`
        : `<span class="${cls}">${formatted}</span>`;
}

// Remarks arrive as one "; "-joined string ("Unapproved Overtime; Unapproved
// Undertime (left early)"), which wrapped into a 4-line wall of gray text in
// the narrow column. Split it back into separate items — only at a "; " that
// sits OUTSIDE parentheses, since a leave/undertime reason can itself contain
// "; " — and show each as its own color-coded chip so the meaning (approved /
// unapproved / leave / missed clock-out) reads at a glance.
function splitRemarks(str) {
    const parts = [];
    let depth = 0, cur = '';
    for (let i = 0; i < str.length; i++) {
        const ch = str[i];
        if (ch === '(') depth++;
        else if (ch === ')') depth = Math.max(0, depth - 1);
        if (depth === 0 && ch === ';' && str[i + 1] === ' ') { parts.push(cur.trim()); cur = ''; i++; continue; }
        cur += ch;
    }
    if (cur.trim()) parts.push(cur.trim());
    return parts.filter(Boolean);
}

function remarksChips(remarks) {
    const items = splitRemarks(String(remarks || ''));
    if (!items.length) return '<span class="text-gray-300">—</span>';
    return '<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;">' + items.map(item => {
        let cls = 'bg-gray-100 text-gray-700';
        if (/^unapproved/i.test(item)) cls = 'bg-red-100 text-red-700';
        else if (/^missed clock-out/i.test(item)) cls = 'bg-yellow-100 text-yellow-700';
        else if (/leave/i.test(item)) cls = 'bg-blue-100 text-blue-700';
        else if (/^approved/i.test(item)) cls = 'bg-green-100 text-green-700';
        return `<span class="${cls} px-2 py-0.5 rounded-full text-xs font-semibold" style="white-space:normal;line-height:1.3;" title="${escapeHtml(item)}">${escapeHtml(item)}</span>`;
    }).join('') + '</div>';
}

function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    if (currentData.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="16" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-2 text-gray-400">
                    <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-base font-semibold text-gray-500">No attendance records</span>
                    <span class="text-sm">No records found for this period</span>
                </div>
            </td></tr>`;
        return;
    }
    let html = '';
    currentData.forEach((day, i) => {
        const rowBg = i % 2 === 0 ? '' : 'bg-gray-50/60';
        const timeInDisplay  = day.in  && day.in  !== '-' ? escapeHtml(day.in)  : '<span class="text-gray-400">—</span>';
        const nextDay = day.out_date && day.out_date !== day.date ? ` <span class="text-[10px] font-semibold text-gray-400">${escapeHtml(formatDate(day.out_date))}</span>` : '';
        const timeOutDisplay = day.out && day.out !== '-' ? escapeHtml(day.out) + nextDay : '<span class="text-gray-400">—</span>';
        const breakDisplay = day.break_hours > 0 ? Number(day.break_hours).toFixed(2) : '<span class="text-gray-400">—</span>';
        const dayTasks = day.tasks || [];
        const clientCell = day.client
            ? `<span class="text-gray-700">${escapeHtml(day.client)}</span>`
            : `<span class="text-gray-300">—</span>`;
        const taskCell = dayTasks.length
            ? `<span class="text-gray-700">${escapeHtml(dayTasks[0].task_performed)}</span>${dayTasks.length > 1 ? `<span class="ml-1 text-[10px] font-semibold text-purple-500 cursor-pointer hover:underline" onclick="openTaskLogModal(${day.attendance_id}, '${day.date}')">+${dayTasks.length - 1} more</span>` : ''}`
            : `<span class="text-gray-300">—</span>`;
        const actionCell = day.attendance_id
            ? `<div class="cursor-pointer w-7 h-7 hover:scale-110 transition inline-block"
                    onclick="openTaskLogModal(${day.attendance_id}, '${day.date}')">
                   <img src="<?= base_url('icons/edit.png') ?>" class="w-7 h-7" alt="Edit">
               </div>`
            : '<span class="text-gray-300">—</span>';
        html += `
            <tr class="border-b border-gray-100 hover:bg-purple-50/40 transition-colors ${rowBg}">
                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700">${formatDate(day.date)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeInDisplay}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeOutDisplay}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600" title="Unpaid break deducted from the day">${breakDisplay}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.regular_hours, day.regular_hours.toFixed(2), 'reg')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.ot_hours, day.ot_hours.toFixed(2), 'ot')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap" title="Worked but not approved — not counted toward Regular or OT">${badge(day.unapproved_ot_hours, day.unapproved_ot_hours.toFixed(2), 'unot')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.rdot_hours, day.rdot_hours.toFixed(2), 'rdot')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(Math.round(day.tardy_minutes), Math.round(day.tardy_minutes), 'tardy')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap" title="Approved undertime — excused, no pay impact">${badge(day.undertime_hours, day.undertime_hours.toFixed(2), 'ut')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap" title="Time missed that wasn't approved — counted against you">${badge(day.unapproved_undertime_hours, day.unapproved_undertime_hours.toFixed(2), 'uut')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.night_diff, day.night_diff.toFixed(2), 'nd')}</td>
                <td class="px-4 py-3 text-sm" title="${escapeHtml(day.remarks)}">${remarksChips(day.remarks)}</td>
                <td class="px-4 py-3 text-sm" title="${escapeHtml(day.client)}">${clientCell}</td>
                <td class="px-4 py-3 text-sm" title="${escapeHtml(day.task_performed)}">${taskCell}</td>
                <td class="px-4 py-3 text-center whitespace-nowrap">${actionCell}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    // Recompute from the currently filtered/sorted rows, not the server-rendered
    // full-period TOTALS — otherwise the footer stays stuck showing every record's
    // totals even after searching or filtering down to a subset.
    const shown = currentData.reduce((acc, day) => {
        acc.regular_hours       += day.regular_hours || 0;
        acc.ot_hours            += day.ot_hours || 0;
        acc.rdot_hours          += day.rdot_hours || 0;
        acc.tardy_minutes       += day.tardy_minutes || 0;
        acc.night_diff          += day.night_diff || 0;
        acc.unapproved_ot_hours += day.unapproved_ot_hours || 0;
        acc.undertime_hours     += day.undertime_hours || 0;
        acc.unapproved_undertime_hours += day.unapproved_undertime_hours || 0;
        return acc;
    }, { regular_hours: 0, ot_hours: 0, rdot_hours: 0, tardy_minutes: 0, night_diff: 0, unapproved_ot_hours: 0, undertime_hours: 0, unapproved_undertime_hours: 0 });

    document.getElementById('totalRegular').innerText = shown.regular_hours.toFixed(2);
    document.getElementById('totalOt').innerText = shown.ot_hours.toFixed(2);
    document.getElementById('totalUnapprovedOt').innerText = shown.unapproved_ot_hours.toFixed(2);
    document.getElementById('totalRdot').innerText = shown.rdot_hours.toFixed(2);
    document.getElementById('totalTardy').innerText = Math.round(shown.tardy_minutes);
    document.getElementById('totalUndertime').innerText = shown.undertime_hours.toFixed(2);
    document.getElementById('totalUnapprovedUndertime').innerText = shown.unapproved_undertime_hours.toFixed(2);
    document.getElementById('totalNightDiff').innerText = shown.night_diff.toFixed(2);
}

// Task Log modal logic (timeline, task list, add/edit form) lives in the
// shared public/js/components/task-log.js, included below — reused as-is
// by the Dashboard's "Log Task" button.

function filterAndSort() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const sortBy = document.getElementById('sortBy').value;
    const department = document.getElementById('departmentFilter').value;

    let filtered = DAILY_DATA.filter(day => {
        const matchesSearch = !searchTerm || 
            day.date.includes(searchTerm) ||
            (day.remarks && day.remarks.toLowerCase().includes(searchTerm)) ||
            day.in.toLowerCase().includes(searchTerm) ||
            day.out.toLowerCase().includes(searchTerm);
        const matchesDept = !department || (day.department && day.department === department);
        return matchesSearch && matchesDept;
    });

    filtered.sort((a, b) => {
        if (sortBy === 'date') return new Date(b.date) - new Date(a.date);
        if (sortBy === 'regular_hours') return b.regular_hours - a.regular_hours;
        if (sortBy === 'ot_hours') return b.ot_hours - a.ot_hours;
        return 0;
    });

    currentData = filtered;
    renderTable();
}

document.getElementById('searchInput')?.addEventListener('input', filterAndSort);
document.getElementById('sortBy')?.addEventListener('change', filterAndSort);
document.getElementById('departmentFilter')?.addEventListener('change', filterAndSort);

renderTable();

// ========== ATTENDANCE CORRECTIONS (flagged finder + all-records browser + corrector) ==========
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function toLocalInputValue(mysqlDatetime) {
    // "2026-06-15 21:33:42" -> "2026-06-15T21:33:42" for a datetime-local input
    // (step="1" below keeps the seconds, so a correction doesn't silently round them off).
    if (!mysqlDatetime) return '';
    return mysqlDatetime.replace(' ', 'T').slice(0, 19);
}

const localDate = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
const attStatusLabel = s => ({
    present: ['On time', '#166534', '#dcfce7'],
    approved_overtime: ['Approved OT', '#1d4ed8', '#dbeafe'],
    unapproved_overtime: ['Unapproved OT', '#b45309', '#fef3c7'],
    approved_undertime: ['Approved UT', '#1d4ed8', '#dbeafe'],
    unapproved_undertime: ['Unapproved UT', '#b91c1c', '#fee2e2'],
}[s] || [String(s || '—').replace(/_/g, ' '), '#374151', '#f3f4f6']);

let attendanceTab = 'issues';
let arState = { page: 1 };

function setBadge(count) {
    const badge = document.getElementById('attendanceIssuesBadge');
    const tabCount = document.getElementById('attTabIssuesCount');
    if (badge) { badge.textContent = count > 99 ? '99+' : count; badge.hidden = count < 1; }
    if (tabCount) tabCount.textContent = count;
}

// One row = one attendance record with its correction form. `kind` only changes the description line.
function attendanceRowHtml(r, kind) {
    const line = kind === 'stuck_open'
        ? `Clocked in ${esc(r.time_in)} — never clocked out`
        : kind === 'bad_hours'
            ? `${esc(r.time_in)} → ${esc(r.time_out)} — total_hours: ${esc(r.total_hours)}`
            : `${esc(r.time_in)} → ${r.time_out ? esc(r.time_out) : '<em>still open</em>'} · ${esc(r.total_hours ?? '—')} h · break ${esc(r.break_duration ?? 0)} h${Number(r.late_minutes) > 0 ? ' · late ' + esc(r.late_minutes) + ' min' : ''}`;
    const [stText, stFg, stBg] = attStatusLabel(r.attendance_status);
    const chip = kind === 'record'
        ? `<span style="margin-left:6px;background:${stBg};color:${stFg};border-radius:9px;padding:1px 8px;font-size:11px;font-weight:600;">${esc(stText)}</span>` : '';
    const notes = [
        r.break_start ? `<div style="color:#b45309;">Left mid-break since ${esc(r.break_start)}. Saving closes it.</div>` : '',
        r.last_correction ? `<div style="color:#6b7280;">Last corrected by ${esc(r.last_correction.by)} on ${esc(r.last_correction.at)}</div>` : '',
        r.payslip_issued ? `<div style="color:#b91c1c;">A payslip already covers this day. Correcting it will NOT change the payslip already generated.</div>` : '',
    ].join('');
    const nameLine = kind === 'record' ? '' : `
                        <strong>${esc(r.employee_name)}</strong>
                        <span style="color:#888;font-size:12px;"> (${esc(r.employee_number || '')})</span>`;
    return `
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;margin-bottom:10px;">
            <div>${nameLine}${chip}
                <div style="font-size:12px;color:#666;margin-top:2px;">${line}</div>
                <div style="font-size:12px;margin-top:2px;">${notes}</div>
            </div>
            <form onsubmit="return submitAttendanceCorrection(event, ${Number(r.id)})" data-payslip="${r.payslip_issued ? '1' : '0'}"
                  style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-top:10px;">
                <label style="font-size:12px;color:#555;">Time In
                    <input type="datetime-local" step="1" name="time_in" value="${esc(toLocalInputValue(r.time_in))}" required
                        style="display:block;border:1px solid #ccc;border-radius:6px;padding:5px 8px;">
                </label>
                <label style="font-size:12px;color:#555;">Time Out
                    <input type="datetime-local" step="1" name="time_out" value="${esc(toLocalInputValue(r.time_out || r.suggested_time_out))}" required
                        style="display:block;border:1px solid #ccc;border-radius:6px;padding:5px 8px;">
                    ${kind === 'stuck_open' && r.suggested_time_out ? '<span style="display:block;font-size:11px;color:#b45309;margin-top:2px;">Suggested: end of scheduled shift. Confirm the actual time.</span>' : ''}
                </label>
                <label style="font-size:12px;color:#555;">Break (hrs)
                    <input type="number" name="break_hours" step="0.01" min="0" value="${esc(r.break_duration ?? 0)}"
                        style="display:block;border:1px solid #ccc;border-radius:6px;padding:5px 8px;width:80px;">
                </label>
                <button type="submit" style="background:#007bff;color:#fff;border:none;border-radius:6px;padding:7px 14px;font-size:13px;cursor:pointer;">Save correction</button>
                <span class="correction-status" style="font-size:12px;"></span>
            </form>
        </div>`;
}

async function attFetchJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    let data;
    try { data = await res.json(); }
    catch (e) { throw new Error(res.status === 403 ? 'You do not have access to this.' : 'Unexpected server response (' + res.status + ').'); }
    if (data.error) throw new Error(data.error);
    return data;
}

function switchAttendanceTab(tab) {
    attendanceTab = tab;
    const on  = 'color:#007bff;border-bottom:3px solid #007bff;';
    const off = 'color:#6b7280;border-bottom:3px solid transparent;';
    for (const [id, name] of [['attTabIssues', 'issues'], ['attTabAll', 'all']]) {
        const el = document.getElementById(id);
        el.setAttribute('aria-selected', String(tab === name));
        el.style.cssText += tab === name ? on : off;
    }
    document.getElementById('attPanelIssues').hidden = tab !== 'issues';
    document.getElementById('attPanelAll').hidden = tab !== 'all';
    if (tab === 'all') initAttendanceBrowser();
}

async function openAttendanceIssues() {
    openModalById('attendanceIssuesModal');
    switchAttendanceTab('issues');
    await loadAttendanceIssues();
}

async function loadAttendanceIssues() {
    const list = document.getElementById('attendanceIssuesList');
    list.innerHTML = '<p class="text-gray-400 text-sm">Loading…</p>';
    try {
        const data = await attFetchJson('<?= site_url('client_portal/timesheet/flagged') ?>');
        const stuckRows = data.stuck_open || [];
        const badRows = data.bad_hours || [];
        setBadge(stuckRows.length + badRows.length);

        if (!stuckRows.length && !badRows.length) {
            list.innerHTML = `<div style="text-align:center;padding:28px 8px;color:#4b5563;">
                <div style="font-size:30px;">✅</div>
                <p style="font-weight:600;margin-top:6px;">Nothing needs attention right now.</p>
                <p style="font-size:13px;color:#6b7280;margin:4px 0 12px;">No unclosed sessions or impossible totals.</p>
                <button type="button" onclick="switchAttendanceTab('all')" style="background:#007bff;color:#fff;border:none;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Browse all records</button>
            </div>`;
            return;
        }
        list.innerHTML =
            (stuckRows.length ? '<h4 style="font-weight:600;margin:6px 0;">Never clocked out</h4>' + stuckRows.map(r => attendanceRowHtml(r, 'stuck_open')).join('') : '') +
            (badRows.length   ? '<h4 style="font-weight:600;margin:6px 0;">Impossible totals</h4>' + badRows.map(r => attendanceRowHtml(r, 'bad_hours')).join('') : '');
    } catch (err) {
        list.innerHTML = `<p style="color:#c0392b;font-size:13px;">${esc(err.message || 'Could not load flagged records.')}</p>`;
    }
}

// Badge on the button: how many records need attention, without opening the modal.
async function refreshAttendanceBadge() {
    if (!document.getElementById('attendanceIssuesBadge')) return;
    try {
        const data = await attFetchJson('<?= site_url('client_portal/timesheet/flagged') ?>');
        setBadge((data.stuck_open || []).length + (data.bad_hours || []).length);
    } catch (e) { /* badge is a nicety; stay hidden on failure */ }
}
document.addEventListener('DOMContentLoaded', refreshAttendanceBadge);

let attendanceEmployeesLoaded = false;
async function initAttendanceBrowser() {
    const fromEl = document.getElementById('arFrom'), toEl = document.getElementById('arTo');
    if (!toEl.value) toEl.value = localDate(new Date());
    if (!fromEl.value) { const d = new Date(); d.setDate(d.getDate() - 30); fromEl.value = localDate(d); }
    if (attendanceEmployeesLoaded) return;
    const sel = document.getElementById('arEmployee');
    try {
        const employees = await attFetchJson('<?= site_url('client_portal/timesheet/review-employees') ?>');
        sel.innerHTML = '<option value="">Select an employee…</option>' +
            employees.map(e => `<option value="${Number(e.id)}">${esc(e.label)} (${esc(e.employee_number || '')})</option>`).join('');
        attendanceEmployeesLoaded = true;
    } catch (err) {
        sel.innerHTML = '<option value="">Could not load employees</option>';
    }
}

async function searchAttendanceRecords(page) {
    const list = document.getElementById('arList'), pager = document.getElementById('arPager');
    const employeeId = document.getElementById('arEmployee').value;
    if (!employeeId) { list.innerHTML = '<p style="color:#c0392b;font-size:13px;">Please choose an employee first.</p>'; pager.innerHTML = ''; return; }
    arState = { page };
    list.innerHTML = '<p class="text-gray-400 text-sm">Loading…</p>';
    try {
        const qs = new URLSearchParams({
            employee_id: employeeId,
            from: document.getElementById('arFrom').value,
            to: document.getElementById('arTo').value,
            page,
        });
        const data = await attFetchJson('<?= site_url('client_portal/timesheet/records') ?>?' + qs.toString());
        const pages = Math.max(1, Math.ceil(data.total / data.per_page));
        list.innerHTML = data.records.length
            ? data.records.map(r => attendanceRowHtml(r, 'record')).join('')
            : '<p class="text-gray-400 text-sm">No attendance records in this range.</p>';
        const btn = (label, target, disabled) => `<button type="button" ${disabled ? 'disabled' : `onclick="searchAttendanceRecords(${target})"`}
            style="border:1px solid #d1d5db;border-radius:6px;padding:5px 12px;background:#fff;${disabled ? 'opacity:.4;cursor:default;' : 'cursor:pointer;'}">${label}</button>`;
        pager.innerHTML = data.total
            ? `<span>${esc(data.employee_name)} · ${data.total} record${data.total === 1 ? '' : 's'} · page ${data.page} of ${pages}</span>
               <span style="display:flex;gap:6px;">${btn('‹ Prev', data.page - 1, data.page <= 1)}${btn('Next ›', data.page + 1, data.page >= pages)}</span>`
            : '';
    } catch (err) {
        list.innerHTML = `<p style="color:#c0392b;font-size:13px;">${esc(err.message || 'Could not load records.')}</p>`;
        pager.innerHTML = '';
    }
}

async function submitAttendanceCorrection(event, attendanceId) {
    event.preventDefault();
    const form = event.target;
    const statusEl = form.querySelector('.correction-status');
    if (form.dataset.payslip === '1' &&
        !confirm('A payslip already covers this day. Correcting the attendance will NOT change that payslip.\n\nSave the correction anyway?')) {
        return false;
    }
    // datetime-local gives "YYYY-MM-DDTHH:MM", or "...THH:MM:SS" when seconds are set.
    const toMysql = v => v ? v.replace('T', ' ') + (v.length === 16 ? ':00' : '') : '';
    const payload = {
        time_in: toMysql(form.time_in.value),
        time_out: toMysql(form.time_out.value),
        break_hours: form.break_hours.value,
    };
    statusEl.textContent = 'Saving…'; statusEl.style.color = '#666';
    try {
        const res = await fetch('<?= site_url('client_portal/timesheet/update-attendance') ?>/' + attendanceId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // Without this the CSRF filter rejects the save ("The action you requested is not allowed").
                'X-CSRF-TOKEN': CSRF(),
            },
            body: JSON.stringify(payload),
        });
        // A non-JSON reply (e.g. an expired session or CSRF rejection) used to surface as a cryptic parse error.
        let result;
        try { result = await res.json(); } catch (e) { throw new Error(res.status === 403 ? 'Session expired or request blocked. Refresh the page and try again.' : 'Unexpected server response (' + res.status + ').'); }
        if (!result.success) throw new Error(result.message || 'Correction failed.');
        // The main table above (Remarks, hours, tardy, etc.) is baked into the page
        // as DAILY_DATA/TOTALS at load time — it does not re-fetch on its own, so a
        // correction here would otherwise show as saved in this modal but invisible
        // in the table until a manual refresh. Reload, same as task-log.js does for
        // its own edits, so the table and modal lists both come back current.
        statusEl.textContent = 'Saved. Refreshing…'; statusEl.style.color = '#16a34a';
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        statusEl.textContent = err.message || 'Could not save.'; statusEl.style.color = '#c0392b';
    }
    return false;
}
</script>
<script src="<?= base_url('js/components/task-log.js') ?>?v=<?= is_file(FCPATH . 'js/components/task-log.js') ? filemtime(FCPATH . 'js/components/task-log.js') : time() ?>"></script>
<?= $this->endSection() ?>
