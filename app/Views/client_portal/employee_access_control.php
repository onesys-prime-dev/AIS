<div class="flex flex-col w-full h-full min-w-0 min-h-0">

    <div class="flex justify-between items-center my-4 shrink-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Access Control</h2>
            <p class="text-sm text-gray-500 mt-1">Assign each account's portal role. This only changes what they can access — it doesn't affect their employee record.</p>
        </div>
    </div>

    <div class="flex gap-2 mb-4 shrink-0" id="acSubTabs">
        <button type="button" class="ac-subtab" data-ac-tab="accounts">Accounts</button>
        <button type="button" class="ac-subtab" data-ac-tab="team">Team Head Assignments</button>
        <button type="button" class="ac-subtab" data-ac-tab="permissions">Role Permissions</button>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto rounded-2xl border border-gray-200 shadow-sm" data-ac-panel="accounts">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                </tr>
            </thead>
            <tbody id="accessControlBody" class="divide-y divide-gray-100"></tbody>
        </table>
    </div>

    <!-- TEAM HEAD ASSIGNMENTS -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="teamAssignmentsSection" data-ac-panel="team" style="display:none;">
        <h3 class="text-lg font-semibold text-gray-800">Team Head Assignments</h3>
        <p class="text-sm text-gray-500 mt-1">Pick exactly which employees each Team Head is allowed to review requests and attendance for. A Team Head with no one assigned can't review anyone.</p>
        <div id="teamAssignmentsBody" class="mt-3 space-y-3 pb-4"></div>
    </div>

    <!-- ROLE PERMISSIONS -->
    <div class="flex-1 min-h-0 overflow-y-auto" id="rolePermissionsSection" data-ac-panel="permissions" style="display:none;">
        <h3 class="text-lg font-semibold text-gray-800">Role Permissions</h3>
        <p class="text-sm text-gray-500 mt-1">What each role can access across the portal. Super Admin isn't listed — it always has full access and can't be limited here.</p>
        <div class="flex gap-2 mt-3" id="rolePermissionTabs"></div>
        <div id="rolePermissionBody" class="mt-3 mb-4 rounded-2xl border border-gray-200 shadow-sm p-4"></div>
    </div>
</div>
