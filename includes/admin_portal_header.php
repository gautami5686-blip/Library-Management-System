<?php
declare(strict_types=1);

$portalNavLinks = [
    ['label' => 'Home', 'href' => url('index.php')],
    ['label' => 'Catalog', 'href' => url('catalog.php')],
    ['label' => 'About', 'href' => url('about.php')],
    ['label' => 'Contact', 'href' => url('contact.php')],
];
?>
<div class="portal-header">
    <a href="<?= e(url('index.php')) ?>" class="portal-logo">
        <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 56px; width: auto; display: block; border-radius: 14px; background: #fff; padding: 6px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
    </a>
    <nav class="portal-nav">
        <?php foreach ($portalNavLinks as $link): ?>
            <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
        <?php endforeach; ?>
        <div class="portal-nav-auth">
            <a href="<?= e(url('admin/dashboard.php')) ?>" class="portal-btn portal-btn-outline">Admin Panel</a>
            <a href="<?= e(url('admin/logout.php')) ?>" class="portal-btn portal-btn-solid">Logout</a>
        </div>
    </nav>
</div>
