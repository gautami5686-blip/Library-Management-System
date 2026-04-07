<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'dashboard';

if (is_post() && isset($_POST['return_issue'])) {
    $result = mark_issue_returned((int) ($_POST['issue_id'] ?? 0));
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/dashboard.php');
}

$totalStudents = (int) db_value('SELECT COUNT(*) FROM student_table');
$totalBooks = (int) db_value('SELECT COALESCE(SUM(Total_copies), 0) FROM books_table');
$activeIssues = (int) db_value("SELECT COUNT(*) FROM issued_books WHERE status = 'active'");
$overdueBooks = (int) db_value("SELECT COUNT(*) FROM issued_books WHERE status = 'overdue'");
$recentActivity = db_all('SELECT * FROM issued_books ORDER BY issue_date DESC, id DESC LIMIT 5');
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main">
        <?php include __DIR__ . '/../includes/admin_portal_header.php'; ?>
        <div class="top-bar" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="welcome-msg">
                <h2 style="color:#0f172a;">Admin Control Center</h2>
                <p style="color:#94a3b8;">Manage your library system efficiently.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('admin/logout.php')) ?>" style="color: #ef4444; font-size: 14px; font-weight: 600; margin-right: 20px; text-decoration: none;"><i class="fas fa-power-off"></i> Logout</a>
                <span style="color: #c5a059; font-size: 15px; font-weight: 600; font-family: 'Playfair Display', serif;"><?= e($admin['name']) ?></span>
                <div class="user-avatar" style="background: #0f172a; width: 45px; height: 45px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: #c5a059; font-size: 20px; margin-left: 12px; border: 2px solid #c5a059;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </div>

        <div class="page-header" style="border-bottom: none; padding-bottom: 0; display:flex; justify-content:space-between; align-items:center; gap:20px;">
            <h1 style="color:#0f172a;">Dashboard Overview</h1>
            <a href="<?= e(url('admin/issues.php')) ?>" class="btn-gold" style="background: linear-gradient(135deg, #c5a059, #a38244); color:#fff;">
                <i class="fas fa-plus"></i> Issue New Book
            </a>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.12)' ?>; border-left: 4px solid <?= $errorMessage ? '#ef4444' : '#10b981' ?>; color: <?= $errorMessage ? '#ef4444' : '#065f46' ?>; padding: 18px 20px; border-radius: 8px; margin-bottom: 25px;">
                <i class="fas <?= $errorMessage ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
                <div class="stat-icon" style="background: rgba(59,130,246,0.1); color:#3b82f6;"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3 style="color:#94a3b8;">Total Students</h3>
                    <p style="color:#0f172a;"><?= e(number_format($totalStudents)) ?></p>
                </div>
            </div>
            <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
                <div class="stat-icon" style="background: rgba(197,160,89,0.1); color:#c5a059;"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3 style="color:#94a3b8;">Total Books</h3>
                    <p style="color:#0f172a;"><?= e(number_format($totalBooks)) ?></p>
                </div>
            </div>
            <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
                <div class="stat-icon" style="background: rgba(16,185,129,0.1); color:#10b981;"><i class="fas fa-hand-holding-box"></i></div>
                <div class="stat-info">
                    <h3 style="color:#94a3b8;">Active Issues</h3>
                    <p style="color:#0f172a;"><?= e(number_format($activeIssues)) ?></p>
                </div>
            </div>
            <div class="stat-card" style="background:#fff; border:1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
                <div class="stat-icon" style="background: rgba(239,68,68,0.1); color:#ef4444;"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3 style="color:#94a3b8;">Overdue Books</h3>
                    <p style="color:#0f172a;"><?= e(number_format($overdueBooks)) ?></p>
                </div>
            </div>
        </div>

        <div class="table-container" style="background:#fff; border:1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
            <div class="table-controls">
                <h3 style="margin: 0; font-family: 'Playfair Display', serif; color: #0f172a;">Recent Book Issues</h3>
                <a href="<?= e(url('admin/issues.php')) ?>" style="color:#c5a059; text-decoration:none; font-size:14px; font-weight:600;">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div style="overflow-x:auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="color:#94a3b8;">Book Title</th>
                            <th style="color:#94a3b8;">Student Email</th>
                            <th style="color:#94a3b8;">Issue Date</th>
                            <th style="color:#94a3b8;">Due Date</th>
                            <th style="color:#94a3b8;">Status</th>
                            <th style="color:#94a3b8;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentActivity): ?>
                            <?php foreach ($recentActivity as $row): ?>
                                <?php
                                $status = strtolower((string) $row['status']);
                                $statusLabel = ucfirst($status);
                                $statusStyle = 'background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2);';
                                if ($status === 'overdue') {
                                    $statusStyle = 'background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2);';
                                } elseif ($status === 'returned') {
                                    $statusStyle = 'background: rgba(148,163,184,0.1); color: #64748b; border: 1px solid rgba(148,163,184,0.2);';
                                }
                                ?>
                                <tr>
                                    <td style="font-weight:600; color:#0f172a;"><i class="fas fa-book" style="color:#94a3b8; margin-right:8px;"></i><?= e($row['book_title']) ?></td>
                                    <td style="color:#334155;"><?= e($row['user_email']) ?></td>
                                    <td style="color:#334155;"><?= e(date('M d, Y', strtotime((string) $row['issue_date']))) ?></td>
                                    <td style="color:#334155;"><?= e(date('M d, Y', strtotime((string) $row['due_date']))) ?></td>
                                    <td><span class="status-badge" style="<?= e($statusStyle) ?>"><?= e($statusLabel) ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <a class="btn-icon" href="<?= e(url('admin/fines.php')) ?>" title="View fines/messages" style="color:#64748b; text-decoration:none; border-color:#e2e8f0;"><i class="fas fa-envelope"></i></a>
                                            <?php if ($status !== 'returned'): ?>
                                                <form method="POST" action="<?= e(url('admin/dashboard.php')) ?>">
                                                    <input type="hidden" name="issue_id" value="<?= (int) $row['id'] ?>">
                                                    <button class="btn-icon edit" type="submit" name="return_issue" title="Mark as Returned" style="color:#c5a059; border-color:#e2e8f0;"><i class="fas fa-check-circle"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">No recent activity found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>
