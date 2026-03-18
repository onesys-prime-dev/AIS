<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Page */
.page {
    background: #f4f4f4;
    min-height: 100vh;
    padding: 25px;
    font-family: Arial, sans-serif;
    width: 100%;
}

/* IMPORTANT FIX */
.content-wrapper {
    width: 100%;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    width: 100%;
}

.header h1 {
    margin: 0;
    font-size: 28px;
    color: #333;
}

.header p {
    margin: 5px 0 0;
    color: #777;
    font-size: 13px;
}

/* Buttons */
.actions {
    display: flex;
    gap: 10px;
}

.btn {
    border: none;
    border-radius: 10px;
    padding: 12px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn img {
    width: 18px;
}

.btn-white {
    background: #fff;
}

.btn-white:hover {
    background: #eee;
}

.btn-red {
    background: #ff3b30;
    color: #fff;
}

.btn-red:hover {
    background: #e63228;
}

/* CARD FIX (THIS WAS YOUR PROBLEM) */
.card {
    width: 100%;
    max-width: 100%; /* prevents shrinking */
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* Date */
.date-range {
    font-weight: 600;
    margin-bottom: 15px;
    color: #444;
}

/* TABLE FULL WIDTH */
.table {
    width: 100%;
}

/* Header row */
.table-header {
    background: #e5e5e5;
    display: grid;
    width: 100%;
    grid-template-columns: 
        120px
        140px
        140px
        250px
        1fr
        120px;
    padding: 12px;
    font-weight: bold;
    font-size: 14px;
    color: #555;
}

/* Rows */
.table-row {
    display: grid;
    width: 100%;
    grid-template-columns: 
        120px
        140px
        140px
        250px
        1fr
        120px;
    padding: 14px 12px;
    font-size: 14px;
    color: #444;
    border-top: 1px solid #eee;
    align-items: center;
}

.table-row:hover {
    background: #fafafa;
}

/* spacing */
.table-header div,
.table-row div {
    padding-right: 10px;
}

/* truncate client */
.table-row div:nth-child(4) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Actions */
.actions-icons {
    display: flex;
    gap: 12px;
}

.actions-icons i {
    cursor: pointer;
    color: #555;
}

.actions-icons i:hover {
    color: #000;
}
</style>

<div class="page">
    <div class="content-wrapper">

        <!-- Header -->
        <div class="header">
            <div>
                <h1>Timesheet</h1>
                <p>Manage your timesheet and payslip here.</p>
            </div>

            <div class="actions">
                <button class="btn btn-white">
                    <img src="<?= base_url('icons/alarms.png'); ?>">
                </button>

                <button class="btn btn-red" data-modal-open="timesheets">
                    <img src="<?= base_url('icons/payslip.png'); ?>">
                    View Payslip
                </button>
            </div>
        </div>

        <!-- Card -->
        <div class="card">

            <div class="date-range">
                February 5, 2026 – February 11, 2026
            </div>

            <!-- Table -->
            <div class="table">

                <div class="table-header">
                    <div>Date</div>
                    <div>Time Start</div>
                    <div>Time End</div>
                    <div>Client</div>
                    <div>Task Performed</div>
                    <div>Action</div>
                </div>

                <?php for($i=0; $i<7; $i++): ?>
                <div class="table-row">
                    <div>02/05/26</div>
                    <div>7:30:00</div>
                    <div>12:00:00</div>
                    <div>ONESYS PRIME SOLUTIONS INC.</div>
                    <div>Coding Session</div>

                    <div class="actions-icons">
                        <i class="fas fa-pen"></i>
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
                <?php endfor; ?>

            </div>

        </div>

    </div>
</div>

<?= $this->endSection() ?>