
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timesheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        p {
            margin: 0 0 20px 0;
            color: #555;
            font-size: 14px;
        }

        .date-range {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        thead tr th {
            background: #e0e0e0;
            color: #333;
            font-weight: bold;
            padding: 12px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            text-align: left;
        }

        tbody tr {
            border-bottom: 1px solid #ddd;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 10px 12px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
        }

        .actions i {
            cursor: pointer;
            font-size: 16px;
            color: #555;
        }

        .actions i:hover {
            color: #000;
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
        <div class="wrapper" style="display:flex;">
        <!-- Sidebar -->
        <?= view('partials/sidebar') ?>
    <div class="container">

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time Start</th>
                    <th>Time End</th>
                    <th>Client</th>
                    <th>Task Performed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($timesheet)): ?>
                    <?php foreach($timesheet as $task): ?>
                        <tr>
                            <td><?= date('m/d/y', strtotime($task['date'])) ?></td>
                            <td><?= date('H:i:s', strtotime($task['start_time'])) ?></td>
                            <td><?= date('H:i:s', strtotime($task['end_time'])) ?></td>
                            <td><?= esc($task['department']) ?></td>
                            <td><?= esc($task['task_performed']) ?></td>
                            <td class="actions">
                                <i class="fa-regular fa-pen-to-square" title="Edit"></i>
                                <i class="fa-regular fa-trash-can" title="Delete"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#888;">No timesheets found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>