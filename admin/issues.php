<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'issues';

if (is_post() && isset($_POST['issue_book'])) {
    $student = db_one('SELECT * FROM student_table WHERE Email_Address = ?', [trim((string) ($_POST['student_email'] ?? ''))]);
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if (!$student) {
        $result = ['success' => false, 'message' => 'Student email was not found.'];
    } else {
        $book = db_one('SELECT * FROM books_table WHERE id = ?', [$bookId], 'i');
        $activeCount = get_student_active_issue_count((int) $student['id']);
        $limit = max(1, (int) $student['No_Books_issued']);

        if (!$book) {
            $result = ['success' => false, 'message' => 'Selected book was not found.'];
        } elseif ((int) $book['Available_copies'] < 1) {
            $result = ['success' => false, 'message' => 'No copy is currently available for direct issue.'];
        } elseif ($activeCount >= $limit) {
            $result = ['success' => false, 'message' => 'This student has already reached the borrowing limit.'];
        } else {
            $result = create_issue($bookId, $student);
        }
    }

    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/issues.php');
}

if (is_post() && isset($_POST['return_issue'])) {
    $result = mark_issue_returned((int) ($_POST['issue_id'] ?? 0));
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/issues.php');
}

if (is_post() && isset($_POST['fulfill_waitlist'])) {
    $request = db_one('SELECT * FROM book_requests WHERE id = ? AND status = ?', [(int) ($_POST['request_id'] ?? 0), 'waitlisted'], 'is');

    if (!$request) {
        $result = ['success' => false, 'message' => 'Waitlist request could not be found.'];
    } else {
        $student = db_one('SELECT * FROM student_table WHERE id = ?', [(int) $request['student_id']], 'i');
        $result = $student ? create_issue((int) $request['book_id'], $student, (int) $request['id']) : ['success' => false, 'message' => 'Student for this request no longer exists.'];
    }

    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/issues.php');
}

$books = db_all('SELECT id, Title, Available_copies FROM books_table ORDER BY Title ASC');
$activeIssues = db_all("SELECT * FROM issued_books WHERE status IN ('active', 'overdue') ORDER BY due_date ASC, id DESC");
$waitlist = db_all(
    "SELECT br.*, b.Title AS book_title, s.Name AS student_name, s.Email_Address AS student_email
     FROM book_requests br
     INNER JOIN books_table b ON b.id = br.book_id
     INNER JOIN student_table s ON s.id = br.student_id
     WHERE br.status = 'waitlisted'
     ORDER BY br.created_at ASC"
);
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue / Return | LMS</title>
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
                <h2 style="color:#0f172a;">Issue & Returns</h2>
                <p style="color:#94a3b8;">Manage live circulation and waitlist requests.</p>
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
                <h3 style="margin-top:0; color:#0f172a; font-family:'Playfair Display', serif;">Issue New Book</h3>
                <form method="POST" action="<?= e(url('admin/issues.php')) ?>" style="display:grid; gap:14px;">
                    <input type="email" name="student_email" placeholder="Student email" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                    <select name="book_id" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                        <option value="">Select book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= (int) $book['id'] ?>"><?= e($book['Title']) ?> (<?= e((string) $book['Available_copies']) ?> available)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="issue_book" class="btn-gold" style="background: linear-gradient(135deg, #c5a059, #a38244); color:#fff; justify-content:center;">Issue Book</button>
                </form>
            </div>

            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <div class="table-controls">
                    <h3 style="margin:0; color:#0f172a; font-family:'Playfair Display', serif;">Active / Overdue Issues</h3>
                    <span style="color:#94a3b8; font-size:14px;"><?= e(count($activeIssues)) ?> records</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="color:#94a3b8;">Book</th>
                                <th style="color:#94a3b8;">Student</th>
                                <th style="color:#94a3b8;">Due Date</th>
                                <th style="color:#94a3b8;">Status</th>
                                <th style="color:#94a3b8;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeIssues as $issue): ?>
                                <?php $isOverdue = $issue['status'] === 'overdue'; ?>
                                <tr>
                                    <td style="color:#0f172a; font-weight:600;"><?= e($issue['book_title']) ?></td>
                                    <td style="color:#334155;"><?= e($issue['user_email']) ?></td>
                                    <td style="color:#334155;"><?= e(date('M d, Y', strtotime((string) $issue['due_date']))) ?></td>
                                    <td><span class="status-badge" style="background: <?= $isOverdue ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)' ?>; color: <?= $isOverdue ? '#ef4444' : '#10b981' ?>; border:1px solid rgba(0,0,0,0.08);"><?= e(ucfirst((string) $issue['status'])) ?></span></td>
                                    <td>
                                        <form method="POST" action="<?= e(url('admin/issues.php')) ?>">
                                            <input type="hidden" name="issue_id" value="<?= (int) $issue['id'] ?>">
                                            <button type="submit" name="return_issue" class="btn-gold" style="padding:10px 16px; background: linear-gradient(135deg, #c5a059, #a38244); color:#fff;">Return</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$activeIssues): ?>
                                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No active issues right now.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

                <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>

