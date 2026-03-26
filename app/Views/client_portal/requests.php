<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
.requests-container { /* Create global css for this default alignment of content in the main*/
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
    padding-left: 2rem;
    padding-right: 2rem;
    padding-bottom: 2rem;
}

.page-holder{
    padding: 0.25rem;
}

.page-title {
    font-size: 1.875rem;   
    line-height: 2.25rem;
    font-weight: 700;      
    color: #1f2937;       
    margin-bottom: 0.25rem;
}

.page-subtitle {
    color: #6b7280;   
    font-size: 0.875rem; 
    line-height: 1.25rem;
}

/* Grid layout */
.request-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

/* Card */
.request-card {
    background: whitesmoke;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

.request-cards-container{
    background-color: white;
    padding: 2rem;
    border-radius: 1.25rem;
    height: 100%;
    border: 1px solid rgb(156 163 175 / 1);
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

.request-person {
    display: flex;
    align-items: center;
    gap: 10px;
}

.request-avatar {
    width: 50px;
    height: 50px;
    border-radius: 999px;
    background-color: #e5e7eb;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #374151;
    border: 1px solid #d1d5db;
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
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.filter-container {
    display: flex;         
    align-items: center;   
    gap: 1rem;
}

.filter-dropdown {
    background-color: #FF2D2D;  
    color: #ffffff;             
    padding-left: 0.75rem;      
    padding-right: 0.75rem;     
    padding-top: 0.75rem;       
    padding-bottom: 0.75rem;    
    border-radius: 0.5rem;      
    box-shadow: 0 1px 3px rgba(0,0,0,0.1),
                0 1px 2px rgba(0,0,0,0.06);
    display: flex;              
    align-items: center;        
    gap: 0.5rem;
}

.duration{
    margin-left: 5px;
}

</style>


<div class="requests-container">

    <div class="page-header">
        <div class="page-holder">
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

    <div class="request-cards-container scroll-bar">
        <div class="request-grid">
            <?php foreach ($requests as $request): ?>
            <div class="request-card" data-status="<?= $request['status'] ?>">

                <?php
                    $startDate = ! empty($request['start_date']) ? date('F j, Y', strtotime($request['start_date'])) : 'N/A';
                    $endDate = ! empty($request['end_date']) ? date('F j, Y', strtotime($request['end_date'])) : null;
                    $dateText = $startDate;
                    if ($endDate !== null && $endDate !== $startDate) {
                        $dateText .= ' to ' . $endDate;
                    }
                    $duration = (int) ($request['duration'] ?? 1);
                    $displayName = (string) ($request['requested_by_name'] ?? (string) ($request['requested_by'] ?? 'U'));
                    $initialsSource = trim($displayName);
                    $avatarInitials = strtoupper(substr($initialsSource !== '' ? $initialsSource : 'U', 0, 2));
                ?>

                <div class="request-header <?= $request['status'] ?>">
                    <div class="request-person">
                        <?php if(auth()->user()->inGroup('client_payroll_staff')): ?>
                            <?php if (! empty($request['photo_path'])): ?>
                                <div class="request-avatar" style="background-image:url('<?= esc($request['photo_path']) ?>');"></div>
                            <?php else: ?>
                                <div class="request-avatar"><?= esc($avatarInitials) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div>
                            <div class="request-name"><?= esc($request['requested_by_name'] ?? (string) $request['requested_by']) ?></div>
                            <div class="request-date">Employee No: <?= esc($request['employee_number'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <div class="request-actions">
                        <?php if (auth()->user()->inGroup('client_payroll_staff') && $request['status'] == 'pending'): ?>
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
                        <span class="label">Duration:</span>
                        <p class="message"><?= esc($dateText) ?>
                            <span class="duration">(<?= esc((string) $duration) ?> day<?= $duration !== 1 ? 's' : '' ?>)</span>
                        </p>
                    </div>

                    <div class="request-row">
                        <span class="label">Message:</span>
                        <p class="message"><?= esc($request['reason']) ?></p>
                    </div>

                    <!-- <div class="request-row">
                        <span class="label">Approval Code:</span>
                        <span class="value"><?= esc($request['approval_code'] ?? 'N/A') ?></span>
                    </div> -->
                </div>

            </div>
            <?php endforeach; ?>
        </div>
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