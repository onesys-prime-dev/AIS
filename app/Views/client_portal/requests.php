<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
.requests-container {
    padding: 24px;
}

.page-title {
    font-size: 26px;
    font-weight: bold;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #6b7280;
    margin-bottom: 24px;
}

/* Grid layout */
.request-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

/* Card */
.request-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

/* Header */
.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #fee2e2;
}

.request-header.pending {
    background: #fee2e2
}

.request-header.approved {
    background: #dcfce7;
}

.request-header.rejected {
    background: #Fecaca;
}

.request-name {
    font-weight: 600;
}

.request-date {
    font-size: 14px;
    color: #6b7280;
}

/* Action buttons */
.request-actions {
    display: flex;
    gap: 8px;
}

.btn-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-reject {
    background: #ef4444;
}

.btn-approve {
    background: #16a34a;
}

/* Body */
.request-body {
    padding: 16px;
}

.request-row {
    margin-bottom: 10px;
}

.label {
    color: #6b7280;
}

.value {
    font-weight: 500;
}

.message {
    margin-top: 5px;
}

/* Status badges */
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    color: white;
    font-size: 14px;
}

.status-badge.approved {
    background: #16a34a;
}

.status-badge.rejected {
    background: #6b7280;
}

.status-badge.pending {
    background: #f59e0b;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.filter-container {
    display: flex;
    align-items: center;
}

.filter-dropdown {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: white;
    cursor: pointer;
}

</style>


<div class="requests-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Employee Requests</h1>
            <p class="page-subtitle">Keep track of employees status.</p>
        </div>

        <div class="filter-container">
            <select id="statusFilter" class="filter-dropdown">
                <option value="all">All Requests</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="request-grid">
        <?php foreach ($requests as $request): ?>
        <div class="request-card" data-status="<?= $request['status'] ?>">

            <div class="request-header <?= $request['status'] ?>">
                <div>
                    <div class="request-name"><?= esc($request['requested_by']) ?></div>
                    <div class="request-date"><?= esc(date('F j, Y', strtotime($request['date']))) ?></div>
                </div>

                <div class="request-actions">
                    <?php if ($request['status'] == 'pending'): ?>
                    <form action="<?= site_url('client_portal/requests/update/'.$request['id']) ?>" method="post">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn-circle btn-reject">✕</button>
                    </form>

                    <form action="<?= site_url('client_portal/requests/update/'.$request['id']) ?>" method="post">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn-circle btn-approve">✓</button>
                    </form>
                    <?php else: ?>

                    <span class="status-badge <?= $request['status'] ?>">
                        <?= ucfirst($request['status']) ?>
                    </span>

                    <?php endif; ?>
                </div>
            </div>

            <div class="request-body">
                <div class="request-row">
                    <span class="label">Request Type:</span>
                    <span class="value"><?= ucfirst($request['request_type']) ?></span>
                </div>

                <div class="request-row">
                    <span class="label">Message:</span>
                    <p class="message"><?= esc($request['reason']) ?></p>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('statusFilter').addEventListener('change', function() {
    const selected = this.value;
    const cards = document.querySelectorAll('.request-card');

    cards.forEach(card => {
        const status = card.dataset.status;

        if (selected === 'all' || status === selected) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?= $this->endSection() ?>