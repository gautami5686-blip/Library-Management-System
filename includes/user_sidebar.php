<?php
$activePage = $activePage ?? '';
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 70px; width: auto; display: block; border-radius: 16px; background: #fff; padding: 8px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
    </div>
    <div class="nav-links">
        <a href="<?= e(url('user_dashboard.php')) ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="<?= e(url('catalog.php')) ?>" class="<?= $activePage === 'catalog' ? 'active' : '' ?>"><i class="fas fa-search"></i> Browse Catalog</a>
        <a href="<?= e(url('my_books.php')) ?>" class="<?= $activePage === 'books' ? 'active' : '' ?>"><i class="fas fa-book"></i> My Books</a>
        <a href="<?= e(url('history.php')) ?>" class="<?= $activePage === 'history' ? 'active' : '' ?>"><i class="fas fa-history"></i> History</a>
        <a href="<?= e(url('fines.php')) ?>" class="<?= $activePage === 'fines' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Fines</a>
        <a href="<?= e(url('user_profile.php')) ?>" class="<?= $activePage === 'profile' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> My Profile</a>
    </div>
</div>
