<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company { font-size: 24px; font-weight: bold; }
        .title { font-size: 18px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        th { background: #f0f0f0; text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .signature { margin-top: 30px; text-align: right; font-style: italic; }
    </style>
</head>
<body>
<div class="header">
    <div class="company"><?= esc($company) ?></div>
    <div class="title">EMPLOYEE PAYSLIP</div>
</div>

<table>
    <tr>
        <td width="30%"><strong>Employee:</strong></td>
        <td><?= esc($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
        <td width="30%"><strong>Payroll Period:</strong></td>
        <td><?= date('M d, Y', strtotime($payroll['period_start'])) ?> - <?= date('M d, Y', strtotime($payroll['period_end'])) ?></td>
    </tr>
    <tr>
        <td><strong>Employee #:</strong></td>
        <td><?= esc($employee['employee_number']) ?></td>
        <td><strong>Position:</strong></td>
        <td><?= esc($payroll['position'] ?? 'N/A') ?></td>
    </tr>
</table>

<table>
    <thead>
        <tr><th colspan="2">EARNINGS</th><th colspan="2">DEDUCTIONS</th></tr>
    </thead>
    <tbody>
        <tr><td>Basic Pay</td><td class="right"><?= number_format($payroll['basic_pay'], 2) ?></td>
            <td>SSS</td><td class="right"><?= number_format($payroll['sss_ee'], 2) ?></td></tr>
        <tr><td>Overtime Pay</td><td class="right"><?= number_format($payroll['overtime_pay'], 2) ?></td>
            <td>PhilHealth</td><td class="right"><?= number_format($payroll['phic_ee'], 2) ?></td></tr>
        <tr><td>De Minimis</td><td class="right"><?= number_format($payroll['de_minimis'], 2) ?></td>
            <td>Pag-IBIG</td><td class="right"><?= number_format($payroll['hdmf_ee'], 2) ?></td></tr>
        <tr><td>Other Taxable</td><td class="right"><?= number_format($payroll['other_earnings_taxable'], 2) ?></td>
            <td>Withholding Tax</td><td class="right"><?= number_format($payroll['withholding_tax'], 2) ?></td></tr>
        <tr><td colspan="2"></td>
            <td>SSS Loan</td><td class="right"><?= number_format($payroll['sss_loan'], 2) ?></td></tr>
        <tr><td colspan="2"></td>
            <td>Calamity Loan</td><td class="right"><?= number_format($payroll['calamity_loan'], 2) ?></td></tr>
        <tr><td colspan="2"></td>
            <td>Salary Advance</td><td class="right"><?= number_format($payroll['salary_advance'], 2) ?></td></tr>
        <tr><td colspan="2"></td>
            <td>Other Deductions</td><td class="right"><?= number_format($payroll['other_deductions'], 2) ?></td></tr>
        <tr class="bold"><td>TOTAL EARNINGS</td><td class="right"><?= number_format($payroll['total_earnings'], 2) ?></td>
            <td>TOTAL DEDUCTIONS</td><td class="right"><?= number_format($payroll['total_deductions'], 2) ?></td></tr>
    </tbody>
</table>

<div style="text-align: right; margin-top: 20px;">
    <strong>NET PAY: <?= number_format($payroll['net_pay'], 2) ?></strong>
</div>
<div class="signature">Employee signature: ___________________________</div>
</body>
</html>