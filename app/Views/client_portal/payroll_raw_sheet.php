<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-hidden border border-gray-300 flex flex-col">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-semibold text-xl">Raw Sheet – <?= esc($cutoff['name']) ?></h3>
            <p class="text-gray-600"><?= date('M d, Y', strtotime($cutoff['start_date'])) ?> to <?= date('M d, Y', strtotime($cutoff['end_date'])) ?></p>
        </div>
        <div class="text-sm text-gray-500">
            <strong>Note:</strong> Worked days, tardy minutes, night diff, and overtime are calculated from attendance data.
        </div>
    </div>

    <form method="post" action="<?= site_url('client_portal/payroll/compute-all/' . $cutoff['id']) ?>">
        <?= csrf_field() ?>
        <div class="overflow-auto max-h-[70vh]">
            <table class="min-w-[1800px] border-collapse text-sm">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="p-2 border">Employee</th>
                        <th class="p-2 border">Worked Days</th>
                        <th class="p-2 border">Total Working Days</th>
                        <th class="p-2 border">Absent Days</th>
                        <th class="p-2 border">Regular Hours</th>
                        <th class="p-2 border">Night Diff Hrs</th>
                        <th class="p-2 border">Tardy (min)</th>
                        <th class="p-2 border">Reg OT (≤10)</th>
                        <th class="p-2 border">Reg OT (>10)</th>
                        <th class="p-2 border">Rest OT (≤10)</th>
                        <th class="p-2 border">Rest OT (>10)</th>
                        <th class="p-2 border">Holiday OT (≤10)</th>
                        <th class="p-2 border">Holiday OT (>10)</th>
                        <th class="p-2 border">Unpaid Leave</th>
                        <th class="p-2 border">Undertime Hrs</th>
                        <?php foreach ($deMinimisCategories as $key => $label): ?>
                        <th class="p-2 border"><?= esc($label) ?></th>
                        <?php endforeach; ?>
                        <th class="p-2 border">Other Taxable</th>
                        <th class="p-2 border">SSS Loan</th>
                        <th class="p-2 border">Calamity Loan</th>
                        <th class="p-2 border">Salary Advance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summaries as $s): 
                        $deMin = json_decode($s['de_minimis_json'], true) ?? [];
                    ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2 border font-medium"><?= esc($s['first_name'] . ' ' . $s['last_name']) ?></td>
                        <td class="p-2 border text-center">
                            <input type="number" step="1" class="w-16 p-1 border rounded text-center bg-gray-100" name="worked_days[<?= $s['id'] ?>]" value="<?= $s['worked_days'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center bg-gray-50"><?= $s['total_working_days'] ?? 0 ?></td>
                        <td class="p-2 border text-center">
                            <input type="number" step="1" class="w-16 p-1 border rounded text-center bg-gray-100" name="absent_days[<?= $s['id'] ?>]" value="<?= $s['absent_days'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-20 p-1 border rounded text-center bg-gray-100" name="regular_hours[<?= $s['id'] ?>]" value="<?= $s['regular_hours'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-20 p-1 border rounded text-center bg-gray-100" name="night_diff_hours[<?= $s['id'] ?>]" value="<?= $s['night_diff_hours'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="1" class="w-16 p-1 border rounded text-center bg-gray-100" name="tardy_minutes[<?= $s['id'] ?>]" value="<?= $s['tardy_minutes'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="reg_ot_hours_le10[<?= $s['id'] ?>]" value="<?= $s['reg_ot_hours_le10'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="reg_ot_hours_excess[<?= $s['id'] ?>]" value="<?= $s['reg_ot_hours_excess'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="rest_ot_hours_le10[<?= $s['id'] ?>]" value="<?= $s['rest_ot_hours_le10'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="rest_ot_hours_excess[<?= $s['id'] ?>]" value="<?= $s['rest_ot_hours_excess'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="holiday_ot_hours_le10[<?= $s['id'] ?>]" value="<?= $s['holiday_ot_hours_le10'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded text-center bg-gray-100" name="holiday_ot_hours_excess[<?= $s['id'] ?>]" value="<?= $s['holiday_ot_hours_excess'] ?>" readonly>
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded" name="unpaid_leave_days[<?= $s['id'] ?>]" value="<?= $s['unpaid_leave_days'] ?>">
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-16 p-1 border rounded" name="undertime_hours[<?= $s['id'] ?>]" value="<?= $s['undertime_hours'] ?>">
                        </td>
                        <?php foreach ($deMinimisCategories as $key => $label): ?>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-24 p-1 border rounded" name="<?= $key ?>[<?= $s['id'] ?>]" value="<?= $deMin[$key] ?? 0 ?>">
                        </td>
                        <?php endforeach; ?>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-24 p-1 border rounded" name="other_taxable[<?= $s['id'] ?>]" value="<?= $s['other_taxable'] ?>">
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-24 p-1 border rounded" name="sss_loan[<?= $s['id'] ?>]" value="<?= $s['sss_loan'] ?>">
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-24 p-1 border rounded" name="calamity_loan[<?= $s['id'] ?>]" value="<?= $s['calamity_loan'] ?>">
                        </td>
                        <td class="p-2 border text-center">
                            <input type="number" step="0.01" class="w-24 p-1 border rounded" name="salary_advance[<?= $s['id'] ?>]" value="<?= $s['salary_advance'] ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow">
                Compute Payroll
            </button>
            <a href="<?= site_url('client_portal/payroll-cutoffs') ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg shadow">
                Back to Cutoffs
            </a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>