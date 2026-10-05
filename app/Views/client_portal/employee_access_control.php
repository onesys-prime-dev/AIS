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

    <div class="flex-1 min-h-0 overflow-y-auto" data-ac-panel="accounts">
        <!-- LIST VIEW -->
        <div id="acListView" class="rounded-2xl border border-gray-200 shadow-sm">
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

        <!-- EDIT VIEW: replaces the list while one account is being edited
             (layout follows the reference: label on the left, role cards on the
             right, access summary below, Update / Cancel at the bottom) -->
        <div id="acEditView" style="display:none;">
            <div class="acp-card">
                <div class="acp-head">
                    <button type="button" id="acpBack" class="acp-back">&#8249; Back to accounts</button>
                    <h3>Edit user role and access level</h3>
                    <p id="acpSubtitle"></p>
                </div>

                <div class="acp-row">
                    <div class="acp-label">Define user role<small>Pick one or more. Employee can't be combined with a staff role.</small></div>
                    <div id="acpCards" class="acp-cards" role="group" aria-label="Roles"></div>
                </div>

                <div id="acpWarn" class="acp-warn" style="display:none;"></div>

                <div class="acp-row">
                    <div class="acp-label">Allow access to<small>What the selected roles give this account. Change what a role can do in the Role Permissions tab.</small></div>
                    <div id="acpAccess" class="acp-access"></div>
                </div>

                <div class="acp-footer">
                    <span id="acpNote" class="acp-note"></span>
                    <button type="button" id="acpUpdate" class="acp-btn acp-update" disabled>Update</button>
                    <button type="button" id="acpCancel" class="acp-btn acp-cancel">Cancel</button>
                </div>
            </div>
        </div>
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

<!-- EDIT PANEL styling (role cards + access summary), used by #acEditView above.
     Self-contained so it doesn't depend on Tailwind classes the precompiled build may lack. -->
<style>
.acr-edit-btn{ border:1px solid #cbbdfb; color:#6650cc; background:#fff; border-radius:8px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s ease; }
.acr-edit-btn:hover{ background:#f1eeff; }

.acp-card{ background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 3px rgba(0,0,0,.06); max-width:980px; margin:0 auto 16px; font-family:Segoe UI,Arial,sans-serif; }
.acp-head{ position:relative; padding:18px 24px; border-bottom:1px solid #e5e7eb; text-align:center; }
.acp-head h3{ margin:0; font-size:18px; font-weight:600; color:#17223b; }
.acp-head p{ margin:4px 0 0; font-size:12px; color:#667085; }
.acp-back{ position:absolute; left:20px; top:18px; background:none; border:none; color:#6650cc; font-size:13px; font-weight:600; cursor:pointer; padding:0; }
.acp-back:hover{ text-decoration:underline; }

.acp-row{ display:grid; grid-template-columns:200px 1fr; gap:24px; padding:22px 24px; border-bottom:1px solid #eceef1; align-items:start; }
.acp-label{ font-size:14px; font-weight:600; color:#344054; }
.acp-label small{ display:block; margin-top:6px; font-size:11px; font-weight:400; color:#737c8b; line-height:1.5; }

.acp-cards{ display:grid; grid-template-columns:repeat(auto-fill, minmax(168px, 1fr)); gap:12px; }
.acp-role{
    position:relative; display:flex; flex-direction:column; gap:8px; text-align:left;
    padding:14px; border:1.5px solid #e5e7eb; border-radius:12px; background:#fff; cursor:pointer;
    transition:border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.acp-role:hover{ border-color:#A891FC; }
.acp-role:focus-visible{ outline:3px solid rgba(168,145,252,.45); outline-offset:2px; }
.acp-role.is-selected{ border-color:#A891FC; background:#f1eeff; box-shadow:0 0 0 1px #A891FC inset; }
.acp-role-top{ display:flex; align-items:center; justify-content:space-between; }
.acp-icon{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:#f3f4f6; color:#374151; transition:background .15s ease, color .15s ease; }
.acp-icon svg{ width:19px; height:19px; }
.acp-role.is-selected .acp-icon{ background:#A891FC; color:#fff; }
.acp-check{ display:none; width:20px; height:20px; border-radius:50%; background:#A891FC; color:#fff; align-items:center; justify-content:center; }
.acp-check svg{ width:12px; height:12px; }
.acp-role.is-selected .acp-check{ display:inline-flex; }
.acp-title{ font-size:14px; font-weight:700; color:#17223b; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.acp-current{ font-size:9px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#6650cc; background:#e6e0ff; border-radius:999px; padding:2px 7px; }
.acp-desc{ font-size:12px; color:#667085; line-height:1.45; }

.acp-warn{ margin:0 24px; padding:10px 12px; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12px; line-height:1.5; }

.acp-access{ border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#fafafa; }
.acp-access-head, .acp-mod{ display:grid; grid-template-columns:1fr 1.4fr; gap:12px; align-items:center; padding:10px 16px; }
.acp-access-head{ background:#f3f4f6; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#737c8b; }
.acp-mod{ border-top:1px solid #eceef1; font-size:13px; color:#344054; background:#fff; }
.acp-mod .acp-status{ display:inline-flex; align-items:center; gap:8px; font-size:12px; color:#737c8b; }
.acp-dot{ width:10px; height:10px; border-radius:50%; border:2px solid #d1d5db; background:#fff; flex:none; }
.acp-mod.is-on .acp-dot{ border-color:#A891FC; background:#A891FC; }
.acp-mod.is-on .acp-status{ color:#4b3fa8; font-weight:600; }
.acp-via{ font-weight:400; color:#737c8b; }

.acp-footer{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:12px; padding:20px 24px; }
.acp-note{ width:100%; text-align:center; font-size:12px; color:#737c8b; margin-bottom:2px; }
.acp-btn{ border:none; border-radius:8px; padding:9px 22px; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s ease, transform .15s ease; }
.acp-update{ background:#8b5cf6; color:#fff; }
.acp-update:hover:not(:disabled){ background:#7c3aed; transform:translateY(-2px); }
.acp-update:disabled{ background:#d8ccfb; cursor:not-allowed; }
.acp-cancel{ background:#e5e7eb; color:#374151; }
.acp-cancel:hover{ background:#d1d5db; }

@media (max-width:760px){
    .acp-row{ grid-template-columns:1fr; gap:12px; }
    .acp-back{ position:static; display:block; margin-bottom:8px; text-align:left; }
}
</style>
