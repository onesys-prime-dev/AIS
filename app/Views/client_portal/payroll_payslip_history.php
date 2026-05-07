<div class="bg-white rounded-2xl shadow-lg p-8 h-full border border-gray-300 flex flex-col">
    <h3 class="font-semibold text-xl mb-4">Payslip History (All Employees)</h3>
    <div class="overflow-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="p-2 font-semibold border-b">Employee</th>
                    <th class="p-2 font-semibold border-b">Period</th>
                    <th class="p-2 font-semibold border-b text-right">Net Pay</th>
                    <th class="p-2 font-semibold border-b">Date Sent</th>
                    <th class="p-2 font-semibold border-b text-center">Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payslips)): ?>
                    <?php foreach ($payslips as $ps): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2"><?= esc((string) ($ps['employee_name'] ?? '---')) ?></td>
                        <td class="p-2 whitespace-nowrap"><?= !empty($ps['period_start']) ? date('M d, Y', strtotime($ps['period_start'])) : '---' ?> &ndash; <?= !empty($ps['period_end']) ? date('M d, Y', strtotime($ps['period_end'])) : '---' ?></td>
                        <td class="p-2 text-right"><?= number_format((float) ($ps['net_pay'] ?? 0), 2) ?></td>
                        <td class="p-2 whitespace-nowrap"><?= !empty($ps['sent_at']) ? date('M d, Y H:i:s', strtotime($ps['sent_at'])) : '<span class="text-gray-400">&mdash;</span>' ?></td>
                        <td class="p-2 text-center"><a href="<?= site_url('client_portal/payroll/downloadPayslip/' . ($ps['id'] ?? 0)) ?>" class="text-blue-600 hover:underline">PDF</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="p-6 text-center text-gray-400">No payslips generated yet. Use "Generate Payslip" to create records.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>