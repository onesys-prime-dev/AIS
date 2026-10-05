<div class="flex flex-col w-full h-full min-w-0 min-h-0">

    <div class="flex justify-between items-center my-4 shrink-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Access Control</h2>
            <p class="text-sm text-gray-500 mt-1">Assign each account's portal role(s). This only changes what they can access — it doesn't affect their employee record.</p>
        </div>
    </div>

    <div class="flex gap-2 mb-4 shrink-0" id="acSubTabs">
        <button type="button" class="ac-subtab" data-ac-tab="accounts">Accounts</button>
        <button type="button" class="ac-subtab" data-ac-tab="departments">Departments</button>
        <button type="button" class="ac-subtab" data-ac-tab="permissions">Role Permissions</button>
        <button type="button" class="ac-subtab" data-ac-tab="network">Attendance Network</button>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto rounded-2xl border border-gray-200 shadow-sm" data-ac-panel="accounts">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Role(s)</th>
                </tr>
            </thead>
            <tbody id="accessControlBody" class="divide-y divide-gray-100"></tbody>
        </table>
    </div>

    <!-- DEPARTMENTS -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="departmentsSection" data-ac-panel="departments" style="display:none;">
        <h3 class="text-lg font-semibold text-gray-800">Departments</h3>
        <p class="text-sm text-gray-500 mt-1">Create departments employees are assigned to in Employee Management, and pick which Team Head(s) cover each one.</p>

        <div class="flex gap-2 mt-3" style="max-width:420px;">
            <input type="text" id="newDepartmentName" placeholder="New department name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1">
            <button type="button" id="addDepartmentBtn" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#A891FC;">Add</button>
        </div>

        <div id="departmentsList" class="mt-4 space-y-3 pb-4"></div>
    </div>

    <!-- ROLE PERMISSIONS -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="rolePermissionsSection" data-ac-panel="permissions" style="display:none;">
        <h3 class="text-lg font-semibold text-gray-800">Role Permissions</h3>
        <p class="text-sm text-gray-500 mt-1">What each role can access across the portal. Super Admin isn't listed — it always has full access and can't be limited here.</p>
        <div class="flex gap-2 mt-3" id="rolePermissionTabs"></div>
        <div id="rolePermissionBody" class="mt-3 mb-4 rounded-2xl border border-gray-200 shadow-sm p-4"></div>
    </div>

    <!-- ATTENDANCE NETWORK RESTRICTION -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="attendanceNetworkSection" data-ac-panel="network" style="display:none;">
        <div class="mb-3">
            <h3 class="text-lg font-semibold text-gray-800">Attendance Network Restriction</h3>
            <p class="text-sm text-gray-500 mt-1">
                Require clock-ins and break starts on Onsite days to come from the office network (clocking out and ending a break are always allowed, so no shift gets stranded). WFH days always bypass this, and anyone on the exempt list below bypasses it on any day. Leave the list empty to not enforce anything.
            </p>
        </div>

        <div class="mb-4" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:16px; align-items:start;">

            <div class="bg-indigo-50 rounded-3xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Allowed office IP(s) / CIDR range(s)</label>
                    <span id="attendanceEnforcementBadge" class="text-xs font-bold px-2 py-1 rounded-full"></span>
                </div>

                <div class="flex items-center justify-between gap-2 bg-white border border-indigo-200 rounded-xl px-3 py-2 mb-2 text-xs text-gray-600">
                    <span>Your connection right now: <strong id="attendanceDetectedIp" class="font-mono text-gray-800"></strong></span>
                    <button type="button" id="attendanceAddDetectedBtn" class="font-semibold px-2 py-1 rounded-md" style="background:#f1eeff;color:#6650cc;">Add this</button>
                </div>

                <div id="attendanceIpChips" class="flex flex-wrap gap-2 bg-white border border-gray-300 rounded-xl p-2 min-h-[46px]">
                    <input type="text" id="attendanceIpInput" placeholder="Type an IP or CIDR range, then press Enter"
                        class="flex-1 min-w-[180px] border-none outline-none text-sm px-1 py-1">
                </div>
                <p id="attendanceIpError" class="text-xs text-red-500 mt-1 hidden"></p>
                <p class="text-xs text-gray-400 mt-1">IPv4 or IPv6, e.g. 203.0.113.4, 203.0.113.0/24 or 2001:db8::/32. Add every address your office uses (all internet lines, and the IPv6 address if it has one). Press Enter, comma, or paste a list.</p>
            </div>

            <div class="bg-indigo-50 rounded-3xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Exempt accounts</label>
                    <span id="attendanceExemptCount" class="text-xs text-gray-500 font-semibold"></span>
                </div>
                <p class="text-xs text-gray-500 mb-2">Always allowed to clock in, on any network, any day.</p>

                <input type="text" id="attendanceExemptSearch" placeholder="Search accounts…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-2 bg-white">
                <div id="attendanceExemptAccountsList" class="bg-white rounded-xl border border-gray-200 p-2 space-y-1" style="max-height:320px; overflow-y:auto;"></div>
            </div>

        </div>

        <div class="flex items-center gap-3 mb-4">
            <button type="button" id="saveAttendanceNetworkBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background:#A891FC;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save
            </button>
            <span id="attendanceNetworkStatus" class="text-xs text-gray-400"></span>
        </div>
    </div>
