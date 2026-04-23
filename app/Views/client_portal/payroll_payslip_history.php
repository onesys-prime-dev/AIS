<div class="bg-white rounded-2xl shadow-lg p-8 h-full border border-gray-300 flex flex-col">
    <h3 class="font-semibold text-xl mb-4">Payslip History (All Employees)</h3>
    <div class="overflow-auto">
        <table class="min-w-full border-collapse">
            <thead>
                <tr><th>Employee</th><th>Period</th><th>Net Pay</th><th>Date Sent</th><th>Download</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($payslips)): ?>
                    <?php foreach ($payslips as $ps): ?>
                    <tr class="border-b">
                        <td class="p-2"><?= esc($ps['employee_name']) ?></td>
                        <td class="p-2"><?= date('M d, Y', strtotime($ps['period_start'])) ?> - <?= date('M d, Y', strtotime($ps['period_end'])) ?></td>
                        <td class="p-2"><?= number_format($ps['net_pay'], 2) ?></td>
                        <td class="p-2"><?= date('Y-m-d H:i', strtotime($ps['created_at'])) ?></td>
                        <td class="p-2"><a href="<?= site_url('client_portal/payroll/downloadPayslip/' . $ps['id']) ?>" class="text-blue-600">PDF</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="p-4 text-center">No payslips generated yet. Use "Generate Payslip" and send to create records.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>