<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'settings';
$newMessages = (int) db_value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
$unpaidFines = (int) db_value("SELECT COUNT(*) FROM fines WHERE status = 'unpaid'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main">
        <?php include __DIR__ . '/../includes/admin_portal_header.php'; ?>
        <div class="top-bar" style="background:#fff; border-bottom:1px solid #e2e8f0;">
            <div class="welcome-msg">
                <h2 style="color:#0f172a;">System Settings</h2>
                <p style="color:#94a3b8;">Quick reference for the current admin account and pending workload.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('admin/logout.php')) ?>" style="color:#ef4444; text-decoration:none; font-weight:600;">Logout</a>
                <span style="color:#c5a059; font-weight:600;"><?= e($admin['name']) ?></span>
            </div>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <h3 style="margin-top:0; color:#0f172a; font-family:'Playfair Display', serif;">Admin Account</h3>
                <p style="color:#334155; line-height:1.8; margin:0;">
                    <strong>Name:</strong> <?= e($admin['name']) ?><br>
                    <strong>Email:</strong> <?= e($admin['email']) ?><br>
                    <strong>Default Login:</strong> admin@gmail.com / Admin@123
                </p>
            </div>
            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <h3 style="margin-top:0; color:#0f172a; font-family:'Playfair Display', serif;">Pending Work</h3>
                <p style="color:#334155; line-height:1.8; margin:0;">
                    <strong>New Messages:</strong> <?= e((string) $newMessages) ?><br>
                    <strong>Unpaid Fine Records:</strong> <?= e((string) $unpaidFines) ?><br>
                    <strong>Auto Setup:</strong> Enabled on request bootstrap
                </p>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>
