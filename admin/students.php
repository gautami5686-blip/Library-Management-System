<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'students';

if (is_post() && isset($_POST['update_limit'])) {
    $result = update_student_limit((int) ($_POST['student_id'] ?? 0), (int) ($_POST['borrow_limit'] ?? 1));
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/students.php');
}

$students = db_all(
    "SELECT s.*, d.name AS Department,
            (SELECT COUNT(*) FROM issued_books ib WHERE ib.student_id = s.id AND ib.status IN ('active', 'overdue')) AS active_count,
            (SELECT COUNT(*) FROM fines f WHERE f.student_id = s.id AND f.status = 'unpaid') AS unpaid_fines
     FROM student_table s
     LEFT JOIN departments d ON d.id = s.department_id
     ORDER BY s.created_at DESC, s.id DESC"
);
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | LMS</title>
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
                <h2 style="color:#0f172a;">Student Management</h2>
                <p style="color:#94a3b8;">Review members, borrowing limits, and current load.</p>
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

        <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
            <div class="table-controls">
                <h3 style="margin:0; color:#0f172a; font-family:'Playfair Display', serif;">Registered Students</h3>
                <span style="color:#94a3b8; font-size:14px;"><?= e(count($students)) ?> students</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="color:#94a3b8;">Student</th>
                            <th style="color:#94a3b8;">Department</th>
                            <th style="color:#94a3b8;">Course</th>
                            <th style="color:#94a3b8;">Active Books</th>
                            <th style="color:#94a3b8;">Unpaid Fines</th>
                            <th style="color:#94a3b8;">Borrow Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td style="color:#0f172a; font-weight:600;"><?= e($student['Name']) ?><br><span style="color:#94a3b8; font-size:12px;"><?= e($student['Email_Address']) ?></span></td>
                                <td style="color:#334155;"><?= e($student['Department']) ?></td>
                                <td style="color:#334155;"><?= e($student['Course']) ?> / <?= e($student['Semester']) ?></td>
                                <td style="color:#334155;"><?= e((string) $student['active_count']) ?></td>
                                <td style="color:#334155;"><?= e((string) $student['unpaid_fines']) ?></td>
                                <td>
                                    <form method="POST" action="<?= e(url('admin/students.php')) ?>" style="display:flex; gap:10px; align-items:center;">
                                        <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                        <input type="number" min="1" max="10" name="borrow_limit" value="<?= e((string) $student['No_Books_issued']) ?>" style="width:80px; padding:10px; border:1px solid #dbe3ee; border-radius:8px;">
                                        <button type="submit" name="update_limit" class="btn-gold" style="padding:10px 16px; background: linear-gradient(135deg, #c5a059, #a38244); color:#fff;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>
