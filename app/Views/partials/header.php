<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="app-header">
    <!-- LEFT SIDE -->
    <div class="header-left">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
            type="text"
            placeholder="What are you looking for?"
            class="search-input"
            >
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="header-right">
        <!-- BELL -->
        <div class="notif-wrapper">
            <button class="notif-btn" onclick="toggleNotif()">
                <i class="fa-solid fa-bell"></i>
                <?php if (!empty($notifications) && count($notifications) > 0): ?>
                    <span class="notif-badge">
                <?= count($notifications) ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- BELL DROPDOWN -->
            <div id="notifDropdown" class="notif-dropdown">
                <div class="notif-header">Notifications</div>
                <?php if (!empty($notifications)): ?>
                <?php foreach($notifications as $n): ?>
                    <div class="notif-item">
                    <div class="notif-title"><?= esc($n['title']) ?></div>
                    <div class="notif-desc"><?= esc($n['message']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="notif-empty">No notifications</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- USER -->
        <div class="user-wrapper">
            <?php $user = auth()->user(); ?>
            <?php
                $headerIdentity = $headerIdentity ?? [];
                $photoUrl = (string) ($headerIdentity['photo_url'] ?? '');
                $displayName = (string) ($headerIdentity['display_name'] ?? ($user->username ?? 'User'));
                $displayEmail = (string) ($headerIdentity['display_email'] ?? ($user->email ?? ''));
                $avatarSource = trim((string) ($displayName !== '' ? $displayName : $displayEmail));
                $avatarInitials = strtoupper(substr($avatarSource !== '' ? $avatarSource : 'U', 0, 2));
            ?>
            <div class="user-profile" onclick="toggleUserMenu()">
                <?php if ($photoUrl !== ''): ?>
                    <div class="avatar avatar-photo" style="background-image:url('<?= esc($photoUrl) ?>');" aria-label="Profile photo"></div>
                <?php else: ?>
                    <div class="avatar">
                        <?= esc($avatarInitials) ?>
                    </div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?= esc($displayName !== '' ? $displayName : 'User') ?></div>
                    <div class="user-email"><?= esc($displayEmail) ?></div>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>

            <!-- USER DROPDOWN -->
            <div id="userDropdown" class="user-dropdown">
                <a href="<?= site_url('client_portal/profile') ?>">Profile</a>
                <a href="<?= site_url('settings/customization') ?>">Settings</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            </div>
        </div>
    </div>
</header>

<style>

:root {
    --header-bg: #ffffff;
    --header-text: #000000;

    --header-border: #e5e7eb;
    --header-shadow: 0 4px 12px rgba(0,0,0,0.1);

    --search-bg: #DAD7D7;
    --search-text: #374151;
    --search-placeholder: #6b7280;

    --dropdown-bg: #ffffff;
    --dropdown-text: #374151;
}

/* HEADER */
.app-header{
    height:80px;
    background: var(--header-bg);
    border-bottom:1px solid var(--header-border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 35px;
    font-family:Segoe UI, Arial;
    box-shadow: var(--header-shadow);

    position:relative;
    z-index:1000;
}

/* SEARCH */
.search-box{
    position:relative;
    width:420px;
}

.search-input{
    width:100%;
    height:42px;
    border:none;
    outline:none;
    border-radius:999px;
    background: var(--search-bg);
    padding-left:45px;
    font-size:14px;
    color: var(--search-text);
}

.search-input::placeholder {
    color: var(--search-placeholder);
    opacity:1;
}

.search-icon{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color: var(--search-placeholder);
}

/* RIGHT SIDE */
.header-right{
    display:flex;
    align-items:center;
    gap:30px;
}

/* BELL */
.notif-wrapper{
    position:relative;
}

.notif-btn{
    background:none;
    border:none;
    font-size:20px;
    cursor:pointer;
    color: var(--header-text);
    position:relative;
}

.notif-badge{
    position:absolute;
    top:-4px;
    right:-6px;
    background:#ef4444;
    color:white;
    width:16px;
    height:16px;
    border-radius:50%;
    font-size:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

/* NOTIF DROPDOWN */
.notif-dropdown{
    position:absolute;
    right:0;
    top:50px;
    width:280px;
    background: var(--dropdown-bg);
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
    display:none;
    padding:10px 0;

    z-index:9999;
}

.notif-header{
    font-weight:600;
    padding:10px 15px;
    border-bottom:1px solid #eee;
    color: var(--header-text);
}

.notif-item{
    padding:10px 15px;
    border-bottom:1px solid #f1f1f1;
}

.notif-title{
    font-size:13px;
    font-weight:600;
    color: var(--header-text);
}

.notif-desc{
    font-size:12px;
    color:#6b7280;
}

.notif-empty{
    padding:15px;
    text-align:center;
    color:#6b7280;
    font-size:13px;
}

/* USER */
.user-wrapper{
    position:relative;
}

.user-profile{
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
}

.avatar{
    width:50px;
    height:50px;
    border-radius:50%;
    background:#e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

.avatar-photo {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.user-info{
    display:flex;
    flex-direction:column;
}

.user-name{
    font-size:14px;
    font-weight:600;
    color: var(--header-text);
}

.user-email{
    font-size:12px;
    color:#9ca3af;
}

.dropdown-icon{
    font-size:12px;
    color: var(--header-text);
}

/* USER DROPDOWN */
.user-dropdown{
    position:absolute;
    right:0;
    top:55px;
    background: var(--dropdown-bg);
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
    display:none;
    width:160px;

    z-index:9999;
}

.user-dropdown a{
    display:block;
    padding:12px 15px;
    text-decoration:none;
    color: var(--dropdown-text);
    font-size:14px;
}

.user-dropdown a:hover{
    background:#f3f4f6;
}

</style>


<script>

    /* TOGGLE NOTIFICATIONS */
    function toggleNotif(){
        let menu = document.getElementById("notifDropdown");
        menu.style.display =
        menu.style.display === "block" ? "none" : "block";
    }

    /* TOGGLE USER MENU */
    function toggleUserMenu(){
        let menu = document.getElementById("userDropdown");
        menu.style.display =
        menu.style.display === "block" ? "none" : "block";
    }

    /* CLOSE DROPDOWNS IF CLICK OUTSIDE */
    window.onclick = function(e){
        if(!e.target.closest('.notif-wrapper')){
            document.getElementById("notifDropdown").style.display="none";
        }
        if(!e.target.closest('.user-wrapper')){
            document.getElementById("userDropdown").style.display="none";
        }
    }

</script>

