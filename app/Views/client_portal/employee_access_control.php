<div class="flex flex-col w-full h-full min-w-0 min-h-0">

    <div class="flex justify-between items-center my-4 shrink-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Access Control</h2>
            <p class="text-sm text-gray-500 mt-1">Assign each account's portal role(s). This only changes what they can access — it doesn't affect their employee record.</p>
        </div>
    </div>

    <?php
    // Line icons used only inside Access Control (stroke style matches the role-card icons below).
    $acTabs = [
        'accounts'    => ['Accounts', '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c0-3 2.4-5 5.5-5s5.5 2 5.5 5"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16.5 14.2c2.6.2 4.5 1.9 4.5 4.3"/>'],
        'departments' => ['Departments', '<path d="M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16"/><path d="M14 10h5a1 1 0 0 1 1 1v10"/><path d="M3 21h18"/><path d="M8 8h2M8 12h2M8 16h2"/>'],
        'permissions' => ['Role Permissions', '<path d="M12 3 5 6v5c0 4.6 3 8.3 7 10 4-1.7 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>'],
        'network'     => ['Attendance Network', '<path d="M2.5 9a15 15 0 0 1 19 0"/><path d="M5.5 12.5a10.5 10.5 0 0 1 13 0"/><path d="M8.7 16a6 6 0 0 1 6.6 0"/><path d="M12 19.5v.01"/>'],
    ];
    ?>
    <div class="ac-seg mb-4 shrink-0" id="acSubTabs">
        <?php foreach ($acTabs as $acTabKey => [$acTabLabel, $acTabIcon]): ?>
        <button type="button" class="ac-subtab" data-ac-tab="<?= $acTabKey ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $acTabIcon ?></svg><?= esc($acTabLabel) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="flex-1 min-h-0" data-ac-panel="accounts">
        <div id="acListView" style="height:100%;">
            <?php
            // Same shared table component Employee Management and Loan Management use,
            // so search, Filters, sorting and the grid look are identical.
            $roleFilterOptions = [['value' => '', 'label' => 'All']];
            foreach (($roleOptions ?? []) as $roleKey => $roleTitle) {
                $roleFilterOptions[] = ['value' => $roleKey, 'label' => $roleTitle];
            }
            echo view('components/crud_table', [
                'title'       => 'Accounts',
                'tableId'     => 'accessControlTable',
                // Raw CSS value (not a Tailwind arbitrary class) and minmax(0,Nfr) tracks, for
                // the same reasons documented in employee_loan.php. Must match rowTemplate()'s
                // ROW_GRID in employee_main.php.
                'gridStyle'   => 'minmax(0,1.5fr) minmax(0,1.6fr) minmax(0,2.2fr) minmax(0,0.7fr)',
                'columns'     => ['Name', 'Email', 'Role(s)', 'Action'],
                'filters'     => [
                    ['key' => 'role', 'label' => 'Role', 'options' => $roleFilterOptions],
                ],
                'sortOptions' => [
                    ['value' => 'name',  'label' => 'Name'],
                    ['value' => 'email', 'label' => 'Email'],
                ],
            ]);
            ?>
        </div>
    </div>

    <!-- DEPARTMENTS: shared table + "Manage department" modal (see the DEPARTMENTS script in employee_main.php) -->
    <div class="flex-1 min-h-0" id="departmentsSection" data-ac-panel="departments" style="display:none;">
        <div style="height:100%;">
            <?php
            echo view('components/crud_table', [
                'title'       => 'Departments',
                'tableId'     => 'departmentsTable',
                // Must match ROW_GRID in the DEPARTMENTS script.
                'gridStyle'   => 'minmax(0,2fr) minmax(0,0.9fr) minmax(0,2.4fr) minmax(0,0.7fr)',
                'columns'     => ['Department', 'Employees', 'Team Head(s)', 'Action'],
                'filters'     => [
                    ['key' => 'team_head', 'label' => 'Team Head', 'options' => [
                        ['value' => '', 'label' => 'All'],
                        ['value' => 'assigned', 'label' => 'Assigned'],
                        ['value' => 'unassigned', 'label' => 'Not assigned'],
                    ]],
                ],
                'sortOptions' => [
                    ['value' => 'name',      'label' => 'Name'],
                    ['value' => 'employees', 'label' => 'Employees'],
                ],
                'actionsHtml' => '<button type="button" id="addDepartmentBtn" class="ac-add-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add department</button>',
            ]);
            ?>
        </div>
    </div>

    <!-- ROLE PERMISSIONS -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="rolePermissionsSection" data-ac-panel="permissions" style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-400 mb-4">
            <h3 class="text-xl font-bold text-gray-700">Role Permissions</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">What each role can access across the portal. Super Admin isn't listed — it always has full access and can't be limited here.</p>
            <div class="ac-seg ac-seg-wrap" id="rolePermissionTabs"></div>
            <div id="rolePermissionBody" class="mt-4"></div>
        </div>
    </div>

    <!-- ATTENDANCE NETWORK RESTRICTION -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="attendanceNetworkSection" data-ac-panel="network" style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-400 mb-4">
            <h3 class="text-xl font-bold text-gray-700">Attendance Network Restriction</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">
                Require clock-ins and break starts on Onsite days to come from the office network (clocking out and ending a break are always allowed, so no shift gets stranded). WFH days always bypass this, and anyone on the exempt list below bypasses it on any day. Leave the list empty to not enforce anything.
            </p>

            <div class="ann-toggle">
                <label class="ac-switch"><input type="checkbox" id="attendanceEnforcementToggle" aria-label="Enforce office network"><span class="ac-switch-track"></span></label>
                <div>
                    <div class="ann-toggle-title">Enforce office network</div>
                    <div id="attendanceEnforcementHint" class="ann-toggle-hint"></div>
                </div>
            </div>

            <div id="attendanceNetworkGrid" class="mb-4" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:16px; align-items:start;">

                <div class="ann-sub">
                    <div class="flex items-center justify-between mb-2">
                        <label class="ann-label">Allowed office IP(s) / CIDR range(s)</label>
                        <span id="attendanceEnforcementBadge" class="text-xs font-bold px-2 py-1 rounded-full"></span>
                    </div>

                    <div class="ann-detect">
                        <span>Your connection right now: <strong id="attendanceDetectedIp" class="font-mono text-gray-800"></strong></span>
                        <button type="button" id="attendanceAddDetectedBtn" class="ac-mini-btn">Add this</button>
                    </div>

                    <div id="attendanceIpChips" class="ann-chips">
                        <input type="text" id="attendanceIpInput" placeholder="Type an IP or CIDR range, then press Enter"
                            class="flex-1 min-w-[180px] border-none outline-none text-sm px-1 py-1" style="min-width:180px;background:transparent;">
                    </div>
                    <p id="attendanceIpError" class="text-xs text-red-500 mt-1 hidden"></p>
                    <p class="text-xs text-gray-400 mt-1">IPv4 or IPv6, e.g. 203.0.113.4, 203.0.113.0/24 or 2001:db8::/32. Add every address your office uses (all internet lines, and the IPv6 address if it has one). Press Enter, comma, or paste a list.</p>
                </div>

                <div class="ann-sub">
                    <div class="flex items-center justify-between mb-2">
                        <label class="ann-label">Exempt accounts</label>
                        <span id="attendanceExemptCount" class="text-xs text-gray-500 font-semibold"></span>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Always allowed to clock in, on any network, any day.</p>

                    <input type="text" id="attendanceExemptSearch" placeholder="Search accounts…" class="ac-input mb-2" style="width:100%;flex:none;">
                    <div id="attendanceExemptAccountsList" class="bg-white rounded-xl border border-gray-200 p-2 space-y-1" style="max-height:320px; overflow-y:auto;"></div>
                </div>

            </div>

            <div class="flex items-center gap-3">
                <button type="button" id="saveAttendanceNetworkBtn" class="acr-btn acr-save inline-flex items-center gap-2" style="padding:9px 18px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Save
                </button>
                <span id="attendanceNetworkStatus" class="text-xs text-gray-400"></span>
            </div>
        </div>
    </div>
