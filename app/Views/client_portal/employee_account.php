<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- ACCOUNT PAGE -->
<div class="account-container">

    <!-- TOP TABS -->
    <div class="tabs">
        <div class="tab active">Account</div>
        <div class="tab">Security</div>
        <!-- <div class="tab">Customization</div>
        <div class="tab">Notifications</div> -->
    </div>

    <!-- GRID -->
    <div class="account-grid scroll-bar">

        <!-- ABOUT ME -->
        <div class="card">
            <h2>About Me</h2>

            <div class="about-content">
                <div class="about-avatar"></div>

                <div class="about-info">
                    <div class="name-row">
                        <div>
                            <div class="name">Barb Backes</div>
                            <div class="joined">Joined in 2014</div>
                        </div>
                        <a href="#">Edit</a>
                    </div>

                    <div class="paypal-link">paypal.me/bbackes</div>
                    <div class="subtext">
                        With this link, anyone can send you money in seconds.
                    </div>

                    <a href="#" class="photo-link">Update photo</a>
                </div>
            </div>
        </div>

        <!-- ADDRESS -->
        <div class="card">
            <div class="card-header">
                <h2>Address</h2>
                <span class="plus">+</span>
            </div>

            <div class="item">
                <div>
                    1602 E. Weathervane Lane<br>
                    TEMPE, AZ 85283<br>
                    <span class="muted">Primary, Billing address</span>
                </div>
                <div class="actions">Edit | Remove</div>
            </div>

            <div class="item">
                <div>
                    7811 N. via Del Mundo<br>
                    SCOTTSDALE, AZ 85258
                </div>
                <div class="actions">Edit | Remove</div>
            </div>
        </div>

        <!-- ACCOUNT OPTIONS -->
        <div class="card">
            <h2>Account options</h2>

            <div class="form-group">
                <label>Language</label>
                <select>
                    <option>English</option>
                </select>
            </div>

            <div class="form-group">
                <label>Time zone</label>
                <select>
                    <option>(GMT-04:00) Atlantic Standard Time</option>
                </select>
            </div>

            <div class="form-group row">
                <label>SSN</label>
                <span>xxx-xx-xx83 <a href="#">Edit</a></span>
            </div>

            <a href="#" class="link">Upgrade to a Business account</a>
            <a href="#" class="link danger">Close your account</a>
        </div>

        <!-- EMAIL -->
        <div class="card">
            <div class="card-header">
                <h2>Email</h2>
                <span class="plus">+</span>
            </div>

            <div class="item">
                <div>
                    barb.backes@gmail.com<br>
                    <span class="muted">Primary</span>
                </div>
                <div class="actions">Edit | Remove</div>
            </div>
        </div>

        <!-- PHONE -->
        <div class="card">
            <div class="card-header">
                <h2>Phone</h2>
                <span class="plus">+</span>
            </div>

            <div class="item">
                <div>
                    (480) 628-6148<br>
                    <span class="muted">Mobile, Primary</span>
                </div>
                <div class="actions">Edit | Remove</div>
            </div>

            <div class="item">
                <div>(480) 628-6148</div>
                <div class="actions">Edit | Remove</div>
            </div>
        </div>

    </div>
</div>

<style>
    /* CONTAINER */
    .account-container{
        font-family:Segoe UI, Arial;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
        background-color: #e5e7eb;
    }

    /* TABS */
    .tabs{
        display:flex;
        align-items:center;
        gap:30px;
        background:#2E2E2E;
        color:white;
        padding:15px 25px;
        min-height:70px;
        position: sticky;
        top: 0;
        z-index: 10;
        flex-shrink: 0;
    }

    .tab{
        cursor:pointer;
        opacity:0.8;
    }

    .tab.active{
        font-weight:600;
        opacity:1;
        position:relative;
    }

    .tab.active::after{
        content:'';
        position:absolute;
        bottom:-15px;
        left:0;
        width:100%;
        height:3px;
        background:white;
    }

    /* GRID */
    .account-grid{
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:25px;
        padding: 2rem;
    }

    /* CARD */
    .card{
        background:white;
        border-radius:10px;
        padding:25px;
        box-shadow:0 4px 15px rgba(0,0,0,0.05);
    }

    .card h2{
        font-size:20px;
        margin-bottom:20px;
        font-weight:500;
    }

    /* HEADER WITH PLUS */
    .card-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    .plus{
        font-size:20px;
        cursor:pointer;
    }

    /* ABOUT */
    .about-content{
        display:flex;
        gap:20px;
    }

    .about-avatar{
        width:80px;
        height:80px;
        border-radius:50%;
        background:#e5e7eb;
    }

    .about-info{
        flex:1;
    }

    .name{
        font-weight:600;
    }

    .joined{
        font-size:12px;
        color:#9ca3af;
    }

    .name-row{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    .paypal-link{
        margin-top:10px;
        font-size:14px;
        border-bottom:1px solid #ddd;
        padding-bottom:5px;
    }

    .subtext{
        font-size:12px;
        color:#9ca3af;
        margin-top:5px;
    }

    .photo-link{
        display:inline-block;
        margin-top:10px;
        font-size:12px;
        color:#1f8dbd;
    }

    /* ITEMS */
    .item{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        padding:10px 0;
        border-bottom:1px solid #eee;
    }

    .item:last-child{
        border-bottom:none;
    }

    .actions{
        font-size:13px;
        color:#1f8dbd;
        cursor:pointer;
    }

    /* FORM */
    .form-group{
        margin-bottom:15px;
    }

    .form-group label{
        display:block;
        font-size:13px;
        margin-bottom:5px;
    }

    .form-group select{
        width:100%;
        padding:8px;
        border-radius:6px;
        border:1px solid #ddd;
    }

    .row{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    /* LINKS */
    .link{
        display:block;
        margin-top:10px;
        font-size:13px;
        color:#1f8dbd;
    }

    .link.danger{
        color:#ef4444;
    }

    /* MUTED */
    .muted{
        font-size:12px;
        color:#9ca3af;
    }
</style>

<?= $this->endSection() ?>
<!-- 
<?= $this->section('modals') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<?= $this->endSection() ?> -->