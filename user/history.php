<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = require_user();
$activePage = 'history';
$books = db_all(
    "SELECT * FROM issued_books WHERE student_id = ? AND status = 'returned' ORDER BY issue_date DESC",
    [(int) $user['id']],
    'i'
);
$displayName = $user['Name'];
$profileImageUrl = student_profile_image_url($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History | LMS</title>
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
                <h2>Borrowing History</h2>
                <p style="color: var(--text-muted);">Review your past reads and completed borrows.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('logout.php')) ?>" style="color: #E63946; font-size: 14px; font-weight: 600; margin-right: 20px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <span style="color: var(--accent-gold); font-size: 15px; font-weight: 600; font-family: 'Playfair Display', serif;"><?= e($displayName) ?></span>
                <div class="user-avatar" style="background: linear-gradient(135deg, var(--accent-gold), #AA8715); width: 45px; height: 45px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: #fff; font-weight: bold; margin-left: 12px; font-family: 'Playfair Display', serif; font-size: 20px; overflow:hidden;">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                    <?php else: ?>
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="page-header" style="border-bottom: none; padding-bottom: 0;">
            <h1>Completed Reads</h1>
            <div class="search-box" style="max-width: 350px; width: 100%;">
                <i class="fas fa-search"></i>
                <input type="text" id="historySearch" placeholder="Search history by title...">
            </div>
        </div>

        <div class="stats-grid" id="historyGrid" style="margin-top: 10px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
            <?php if ($books): ?>
                <?php foreach ($books as $book): ?>
                    <div class="stat-card history-card" style="display:block; text-align:center; position: relative; overflow:hidden; opacity: 0.95;">
                        <div style="width: 120px; height: 160px; background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)); margin: 0 auto 20px auto; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--text-muted); border: 1px solid rgba(255,255,255,0.08); position: relative; filter: grayscale(20%);">
                            <i class="fas fa-book"></i>
                            <div style="position: absolute; top: -10px; right: -10px; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: white; font-size: 14px; background: var(--text-muted);">
                                <i class="fas fa-check-double"></i>
                            </div>
                        </div>
                        <h3 class="book-title" style="font-family: 'Playfair Display', serif; font-size: 18px; color: #fff; margin-bottom: 8px;"><?= e($book['book_title']) ?></h3>
                        <p class="book-author" style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">By <?= e($book['author']) ?></p>
                        <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 12px; margin-bottom: 20px; display:flex; justify-content: space-between; font-size: 12px;">
                            <div style="text-align:left;">
                                <span style="display:block; text-transform: uppercase; font-size: 10px; font-weight: 600; color: var(--text-muted);">Issued On</span>
                                <span style="color:#fff; font-weight:600;"><?= e(date('M d, Y', strtotime((string) $book['issue_date']))) ?></span>
                            </div>
                            <div style="text-align:right;">
                                <span style="display:block; text-transform: uppercase; font-size: 10px; font-weight: 600; color: var(--text-muted);">Returned On</span>
                                <span style="color:#fff; font-weight:600;"><?= e($book['return_date'] ? date('M d, Y', strtotime((string) $book['return_date'])) : date('M d, Y', strtotime((string) $book['due_date']))) ?></span>
                            </div>
                        </div>
                        <button class="btn-gold" type="button" disabled style="width: 100%; justify-content: center; opacity: 0.7;"><i class="fas fa-check-circle"></i> Successfully Returned</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.12);">
                    <div style="width: 80px; height: 80px; background: rgba(197, 160, 89, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: var(--text-muted); font-size: 32px;">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 style="font-family: 'Playfair Display', serif; color: #fff; margin-bottom: 10px;">No Borrowing History</h3>
                    <p style="color: var(--text-muted);">You haven't returned any books yet.</p>
                    <a href="<?= e(url('catalog.php')) ?>" class="btn-gold" style="display: inline-flex; width: auto; padding: 12px 30px; margin-top: 15px; text-decoration: none;">
                        <i class="fas fa-search"></i> Find a Book to Read
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const historySearch = document.getElementById('historySearch');
        if (historySearch) {
            historySearch.addEventListener('keyup', function () {
                const filter = this.value.toLowerCase();
                document.querySelectorAll('.history-card').forEach((card) => {
                    const title = card.querySelector('.book-title')?.innerText.toLowerCase() || '';
                    const author = card.querySelector('.book-author')?.innerText.toLowerCase() || '';
                    card.style.display = title.includes(filter) || author.includes(filter) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
