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
</div>
