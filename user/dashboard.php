<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = require_user();
$activePage = 'dashboard';
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$dashboardPath = 'user_dashboard.php' . ($statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : '');

if (is_post() && isset($_POST['renew_issue'])) {
    $result = renew_issue((int) ($_POST['issue_id'] ?? 0), (int) $user['id']);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect($dashboardPath);
}

if (is_post() && isset($_POST['update_profile'])) {
    $result = update_student_profile((int) $user['id'], $_POST, $_FILES);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect($dashboardPath . '#profile-section');
}

$activeCount = (int) db_value("SELECT COUNT(*) FROM issued_books WHERE student_id = ? AND status = 'active'", [(int) $user['id']], 'i');
$returnedCount = (int) db_value("SELECT COUNT(*) FROM issued_books WHERE student_id = ? AND status = 'returned'", [(int) $user['id']], 'i');
$overdueCount = (int) db_value("SELECT COUNT(*) FROM issued_books WHERE student_id = ? AND status = 'overdue'", [(int) $user['id']], 'i');
$totalFines = (float) db_value("SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE student_id = ? AND status = 'unpaid'", [(int) $user['id']], 'i');

$where = 'student_id = ?';
$params = [(int) $user['id']];
$types = 'i';

if (in_array($statusFilter, ['active', 'returned', 'overdue'], true)) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$books = db_all(
    'SELECT * FROM issued_books WHERE ' . $where . ' ORDER BY issue_date DESC',
    $params,
    $types
);
$successMessage = flash('success');
$errorMessage = flash('error');
$departments = departments_all();
$displayName = $user['Name'];
$profileImageUrl = student_profile_image_url($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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
                <h2>Welcome Back!</h2>
                <p>View your library activity and manage your books.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('logout.php')) ?>" style="color: #E63946; font-size: 14px; font-weight: 500; margin-right: 15px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <span style="color: var(--accent-gold); font-size: 14px; font-weight: 500;"><?= e($displayName) ?></span>
                <div class="user-avatar" style="background: rgba(212,175,55,0.2); width: 40px; height: 40px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: var(--accent-gold); font-weight: bold; margin-left: 10px; overflow:hidden;">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                    <?php else: ?>
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="page-header">
            <h1>My Dashboard</h1>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="<?= e(url('user_profile.php')) ?>" class="profile-link-btn">
                    <i class="fas fa-id-card"></i> Full Profile Page
                </a>
                <a href="#profile-section" class="btn-gold">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <a href="<?= e(url('catalog.php')) ?>" class="btn-gold">
                    <i class="fas fa-book-open"></i> Explore Books
                </a>
            </div>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(230,57,70,0.1)' : 'rgba(46,196,182,0.12)' ?>; border: 1px solid <?= $errorMessage ? '#E63946' : 'rgba(46,196,182,0.3)' ?>; color: <?= $errorMessage ? '#fca5a5' : '#d1fae5' ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas <?= $errorMessage ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #4361ee; background: rgba(67, 97, 238, 0.1);"><i class="fas fa-book-reader"></i></div>
                <div class="stat-info">
                    <h3>Currently Borrowed</h3>
                    <p><?= e($activeCount) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #2EC4B6; background: rgba(46, 196, 182, 0.1);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3>Total Returned</h3>
                    <p><?= e($returnedCount) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #E63946; background: rgba(230, 57, 70, 0.1);"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-info">
                    <h3>Overdue Books</h3>
                    <p><?= e($overdueCount) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"><i class="fas fa-rupee-sign"></i></div>
                <div class="stat-info">
                    <h3>Pending Fines</h3>
                    <p>₹<?= e(number_format($totalFines, 2)) ?></p>
                </div>
            </div>
        </div>

        <section id="profile-section" class="profile-section-grid">
            <div class="stat-card profile-summary-card">
                <div class="profile-summary-avatar">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                    <?php else: ?>
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <h3 class="profile-summary-title"><?= e($user['Name']) ?></h3>
                <p class="profile-summary-subtitle">
                    <i class="fas fa-envelope" style="color: var(--accent-gold); margin-right: 6px;"></i><?= e($user['Email_Address']) ?>
                </p>
                <div style="margin-bottom: 24px;">
                    <span class="status-badge" style="display:inline-block; padding: 8px 18px; font-size: 13px; font-weight: 600; border-radius: 20px; background: rgba(197,160,89,0.1); color: var(--accent-gold); border: 1px solid rgba(197,160,89,0.2);">Profile Active</span>
                </div>
                <div class="profile-summary-meta">
                    <div class="profile-summary-meta-item">
                        <span>Department</span>
                        <strong><?= e((string) ($user['Department'] ?? 'Not set')) ?></strong>
                    </div>
                    <div class="profile-summary-meta-item">
                        <span>Course</span>
                        <strong><?= e($user['Course']) ?></strong>
                    </div>
                    <div class="profile-summary-meta-item">
                        <span>Semester</span>
                        <strong><?= e($user['Semester']) ?></strong>
                    </div>
                    <div class="profile-summary-meta-item">
                        <span>Borrow Limit</span>
                        <strong><?= e((string) $user['No_Books_issued']) ?> Books</strong>
                    </div>
                </div>
            </div>

            <div class="table-container profile-form-card">
                <h3 class="profile-form-title"><i class="fas fa-user-cog" style="color: var(--accent-gold); margin-right: 10px;"></i>Edit Profile</h3>
                <p class="profile-form-text">Update your personal and academic details directly from the dashboard.</p>

                <form method="POST" action="<?= e(url($dashboardPath . '#profile-section')) ?>" enctype="multipart/form-data">
                    <div class="profile-photo-editor">
                        <div class="profile-photo-frame">
                            <?php if ($profileImageUrl): ?>
                                <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                            <?php else: ?>
                                <div class="profile-photo-placeholder"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-upload-controls">
                            <div class="profile-field">
                                <label for="dashboard-profile-image">Profile Image</label>
                                <input id="dashboard-profile-image" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>
                            <p class="profile-upload-hint">Upload JPG, PNG, or WEBP image. Maximum file size: 2 MB.</p>
                            <?php if ($profileImageUrl): ?>
                                <label class="profile-checkbox">
                                    <input type="checkbox" name="remove_profile_image" value="1">
                                    Remove current photo
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-form-grid">
                        <div class="profile-field">
                            <label for="dashboard-name">Full Name</label>
                            <input id="dashboard-name" type="text" name="name" value="<?= e($user['Name']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label for="dashboard-email">Email Address</label>
                            <input id="dashboard-email" type="email" value="<?= e($user['Email_Address']) ?>" disabled>
                        </div>
                        <div class="profile-field">
                            <label for="dashboard-department">Department</label>
                            <select id="dashboard-department" name="department_id" required>
                                <option value="" disabled>Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e((string) $department['id']) ?>" <?= selected((string) $department['id'], (string) ($user['department_id'] ?? '')) ?>>
                                        <?= e($department['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="profile-field">
                            <label for="dashboard-course">Course</label>
                            <input id="dashboard-course" type="text" name="course" value="<?= e($user['Course']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label for="dashboard-semester">Semester</label>
                            <input id="dashboard-semester" type="text" name="semester" value="<?= e($user['Semester']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label for="dashboard-limit">Maximum Allowed Books</label>
                            <input id="dashboard-limit" type="text" value="<?= e((string) $user['No_Books_issued']) ?> Books" disabled>
                        </div>
                    </div>

                    <div class="profile-form-actions">
                        <a href="<?= e(url('user_profile.php')) ?>" class="profile-link-btn">Open Full Profile</a>
                        <button type="submit" name="update_profile" class="btn-gold"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="table-container">
            <div class="table-controls">
                <h3 style="margin: 0; font-family: 'Playfair Display', serif; color: #fff;">My Borrowed Books</h3>
                <form method="GET" action="<?= e(url('user_dashboard.php')) ?>">
                    <select class="filter-select" name="status" onchange="this.form.submit()">
                        <option value="all" <?= selected('all', $statusFilter) ?>>All Books</option>
                        <option value="active" <?= selected('active', $statusFilter) ?>>Currently Reading</option>
                        <option value="overdue" <?= selected('overdue', $statusFilter) ?>>Overdue</option>
                        <option value="returned" <?= selected('returned', $statusFilter) ?>>Returned</option>
                    </select>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books): ?>
                            <?php foreach ($books as $row): ?>
                                <?php
                                $status = strtolower((string) $row['status']);
                                $statusHtml = '<span class="status-badge status-active">Active</span>';
                                $actionHtml = '<form method="POST" action="' . e(url('user_dashboard.php' . ($statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''))) . '"><input type="hidden" name="issue_id" value="' . (int) $row['id'] . '"><button class="btn-icon edit" type="submit" name="renew_issue" title="Renew Book"><i class="fas fa-redo"></i></button></form>';
                                $iconColor = '#4361ee';
                                $bgColor = 'rgba(67, 97, 238, 0.1)';

                                if ($status === 'overdue') {
                                    $statusHtml = '<span class="status-badge" style="background: rgba(230,57,70,0.1); color: #E63946; border: 1px solid rgba(230,57,70,0.2);">Overdue</span>';
                                    $actionHtml = '<a class="btn-icon" href="' . e(url('fines.php')) . '" style="color: #E63946; text-decoration:none;" title="Pay Fine"><i class="fas fa-wallet"></i></a>';
                                    $iconColor = '#E63946';
                                    $bgColor = 'rgba(230,57,70,0.1)';
                                } elseif ($status === 'returned') {
                                    $statusHtml = '<span class="status-badge status-suspended" style="background: rgba(46,196,182,0.1); color: #2EC4B6; border: 1px solid rgba(46,196,182,0.2);">Returned</span>';
                                    $actionHtml = '<button class="btn-icon" type="button" disabled style="color: var(--text-muted); cursor: not-allowed;"><i class="fas fa-check"></i></button>';
                                    $iconColor = '#2EC4B6';
                                    $bgColor = 'rgba(46,196,182,0.1)';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar" style="background: <?= e($bgColor) ?>; border-radius: 8px; color: <?= e($iconColor) ?>; display:flex; align-items:center; justify-content:center;">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="user-details">
                                                <h4><?= e($row['book_title']) ?></h4>
                                                <p><?= e($row['author']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($row['author']) ?></td>
                                    <td><?= e(date('M d, Y', strtotime((string) $row['issue_date']))) ?></td>
                                    <td><?= e(date('M d, Y', strtotime((string) $row['due_date']))) ?></td>
                                    <td><?= $statusHtml ?></td>
                                    <td><div class="action-btns"><?= $actionHtml ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No books borrowed yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
