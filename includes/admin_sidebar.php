<?php
$activePage = $activePage ?? '';
?>
<div class="sidebar" style="background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05);">
    <div class="sidebar-logo" style="color: #c5a059;">
        <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 70px; width: auto; display: block; border-radius: 16px; background: #fff; padding: 8px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
    </div>
    <div class="nav-links">
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="<?= e(url('admin/students.php')) ?>" class="<?= $activePage === 'students' ? 'active' : '' ?>"><i class="fas fa-users"></i> Manage Students</a>
        <a href="<?= e(url('admin/books.php')) ?>" class="<?= $activePage === 'books' ? 'active' : '' ?>"><i class="fas fa-book"></i> Manage Books</a>
        <a href="<?= e(url('admin/issues.php')) ?>" class="<?= $activePage === 'issues' ? 'active' : '' ?>"><i class="fas fa-exchange-alt"></i> Issue / Return</a>
        <a href="<?= e(url('admin/fines.php')) ?>" class="<?= $activePage === 'fines' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> Fines & Reports</a>
        <a href="<?= e(url('admin/settings.php')) ?>" class="<?= $activePage === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a>
    </div>
</div>
