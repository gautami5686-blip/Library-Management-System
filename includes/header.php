<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = $user ?? current_user();
$admin = $admin ?? current_admin();
?>
<header class="header">
    <a href="<?= e(url('index.php')) ?>" class="logo">
        <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 56px; width: auto; display: block; border-radius: 14px; background: #fff; padding: 6px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
    </a>
    <nav>
        <a href="<?= e(url('index.php')) ?>" class="active">Home</a>
        <a href="<?= e(url('catalog.php')) ?>">Catalog</a>
        <a href="<?= e(url('about.php')) ?>">About</a>
        <a href="<?= e(url('contact.php')) ?>">Contact</a>
        <div class="nav-auth">
            <?php if ($user): ?>
                <a href="<?= e(url('user_dashboard.php')) ?>" class="btn-nav-login">Dashboard</a>
                <a href="<?= e(url('logout.php')) ?>" class="btn-nav-signup">Logout</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="btn-nav-login">Login</a>
                <a href="<?= e(url('signup.php')) ?>" class="btn-nav-signup">Sign Up</a>
            <?php endif; ?>

            <?php if ($admin): ?>
                <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
            <?php else: ?>
                <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
