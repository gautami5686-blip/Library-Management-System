<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = require_user();
$activePage = 'fines';

if (is_post() && isset($_POST['pay_fine'])) {
    $result = pay_fine((int) ($_POST['fine_id'] ?? 0), (string) $user['Email_Address']);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('fines.php');
}

if (is_post() && isset($_POST['pay_all'])) {
    $result = pay_all_fines((string) $user['Email_Address']);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('fines.php');
}

$totalUnpaid = (float) db_value("SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE student_id = ? AND status = 'unpaid'", [(int) $user['id']], 'i');
$fines = db_all(
    'SELECT * FROM fines WHERE student_id = ? ORDER BY CASE WHEN status = "unpaid" THEN 0 ELSE 1 END, fine_date DESC, id DESC',
    [(int) $user['id']],
    'i'
);
$successMessage = flash('success');
$errorMessage = flash('error');
$displayName = $user['Name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Fines | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

    <div class="main">
        <div class="top-bar">
            <div class="welcome-msg">
                <h2>My Fines & Dues</h2>
                <p style="color: var(--text-muted);">Manage your library penalties and payments.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('logout.php')) ?>" style="color: #E63946; font-size: 14px; font-weight: 600; margin-right: 20px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <span style="color: var(--accent-gold); font-size: 15px; font-weight: 600; font-family: 'Playfair Display', serif;"><?= e($displayName) ?></span>
                <div class="user-avatar" style="background: linear-gradient(135deg, var(--accent-gold), #AA8715); width: 45px; height: 45px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: #fff; font-weight: bold; margin-left: 12px; font-family: 'Playfair Display', serif; font-size: 20px;">
                    <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                </div>
            </div>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(230,57,70,0.08)' : 'rgba(46,196,182,0.12)' ?>; border-left: 4px solid <?= $errorMessage ? '#E63946' : '#2EC4B6' ?>; color: <?= $errorMessage ? '#fca5a5' : '#d1fae5' ?>; padding: 18px 20px; border-radius: 8px; margin-bottom: 25px;">
                <i class="fas <?= $errorMessage ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div style="background: linear-gradient(135deg, #1a1a24, #2a2a35); color: #fff; padding: 30px; border-radius: 16px; display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom: 35px; flex-wrap: wrap; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
            <div>
                <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #e8d099; margin: 0 0 10px 0;">Total Outstanding Fines</h3>
                <p style="font-family: 'Playfair Display', serif; font-size: 48px; margin: 0; font-weight: 700;">₹<?= e(number_format($totalUnpaid, 2)) ?></p>
            </div>
            <div>
                <?php if ($totalUnpaid > 0): ?>
                    <form method="POST" action="<?= e(url('fines.php')) ?>">
                        <button class="btn-gold" type="submit" name="pay_all"><i class="fas fa-credit-card"></i> Pay All Now</button>
                    </form>
                <?php else: ?>
                    <div style="background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 8px; font-size: 15px;">
                        <i class="fas fa-check-circle" style="color: #2EC4B6; margin-right: 8px;"></i> All Clear!
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-header" style="border-bottom: none; padding-bottom: 0;">
            <h1 style="font-size: 24px;">Fine History</h1>
        </div>

        <div class="stats-grid" style="margin-top: 10px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php if ($fines): ?>
                <?php foreach ($fines as $fine): ?>
                    <?php $isUnpaid = $fine['status'] === 'unpaid'; ?>
                    <div class="stat-card" style="display:block; position: relative; overflow:hidden; border-top: 4px solid <?= $isUnpaid ? '#E63946' : '#2EC4B6' ?>;">
                        <div style="position:absolute; top: 20px; right: 20px; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: <?= $isUnpaid ? '#E63946' : 'rgba(46,196,182,0.1)' ?>; color: <?= $isUnpaid ? '#fff' : '#2EC4B6' ?>;">
                            <?= e(ucfirst((string) $fine['status'])) ?>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:15px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-size: 20px; background: <?= $isUnpaid ? 'rgba(230,57,70,0.1)' : 'rgba(46,196,182,0.1)' ?>; color: <?= $isUnpaid ? '#E63946' : '#2EC4B6' ?>;">
                                <i class="fas <?= $isUnpaid ? 'fa-file-invoice-dollar' : 'fa-receipt' ?>"></i>
                            </div>
                            <div style="font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; color: #fff;">₹<?= e(number_format((float) $fine['fine_amount'], 2)) ?></div>
                        </div>
                        <div style="font-size: 15px; color: #fff; font-weight: 600; margin-bottom: 8px; line-height: 1.4;"><?= e($fine['fine_reason']) ?></div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 25px; display:flex; align-items:center; gap:6px;">
                            <i class="far fa-calendar-alt"></i> Issued: <?= e(date('M d, Y', strtotime((string) $fine['fine_date']))) ?>
                        </div>
                        <?php if ($isUnpaid): ?>
                            <form method="POST" action="<?= e(url('fines.php')) ?>">
                                <input type="hidden" name="fine_id" value="<?= (int) $fine['id'] ?>">
                                <button class="btn-gold" type="submit" name="pay_fine" style="width:100%; justify-content:center;">
                                    Pay ₹<?= e(number_format((float) $fine['fine_amount'], 2)) ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn-gold" type="button" disabled style="width:100%; justify-content:center; opacity:0.7;"><i class="fas fa-check-circle"></i> Payment Completed</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.12);">
                    <div style="width: 80px; height: 80px; background: rgba(46, 196, 182, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: #2EC4B6; font-size: 32px;">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 style="font-family: 'Playfair Display', serif; color: #fff; margin-bottom: 10px;">No Fines Found</h3>
                    <p style="color: var(--text-muted);">Great job! You have a clean record with no penalties.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
