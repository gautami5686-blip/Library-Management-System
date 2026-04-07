<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = require_user();
$activePage = 'books';

if (is_post() && isset($_POST['renew_issue'])) {
    $result = renew_issue((int) ($_POST['issue_id'] ?? 0), (int) $user['id']);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('my_books.php');
}

$books = db_all(
    "SELECT * FROM issued_books WHERE student_id = ? AND status IN ('active', 'overdue') ORDER BY issue_date DESC",
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
    <title>My Books | LMS</title>
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
                <h2>My Books</h2>
                <p style="color: var(--text-muted);">View and manage your currently borrowed books.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('logout.php')) ?>" style="color: #E63946; font-size: 14px; font-weight: 600; margin-right: 20px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <span style="color: var(--accent-gold); font-size: 15px; font-weight: 600; font-family: 'Playfair Display', serif;"><?= e($displayName) ?></span>
                <div class="user-avatar" style="background: linear-gradient(135deg, var(--accent-gold), #AA8715); width: 45px; height: 45px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: #fff; font-weight: bold; margin-left: 12px; font-family: 'Playfair Display', serif; font-size: 20px; box-shadow: 0 4px 10px rgba(197, 160, 89, 0.3);">
                    <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                </div>
            </div>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(230,57,70,0.08)' : 'rgba(46,196,182,0.12)' ?>; border-left: 4px solid <?= $errorMessage ? '#E63946' : '#2EC4B6' ?>; color: <?= $errorMessage ? '#fca5a5' : '#d1fae5' ?>; padding: 18px 20px; border-radius: 8px; margin-bottom: 25px;">
                <i class="fas <?= $errorMessage ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="page-header" style="border-bottom: none; padding-bottom: 0;">
            <h1>My Borrowed Library</h1>
            <div class="search-box" style="max-width: 350px; width: 100%;">
                <i class="fas fa-search"></i>
                <input type="text" id="bookSearch" placeholder="Search by title or author...">
            </div>
        </div>

        <div class="stats-grid" id="bookGrid" style="margin-top: 10px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
            <?php if ($books): ?>
                <?php foreach ($books as $book): ?>
                    <?php
                    $status = strtolower((string) $book['status']);
                    $isOverdue = $status === 'overdue';
                    $iconClass = $isOverdue ? 'fa-exclamation' : 'fa-check';
                    $statusClass = $isOverdue ? 'background:#E63946;' : 'background:#2EC4B6;';
                    ?>
                    <div class="stat-card book-card" style="display:block; text-align:center; position: relative; overflow:hidden;">
                        <div style="width: 120px; height: 160px; background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)); margin: 0 auto 20px auto; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--accent-gold); border: 1px solid rgba(255,255,255,0.08); box-shadow: 5px 5px 15px rgba(0,0,0,0.05); position: relative;">
                            <i class="fas fa-book"></i>
                            <div style="position: absolute; top: -10px; right: -10px; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: white; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); <?= e($statusClass) ?>">
                                <i class="fas <?= e($iconClass) ?>"></i>
                            </div>
                        </div>
                        <h3 class="book-title" style="font-family: 'Playfair Display', serif; font-size: 18px; color: #fff; margin-bottom: 8px;"><?= e($book['book_title']) ?></h3>
                        <p class="book-author" style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">By <?= e($book['author']) ?></p>
                        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 12px; margin-bottom: 20px; display:flex; justify-content: space-between; font-size: 12px;">
                            <div style="text-align:left;">
                                <span style="display:block; text-transform: uppercase; font-size: 10px; font-weight: 600; color: var(--text-muted); letter-spacing: 0.5px;">Issued On</span>
                                <span style="color:#fff; font-weight:600;"><?= e(date('M d, Y', strtotime((string) $book['issue_date']))) ?></span>
                            </div>
                            <div style="text-align:right;">
                                <span style="display:block; text-transform: uppercase; font-size: 10px; font-weight: 600; color: var(--text-muted); letter-spacing: 0.5px;">Due By</span>
                                <span style="color: <?= $isOverdue ? '#f87171' : '#fff' ?>; font-weight:600;"><?= e(date('M d, Y', strtotime((string) $book['due_date']))) ?></span>
                            </div>
                        </div>
                        <?php if ($isOverdue): ?>
                            <a href="<?= e(url('fines.php')) ?>" class="btn-gold" style="width: 100%; justify-content: center; text-decoration:none;">Pay Fine</a>
                        <?php else: ?>
                            <form method="POST" action="<?= e(url('my_books.php')) ?>">
                                <input type="hidden" name="issue_id" value="<?= (int) $book['id'] ?>">
                                <button class="btn-gold" type="submit" name="renew_issue" style="width: 100%; justify-content: center;"><i class="fas fa-redo"></i> Renew Book</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.12);">
                    <div style="width: 80px; height: 80px; background: rgba(197, 160, 89, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: var(--accent-gold); font-size: 32px;">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3 style="font-family: 'Playfair Display', serif; color: #fff; margin-bottom: 10px;">No Borrowed Books</h3>
                    <p style="color: var(--text-muted);">You haven't borrowed any books from the library yet.</p>
                    <a href="<?= e(url('catalog.php')) ?>" class="btn-gold" style="display: inline-flex; width: auto; padding: 12px 30px; margin-top: 15px; text-decoration: none;">
                        <i class="fas fa-search"></i> Browse Catalog
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('bookSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const filter = this.value.toLowerCase();
                document.querySelectorAll('.book-card').forEach((card) => {
                    const title = card.querySelector('.book-title')?.innerText.toLowerCase() || '';
                    const author = card.querySelector('.book-author')?.innerText.toLowerCase() || '';
                    card.style.display = title.includes(filter) || author.includes(filter) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
