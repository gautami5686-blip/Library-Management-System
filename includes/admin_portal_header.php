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
    <span class="portal-logo">Admin Portal</span>
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
