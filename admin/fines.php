<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'fines';

if (is_post() && isset($_POST['pay_fine'])) {
    $result = pay_fine((int) ($_POST['fine_id'] ?? 0));
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/fines.php');
}

if (is_post() && isset($_POST['mark_message_read'])) {
    mark_message_read((int) ($_POST['message_id'] ?? 0));
    flash('success', 'Message marked as read.');
    redirect('admin/fines.php');
}

$fines = db_all('SELECT * FROM fines ORDER BY CASE WHEN status = "unpaid" THEN 0 ELSE 1 END, fine_date DESC, id DESC');
$messages = db_all('SELECT * FROM contact_messages ORDER BY CASE WHEN status = "new" THEN 0 ELSE 1 END, created_at DESC');
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines & Reports | LMS</title>
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
                <h2 style="color:#0f172a;">Fines & Contact Inbox</h2>
                <p style="color:#94a3b8;">Track payments and review user messages.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('admin/logout.php')) ?>" style="color:#ef4444; text-decoration:none; font-weight:600;">Logout</a>
                <span style="color:#c5a059; font-weight:600;"><?= e($admin['name']) ?></span>
            </div>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.12)' ?>; border-left: 4px solid <?= $errorMessage ? '#ef4444' : '#10b981' ?>; color: <?= $errorMessage ? '#ef4444' : '#065f46' ?>; padding: 18px 20px; border-radius: 8px; margin-bottom: 25px;">
                <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items:start;">
            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <div class="table-controls">
                    <h3 style="margin:0; color:#0f172a; font-family:'Playfair Display', serif;">Fine Records</h3>
                    <span style="color:#94a3b8; font-size:14px;"><?= e(count($fines)) ?> fines</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="color:#94a3b8;">User</th>
                                <th style="color:#94a3b8;">Reason</th>
                                <th style="color:#94a3b8;">Amount</th>
                                <th style="color:#94a3b8;">Status</th>
                                <th style="color:#94a3b8;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines as $fine): ?>
                                <?php $isUnpaid = $fine['status'] === 'unpaid'; ?>
                                <tr>
                                    <td style="color:#0f172a; font-weight:600;"><?= e($fine['user_email']) ?></td>
                                    <td style="color:#334155;"><?= e($fine['fine_reason']) ?></td>
                                    <td style="color:#334155;">₹<?= e(number_format((float) $fine['fine_amount'], 2)) ?></td>
                                    <td><span class="status-badge" style="background: <?= $isUnpaid ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)' ?>; color: <?= $isUnpaid ? '#ef4444' : '#10b981' ?>; border:1px solid rgba(0,0,0,0.08);"><?= e(ucfirst((string) $fine['status'])) ?></span></td>
                                    <td>
                                        <?php if ($isUnpaid): ?>
                                            <form method="POST" action="<?= e(url('admin/fines.php')) ?>">
                                                <input type="hidden" name="fine_id" value="<?= (int) $fine['id'] ?>">
                                                <button type="submit" name="pay_fine" class="btn-gold" style="padding:10px 16px; background: linear-gradient(135deg, #c5a059, #a38244); color:#fff;">Mark Paid</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:13px;">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$fines): ?>
                                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No fine records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <div class="table-controls">
                    <h3 style="margin:0; color:#0f172a; font-family:'Playfair Display', serif;">Contact Messages</h3>
                    <span style="color:#94a3b8; font-size:14px;"><?= e(count($messages)) ?> messages</span>
                </div>
                <div style="display:grid; gap:14px;">
                    <?php foreach ($messages as $message): ?>
                        <div style="border:1px solid #e2e8f0; border-radius:14px; padding:16px; background: <?= $message['status'] === 'new' ? 'rgba(197,160,89,0.06)' : '#fff' ?>;">
                            <div style="display:flex; justify-content:space-between; gap:12px; align-items:start; margin-bottom:10px;">
                                <div>
                                    <strong style="color:#0f172a;"><?= e($message['name']) ?></strong><br>
                                    <span style="color:#64748b; font-size:13px;"><?= e($message['email']) ?></span>
                                </div>
                                <span class="status-badge" style="background: <?= $message['status'] === 'new' ? 'rgba(197,160,89,0.1)' : 'rgba(148,163,184,0.1)' ?>; color: <?= $message['status'] === 'new' ? '#a38244' : '#64748b' ?>; border:1px solid rgba(0,0,0,0.08);">
                                    <?= e(strtoupper((string) $message['status'])) ?>
                                </span>
                            </div>
                            <p style="color:#334155; margin:0 0 12px 0; line-height:1.6;"><?= nl2br(e($message['message'])) ?></p>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                                <span style="color:#94a3b8; font-size:12px;"><?= e(date('M d, Y h:i A', strtotime((string) $message['created_at']))) ?></span>
                                <?php if ($message['status'] === 'new'): ?>
                                    <form method="POST" action="<?= e(url('admin/fines.php')) ?>">
                                        <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                        <button type="submit" name="mark_message_read" class="btn-gold" style="padding:10px 16px; background: linear-gradient(135deg, #c5a059, #a38244); color:#fff;">Mark Read</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$messages): ?>
                        <div style="text-align:center; padding:30px; color:#94a3b8; border:1px dashed #dbe3ee; border-radius:14px;">No contact messages yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>