</div>

<!-- EDIT ACCESS MODAL (role cards). Self-contained CSS in the house modal style
     (light backdrop, no blur, 14px radius, flat gray Cancel, violet Save) so it
     doesn't depend on Tailwind classes the precompiled build may lack. -->
<style>
.acr-overlay{ position:fixed; inset:0; z-index:99999; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.45); }
.acr-overlay.is-open{ display:flex; }
.acr-box{
    display:flex; flex-direction:column;
    width:min(760px, calc(100vw - 32px)); max-height:90vh;
    background:#fff; border-radius:14px; box-shadow:0 25px 50px rgba(0,0,0,.25);
    animation:acrPop .2s ease; font-family:Segoe UI,Arial,sans-serif;
}
@keyframes acrPop{ from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.acr-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid #e5e7eb; }
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
.acr-card:hover{ border-color:#A891FC; }
.acr-card:focus-visible{ outline:3px solid rgba(168,145,252,.45); outline-offset:2px; }
.acr-card.is-selected{ border-color:#A891FC; background:#f1eeff; box-shadow:0 0 0 1px #A891FC inset; }
.acr-card-top{ display:flex; align-items:center; justify-content:space-between; }
.acr-icon{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:#f3f4f6; color:#374151; transition:background .15s ease, color .15s ease; }
.acr-icon svg{ width:19px; height:19px; }
.acr-card.is-selected .acr-icon{ background:#A891FC; color:#fff; }
.acr-check{ display:none; width:20px; height:20px; border-radius:50%; background:#A891FC; color:#fff; align-items:center; justify-content:center; }
.acr-check svg{ width:12px; height:12px; }
.acr-card.is-selected .acr-check{ display:inline-flex; }
.acr-title{ font-size:14px; font-weight:700; color:#17223b; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.acr-current{ font-size:9px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#6650cc; background:#e6e0ff; border-radius:999px; padding:2px 7px; }
.acr-desc{ font-size:12px; color:#667085; line-height:1.45; }
.acr-access{ font-size:11px; color:#737c8b; line-height:1.4; border-top:1px dashed #e5e7eb; padding-top:7px; margin-top:auto; }
.acr-warn{ margin-top:14px; padding:10px 12px; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12px; line-height:1.5; }
.acr-footer{ display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:14px 20px; border-top:1px solid #e5e7eb; }
.acr-footer .acr-note{ margin-right:auto; font-size:12px; color:#737c8b; }
.acr-btn{ border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s ease, transform .15s ease; }
.acr-cancel{ background:#e5e7eb; color:#374151; }
.acr-cancel:hover{ background:#d1d5db; }
.acr-save{ background:#8b5cf6; color:#fff; }
.acr-save:hover:not(:disabled){ background:#7c3aed; transform:translateY(-2px); }
.acr-save:disabled{ background:#d8ccfb; cursor:not-allowed; }
.acr-edit-btn{ border:1px solid #cbbdfb; color:#6650cc; background:#fff; border-radius:8px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s ease; }
.acr-edit-btn:hover{ background:#f1eeff; }
</style>

<div id="acRoleModal" class="acr-overlay" aria-hidden="true">
    <div class="acr-box" role="dialog" aria-modal="true" aria-labelledby="acrTitle">
        <div class="acr-header">
            <div>
                <h3 id="acrTitle">Edit access</h3>
                <p id="acrSubtitle"></p>
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
