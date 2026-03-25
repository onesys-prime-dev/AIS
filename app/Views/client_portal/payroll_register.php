<div class="page-wrapper">

    <div class="card">

        <!-- HEADER -->
        <div class="card-header">
            <h2>Payroll Register</h2>
        </div>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name of Employees</th>
                        <th>Monthly Basic Salary</th>
                        <th>Semi-Monthly Basic Salary</th>
                        <th>Rice Subsidy</th>
                        <th>Uniform Allowance</th>
                        <th>Laundry Allowance</th>
                        <th>Other Non-Taxable</th>
                        <th>Salary Adjustment</th>
                        <th>Overtime</th>
                        <th>NSD</th>
                        <th>Absences / Tardiness</th>
                        <th>Holiday Pay</th>
                        <th>Gross Compensation</th>
                        <th>Gross Taxable</th>
                        <th>Non-Taxable</th>
                        <th>SSS</th>
                        <th>PHIC</th>
                        <th>HDMF</th>
                        <th>Net Taxable</th>
                        <th>Withholding Tax</th>
                        <th>SSS Loan</th>
                        <th>Advances</th>
                        <th>Net Pay</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($payrolls)): ?>
                        <?php $i = 1; foreach ($payrolls as $p): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= esc($p['employee_name']) ?></td>
                            <td><?= number_format($p['monthly_salary'], 2) ?></td>
                            <td><?= number_format($p['semi_monthly_salary'], 2) ?></td>
                            <td><?= number_format($p['rice_subsidy'], 2) ?></td>
                            <td><?= number_format($p['uniform_allowance'], 2) ?></td>
                            <td><?= number_format($p['laundry_allowance'], 2) ?></td>
                            <td><?= number_format($p['other_nontax'], 2) ?></td>
                            <td><?= number_format($p['salary_adjustment'], 2) ?></td>
                            <td><?= number_format($p['overtime_pay'], 2) ?></td>
                            <td><?= number_format($p['nsd_pay'], 2) ?></td>
                            <td><?= number_format($p['absent_deduction'], 2) ?></td>
                            <td><?= number_format($p['holiday_pay'], 2) ?></td>
                            <td><?= number_format($p['gross_pay'], 2) ?></td>
                            <td><?= number_format($p['gross_taxable'], 2) ?></td>
                            <td><?= number_format($p['non_taxable'], 2) ?></td>
                            <td><?= number_format($p['sss'], 2) ?></td>
                            <td><?= number_format($p['philhealth'], 2) ?></td>
                            <td><?= number_format($p['pagibig'], 2) ?></td>
                            <td><?= number_format($p['net_taxable'], 2) ?></td>
                            <td><?= number_format($p['withholding_tax'], 2) ?></td>
                            <td><?= number_format($p['sss_loan'], 2) ?></td>
                            <td><?= number_format($p['advances'], 2) ?></td>
                            <td class="net-pay"><?= number_format($p['net_pay'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="24" class="no-data">No payroll records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<style>
.page-wrapper{
    padding: 20px;
    height: 100%;
}

/* CARD */
.card{
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #9E9E9E;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-header{
    padding: 20px 25px;
    border-bottom: 1px solid #dcdcdc;
}

.card-header h2{
    font-size: 18px;
    color: #555;
    font-weight: 600;
}

/* TABLE */
.table-wrapper{
    overflow-x: auto;
    padding: 20px;
}

.payroll-table{
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.payroll-table th{
    background: #DCDCDC;
    color: #555;
    padding: 10px;
    border: 1px solid #9E9E9E;
    text-align: center;
}

.payroll-table td{
    padding: 8px;
    border: 1px solid #cfcfcf;
    text-align: center;
}

.payroll-table tr:nth-child(even){
    background: #f7f7f7;
}

.net-pay{
    font-weight: bold;
    color: #2e7d32;
}

.no-data{
    text-align: center;
    padding: 20px;
    color: #888;
}
</style>