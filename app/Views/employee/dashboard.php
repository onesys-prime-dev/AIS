<h1>Employee Dashboard</h1>

<?php if(session()->getFlashdata('success')): ?>
    <p style="color: green;"><?= session()->getFlashdata('success') ?></p>
<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>
    <p style="color: red;"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>


<?php if($currentAttendance): ?>
    <!-- User is currently timed in -->
    <p>Time in: <?= $currentAttendance['time_in'] ?></p>
    <a href="<?= base_url("employee/time-out/{$currentAttendance['id']}") ?>">Time Out</a>

<?php else: ?>

    <!-- Show timeout result -->
    <?php if(session()->getFlashdata('timeout_time')): ?>
        <p>Time out recorded at: <?= session()->getFlashdata('timeout_time') ?></p>
        <p>Total hours worked: <?= session()->getFlashdata('total_hours') ?></p>
    <?php endif; ?>

    <!-- User can time in -->
    <a href="<?= base_url('employee/time-in') ?>">Time In</a>

<?php endif; ?>


<br><br>
<a href="<?= route_to('logout') ?>">Logout</a>