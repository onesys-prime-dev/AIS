<aside class="sidebar">

<div class="sidebar-inner">

<!-- LOGO -->
<div class="logo-box">
<div class="logo"></div>
</div>


<!-- MAIN MENU -->
<nav class="menu">

<a href="<?= site_url('client_portal/dashboard') ?>"
class="menu-item <?= current_url() === site_url('client_portal/dashboard') ? 'active' : '' ?>">

<img src="<?= base_url('icons/dashboard.png') ?>" class="icon">
<span>Dashboard</span>

</a>


<a href="<?= site_url('client_portal/employees') ?>" class="menu-item">

<img src="<?= base_url('icons/employee.png') ?>" class="icon">
<span>Employee</span>

</a>


<?php if(auth()->user()->inGroup('client_payroll_staff')): ?>

<a href="<?= site_url('client_portal/payroll') ?>" class="menu-item">

<img src="<?= base_url('icons/payroll.png') ?>" class="icon">
<span>Payroll</span>

</a>

<?php endif; ?>


<a href="<?= site_url('client_portal/requests') ?>" class="menu-item">

<img src="<?= base_url('icons/Requests.png') ?>" class="icon">
<span>Requests</span>

</a>

</nav>



<!-- BOTTOM MENU -->
<div class="bottom-menu">

<a href="<?= site_url('help') ?>" class="menu-item">

<img src="<?= base_url('icons/help.png') ?>" class="icon">
<span>Help</span>

</a>


<a href="<?= site_url('settings') ?>" class="menu-item">

<img src="<?= base_url('icons/settings.png') ?>" class="icon">
<span>Settings</span>

</a>


<a href="<?= site_url('logout') ?>" class="menu-item">

<img src="<?= base_url('icons/logout.png') ?>" class="icon">
<span>Log-out</span>

</a>

</div>

</div>

</aside>



<style>

/* SIDEBAR */

.sidebar{
width:95px;
background:#1e1e1e;
min-height:100vh;
display:flex;
flex-direction:column;
align-items:center;
border-right:1px solid #2c2c2c;
font-family:Segoe UI, Arial;
flex-shrink:0;
}



/* INNER */

.sidebar-inner{
display:flex;
flex-direction:column;
height:100%;
width:100%;
align-items:center;
}



/* LOGO */

.logo-box{
padding:25px 0;
border-bottom:1px solid #2c2c2c;
width:100%;
display:flex;
justify-content:center;
}

.logo{
width:50px;
height:50px;
background:#d9d9d9;
border-radius:4px;
}



/* MAIN MENU */

.menu{
display:flex;
flex-direction:column;
gap:22px;
padding-top:30px;
align-items:center;
width:100%;
}



/* MENU ITEM */

.menu-item{
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
gap:6px;
width:72px;
height:72px;
border-radius:16px;
text-decoration:none;
color:#cfcfcf;
font-size:11px;
transition:0.2s;
}



/* ACTIVE ITEM */

.menu-item.active{
background:#555;
color:white;
}



/* HOVER */

.menu-item:hover{
background:#333;
}



/* ICON */

.icon{
width:28px;
height:28px;
}



/* BOTTOM MENU */

.bottom-menu{
margin-top:auto;
padding-bottom:25px;
display:flex;
flex-direction:column;
gap:22px;
align-items:center;
width:100%;
}

</style>