</div>

<!-- Access Control modals + shared widgets. Self-contained CSS in the house modal style
     (light backdrop, no blur, 14px radius, flat gray Cancel, theme-colored primary button)
     so it doesn't depend on Tailwind classes the precompiled build may lack. -->
<style>
.acr-overlay, #rolePermissionsSection, #attendanceNetworkSection{ --acr-accent:var(--sidebar-bg, #1e1e1e); --acr-on-accent:var(--sidebar-text, #fff); }
.acr-overlay{ position:fixed; inset:0; z-index:99999; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.45); }
.acr-overlay.is-open{ display:flex; }
.acr-box{
    display:flex; flex-direction:column;
    width:min(760px, calc(100vw - 32px)); max-height:90vh;
    background:#fff; border-radius:14px; box-shadow:0 25px 50px rgba(0,0,0,.25);
    animation:acrPop .2s ease; font-family:Segoe UI,Arial,sans-serif;
}
.acr-box-sm{ width:min(560px, calc(100vw - 32px)); }
.acr-box-xs{ width:min(440px, calc(100vw - 32px)); }
@keyframes acrPop{ from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.acr-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid #e5e7eb; }
.acr-header-main{ display:flex; align-items:center; gap:12px; min-width:0; }
.acr-header h3{ margin:0; font-size:17px; font-weight:700; color:#17223b; }
.acr-header p{ margin:3px 0 0; font-size:12px; color:#667085; }
.acr-close{ background:none; border:none; font-size:18px; line-height:1; color:#6b7280; cursor:pointer; padding:2px 4px; }
.acr-body{ padding:16px 20px; overflow-y:auto; min-height:0; }
.acr-help{ margin:0 0 14px; font-size:12px; color:#667085; line-height:1.5; }
.acr-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; }
.acr-card{
    position:relative; display:flex; flex-direction:column; gap:8px; text-align:left;
    padding:14px; border:1.5px solid #e5e7eb; border-radius:12px; background:#fff;
    cursor:pointer; transition:border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.acr-card:hover{ border-color:#9ca3af; box-shadow:0 4px 12px rgba(15,23,42,.07); transform:translateY(-1px); }
.acr-card:focus-visible{ outline:3px solid rgba(30,30,30,.28); outline-offset:2px; }
.acr-card.is-selected{ border-color:var(--acr-accent); background:#f3f4f6; box-shadow:0 0 0 1px var(--acr-accent) inset; }
.acr-card-top{ display:flex; align-items:center; justify-content:space-between; }
.acr-icon{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:#f3f4f6; color:#374151; transition:background .15s ease, color .15s ease; }
.acr-icon svg{ width:19px; height:19px; }
.acr-card.is-selected .acr-icon{ background:var(--acr-accent); color:var(--acr-on-accent); }
.acr-check{ display:none; width:20px; height:20px; border-radius:50%; background:var(--acr-accent); color:var(--acr-on-accent); align-items:center; justify-content:center; }
.acr-check svg{ width:12px; height:12px; }
.acr-card.is-selected .acr-check{ display:inline-flex; }
.acr-title{ font-size:14px; font-weight:700; color:#17223b; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.acr-current{ font-size:9px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#374151; background:#e5e7eb; border-radius:999px; padding:2px 7px; }
.acr-desc{ font-size:12px; color:#667085; line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:35px; }
.acr-access{ border-top:1px dashed #e5e7eb; padding-top:9px; margin-top:auto; }
.acr-access-label{ display:block; font-size:9px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9aa1ad; margin-bottom:5px; }
.acr-chips{ display:flex; flex-wrap:wrap; gap:4px; }
.acr-chip{ font-size:10.5px; font-weight:600; color:#4b5563; background:#f3f4f6; border-radius:999px; padding:2px 8px; }
.acr-card.is-selected .acr-chip{ background:#e5e7eb; color:#1f2937; }
.acr-chip-muted{ background:transparent; color:#9aa1ad; font-weight:500; padding-left:0; }
.acr-warn{ display:flex; align-items:flex-start; gap:8px; margin-top:14px; padding:10px 12px; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12px; line-height:1.5; }
.acr-footer{ display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:14px 20px; border-top:1px solid #e5e7eb; }
.acr-footer .acr-note, .acp-footer .acr-note{ margin-right:auto; font-size:12px; color:#737c8b; display:flex; align-items:center; gap:7px; }
.acr-note .acr-dirty{ width:7px; height:7px; border-radius:50%; background:#f59e0b; display:inline-block; }
.acr-btn{ border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s ease, transform .15s ease; }
.acr-cancel{ background:#e5e7eb; color:#374151; }
.acr-cancel:hover{ background:#d1d5db; }
.acr-save{ background:var(--acr-accent); color:var(--acr-on-accent); }
.acr-save:hover:not(:disabled){ background:var(--sidebar-hover, #333); transform:translateY(-2px); }
.acr-save:disabled{ background:#e5e7eb; color:#9ca3af; cursor:not-allowed; }
.acr-avatar{ display:inline-flex; align-items:center; justify-content:center; flex:none; width:32px; height:32px; border-radius:50%; background:#d1d5db; color:#374151; font-size:11px; font-weight:700; letter-spacing:.02em; }
.acr-avatar-lg{ width:40px; height:40px; font-size:13px; }
.acr-avatar svg{ width:20px; height:20px; }

/* Segmented control — same look as the Payroll Register's Payslip / Raw Sheet / Register / Bank File switch. */
.ac-seg{ display:inline-flex; flex-wrap:wrap; gap:2px; align-self:flex-start; max-width:100%; background:#e5e5e5; border-radius:20px; padding:3px; }
.ac-seg button{ display:inline-flex; align-items:center; gap:7px; border:none; background:transparent; padding:7px 16px; border-radius:20px; font-size:13px; font-weight:500; color:#374151; white-space:nowrap; cursor:pointer; transition:background .15s ease, color .15s ease; }
.ac-seg button:hover:not(.is-active){ background:rgba(255,255,255,.55); color:#111827; }
.ac-seg button.is-active{ background:#fff; color:#111827; box-shadow:0 1px 3px rgba(0,0,0,.1); }
.ac-seg svg{ width:15px; height:15px; flex:none; }

/* Table action button + toolbar button (Access Control tables only). */
.ac-icon-btn{ display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:9px; border:1px solid #e5e7eb; background:#fff; color:#4b5563; cursor:pointer; transition:background .15s ease, color .15s ease, border-color .15s ease; }
.ac-icon-btn:hover{ background:#f3f4f6; color:#111827; border-color:#d1d5db; }
.ac-icon-btn:focus-visible{ outline:3px solid rgba(30,30,30,.28); outline-offset:2px; }
.ac-icon-btn svg{ width:17px; height:17px; }
.ac-ico svg{ width:18px; height:18px; }
.ac-add-btn{ display:inline-flex; align-items:center; gap:8px; padding:0 22px; min-height:46px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; background:var(--sidebar-bg, #1e1e1e); color:var(--sidebar-text, #fff); box-shadow:0 4px 6px rgba(0,0,0,.12); transition:background .15s ease; }
.ac-add-btn:hover{ background:var(--sidebar-hover, #333); }
.ac-add-btn svg{ width:16px; height:16px; }

/* Modal form bits */
.ac-field-label{ font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9aa1ad; margin:18px 0 8px; }
.ac-field-label:first-child{ margin-top:0; }
.ac-inline{ display:flex; gap:8px; }
.ac-input{ flex:1; min-width:0; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; color:#111827; background:#fff; outline:none; }
.ac-input:focus{ border-color:var(--sidebar-bg, #1e1e1e); box-shadow:0 0 0 3px rgba(30,30,30,.1); }
#adTeamHeads{ display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:8px; max-height:220px; overflow-y:auto; }
.ac-pick{ display:flex; align-items:center; gap:10px; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; cursor:pointer; background:#fff; }
.ac-pick:hover{ background:#f9fafb; }
.ac-pick-name{ flex:1; min-width:0; font-size:13px; font-weight:600; color:#17223b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ac-pick input{ width:16px; height:16px; accent-color:var(--sidebar-bg, #1e1e1e); }
#adEmployees{ display:flex; flex-wrap:wrap; gap:6px; max-height:120px; overflow-y:auto; }
.ac-danger-zone{ margin-top:20px; padding-top:14px; border-top:1px dashed #e5e7eb; }
.ac-link-danger{ display:inline-flex; align-items:center; gap:7px; background:none; border:none; color:#6b7280; font-size:13px; font-weight:600; cursor:pointer; padding:4px 0; }
.ac-link-danger:hover{ color:#dc2626; }
.ac-link-danger svg{ width:15px; height:15px; }
.ac-danger-actions{ display:flex; gap:8px; margin-top:10px; }
.ac-btn-danger{ background:#dc2626; color:#fff; }
.ac-btn-danger:hover{ background:#b91c1c; }

/* Role Permissions rows */
.acp-list{ border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.acp-perm{ display:flex; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid #f0f1f3; background:#fff; cursor:pointer; }
.acp-perm:last-child{ border-bottom:none; }
.acp-perm:hover{ background:#f9fafb; }
.acp-perm-main{ flex:1; display:flex; align-items:center; gap:8px; min-width:0; }
.acp-perm-name{ font-size:14px; font-weight:600; color:#17223b; }
.acp-perm-state{ width:84px; text-align:right; font-size:12px; color:#9aa1ad; }
.acp-perm.is-on .acp-perm-state{ color:#374151; font-weight:600; }
.acp-sensitive{ font-size:9px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#92400e; background:#fef3c7; border-radius:999px; padding:2px 7px; }
.acp-footer{ display:flex; align-items:center; justify-content:flex-end; gap:8px; margin-top:14px; }
.ac-switch{ position:relative; display:inline-block; flex:none; width:40px; height:22px; }
.ac-switch input{ position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; z-index:1; }
.ac-switch-track{ position:absolute; inset:0; border-radius:999px; background:#d1d5db; transition:background .15s ease; }
.ac-switch-track::after{ content:''; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.3); transition:transform .15s ease; }
.ac-switch input:checked + .ac-switch-track{ background:var(--sidebar-bg, #1e1e1e); }
.ac-switch input:checked + .ac-switch-track::after{ transform:translateX(18px); }
.ac-switch input:focus-visible + .ac-switch-track{ outline:3px solid rgba(30,30,30,.28); outline-offset:2px; }

/* Attendance Network */
.ann-toggle{ display:flex; align-items:center; gap:14px; padding:14px 18px; margin-bottom:16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; }
.ann-toggle-title{ font-size:14px; font-weight:700; color:#17223b; }
.ann-toggle-hint{ margin-top:2px; font-size:12px; color:#667085; line-height:1.45; }
.ann-off{ opacity:.55; }
.ann-sub{ background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; padding:18px; }
.ann-label{ font-size:13px; font-weight:700; color:#17223b; }
.ann-detect{ display:flex; align-items:center; justify-content:space-between; gap:8px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:8px 12px; margin-bottom:8px; font-size:12px; color:#4b5563; }
.ann-chips{ display:flex; flex-wrap:wrap; gap:6px; align-items:center; background:#fff; border:1px solid #d1d5db; border-radius:12px; padding:8px; min-height:46px; }
.ann-chips:focus-within{ border-color:var(--sidebar-bg, #1e1e1e); box-shadow:0 0 0 3px rgba(30,30,30,.1); }
.ac-ip-chip{ display:inline-flex; align-items:center; gap:6px; background:#e5e7eb; color:#374151; font-size:12px; font-weight:600; padding:3px 6px 3px 10px; border-radius:999px; }
.ac-ip-chip button{ background:none; border:none; color:#6b7280; cursor:pointer; line-height:1; font-size:15px; padding:0 2px; }
.ac-ip-chip button:hover{ color:#111827; }
.ac-mini-btn{ border:1px solid #d1d5db; background:#fff; color:#374151; font-size:12px; font-weight:600; padding:4px 10px; border-radius:7px; cursor:pointer; white-space:nowrap; }
.ac-mini-btn:hover:not(:disabled){ background:#f3f4f6; }
</style>

<!-- EDIT ACCESS MODAL (role cards) -->
<div id="acRoleModal" class="acr-overlay" aria-hidden="true">
    <div class="acr-box" role="dialog" aria-modal="true" aria-labelledby="acrTitle">
        <div class="acr-header">
            <div class="acr-header-main">
                <span id="acrAvatar" class="acr-avatar acr-avatar-lg" aria-hidden="true"></span>
                <div>
                    <h3 id="acrTitle">Edit access</h3>
                    <p id="acrSubtitle"></p>
                </div>
            </div>
            <button type="button" id="acrClose" class="acr-close" aria-label="Close">&#10005;</button>
        </div>
        <div class="acr-body">
            <p class="acr-help">Choose one or more roles for this account. Employee can't be combined with a staff role — picking one clears the other.</p>
            <div id="acrCards" class="acr-grid" role="group" aria-label="Roles"></div>
            <div id="acrWarn" class="acr-warn" style="display:none;"></div>
        </div>
        <div class="acr-footer">
            <span id="acrNote" class="acr-note"></span>
            <button type="button" id="acrCancel" class="acr-btn acr-cancel">Cancel</button>
            <button type="button" id="acrBack" class="acr-btn acr-cancel" style="display:none;">Back</button>
            <button type="button" id="acrSave" class="acr-btn acr-save" disabled>Save changes</button>
        </div>
    </div>
</div>

<!-- MANAGE DEPARTMENT MODAL -->
<div id="acDeptModal" class="acr-overlay" aria-hidden="true">
    <div class="acr-box acr-box-sm" role="dialog" aria-modal="true" aria-labelledby="adTitle">
        <div class="acr-header">
            <div class="acr-header-main">
                <span class="acr-avatar acr-avatar-lg" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16"/><path d="M14 10h5a1 1 0 0 1 1 1v10"/><path d="M3 21h18"/><path d="M8 8h2M8 12h2M8 16h2"/></svg></span>
                <div>
                    <h3 id="adTitle">Manage department</h3>
                    <p id="adSubtitle"></p>
                </div>
            </div>
            <button type="button" id="adClose" class="acr-close" aria-label="Close">&#10005;</button>
        </div>
        <div class="acr-body">
            <div class="ac-field-label">Department name</div>
            <div class="ac-inline">
                <input type="text" id="adName" class="ac-input" autocomplete="off">
                <button type="button" id="adRename" class="acr-btn acr-cancel">Save name</button>
            </div>

            <div class="ac-field-label">Team Head(s)</div>
            <div id="adTeamHeads"></div>

            <div class="ac-field-label">Employees in this department</div>
            <div id="adEmployees"></div>

            <div id="adDanger" class="ac-danger-zone"></div>
        </div>
        <div class="acr-footer">
            <span id="adNote" class="acr-note"></span>
            <button type="button" id="adCancel" class="acr-btn acr-cancel">Close</button>
            <button type="button" id="adSave" class="acr-btn acr-save" disabled>Save Team Heads</button>
        </div>
    </div>
</div>

<!-- ADD DEPARTMENT MODAL -->
<div id="acDeptAddModal" class="acr-overlay" aria-hidden="true">
    <div class="acr-box acr-box-xs" role="dialog" aria-modal="true" aria-labelledby="adAddTitle">
        <div class="acr-header">
            <div class="acr-header-main">
                <div>
                    <h3 id="adAddTitle">Add department</h3>
                    <p>Employees are assigned to departments in Employee Management.</p>
                </div>
            </div>
            <button type="button" id="adAddClose" class="acr-close" aria-label="Close">&#10005;</button>
        </div>
        <div class="acr-body">
            <div class="ac-field-label">Department name</div>
            <input type="text" id="newDepartmentName" class="ac-input" style="width:100%;flex:none;" placeholder="e.g. Accounting" autocomplete="off">
        </div>
        <div class="acr-footer">
            <button type="button" id="adAddCancel" class="acr-btn acr-cancel">Cancel</button>
            <button type="button" id="adAddSubmit" class="acr-btn acr-save">Add department</button>
        </div>
    </div>
</div>
