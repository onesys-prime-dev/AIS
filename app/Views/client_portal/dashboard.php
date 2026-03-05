<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-5">

<h1>Employee Dashboard</h1>

<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>


<?php if($currentAttendance): ?>
    
    <p><strong>Time in:</strong> <?= $currentAttendance['time_in'] ?></p>

    <a class="btn btn-danger" href="<?= base_url("client_portal/time-out/{$currentAttendance['id']}") ?>">
        Time Out
    </a>

<?php else: ?>

    <?php if(session()->getFlashdata('timeout_time')): ?>
        <div class="alert alert-info">
            <p>Time out recorded at: <?= session()->getFlashdata('timeout_time') ?></p>
            <p>Total hours worked: <?= session()->getFlashdata('total_hours') ?></p>
        </div>
    <?php endif; ?>

    <a class="btn btn-success" href="<?= base_url('client_portal/time-in') ?>">
        Time In
    </a>

<?php endif; ?>


<hr>

<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
    Submit Request
</button>


<br><br>

<a class="btn btn-secondary" href="<?= route_to('logout') ?>">
    Logout
</a>



<!-- REQUEST MODAL -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Submit Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="post" action="<?= base_url('client_portal/request/submit') ?>">

                <?= csrf_field() ?>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Request Type</label>
                        <select name="request_type" class="form-control" required>
                            <option value="">Select</option>
                            <option value="leave">Leave</option>
                            <option value="overtime">Overtime</option>
                            <option value="undertime">Undertime</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Submit Request
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
