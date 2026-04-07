<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = current_user();
$admin = current_admin();
$navLinks = [
    ['label' => 'Home', 'href' => url('index.php')],
    ['label' => 'About', 'href' => url('about.php')],
    ['label' => 'Contact', 'href' => url('contact.php')],
];
$returnUrl = 'catalog.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $returnUrl .= '?' . $_SERVER['QUERY_STRING'];
}

if (is_post() && isset($_POST['reserve_book'])) {
    if (!$user) {
        flash('error', 'Please sign in before reserving a book.');
        redirect('login.php');
    }

    $result = reserve_book((int) ($_POST['book_id'] ?? 0), $user);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect($returnUrl);
}

$departmentId = (int) ($_GET['department_id'] ?? 0);
$legacyDepartment = trim((string) ($_GET['department'] ?? ''));

if ($departmentId < 1 && $legacyDepartment !== '') {
    $legacyDepartmentRow = department_by_name($legacyDepartment);
    $departmentId = (int) ($legacyDepartmentRow['id'] ?? 0);
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$where = [];
$params = [];
$types = '';

if ($departmentId > 0) {
    $where[] = 'b.department_id = ?';
    $params[] = $departmentId;
    $types .= 'i';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$totalBooks = (int) db_value(
    'SELECT COUNT(*) FROM books_table b ' . $whereSql,
    $params,
    $types !== '' ? $types : null
);
$totalPages = max(1, (int) ceil($totalBooks / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$bookParams = array_merge($params, [$perPage, $offset]);
$bookTypes = ($types !== '' ? $types : '') . 'ii';
$books = db_all(
    'SELECT b.*, d.name AS Department
     FROM books_table b
     LEFT JOIN departments d ON d.id = b.department_id
     ' . $whereSql . '
     ORDER BY b.created_at DESC
     LIMIT ? OFFSET ?',
    $bookParams,
    $bookTypes
);
$departments = departments_all();
$successMessage = flash('success');
$errorMessage = flash('error');

$userIssued = [];
$userWaitlisted = [];

if ($user) {
    foreach (db_all("SELECT book_id FROM issued_books WHERE student_id = ? AND status IN ('active', 'overdue')", [(int) $user['id']], 'i') as $issuedBook) {
        $userIssued[(int) $issuedBook['book_id']] = true;
    }

    foreach (db_all("SELECT book_id FROM book_requests WHERE student_id = ? AND status = 'waitlisted'", [(int) $user['id']], 'i') as $requestBook) {
        $userWaitlisted[(int) $requestBook['book_id']] = true;
    }
}

$badges = [
    'The Art of Innovation' => 'New Arrival',
    'The Silent Cosmos' => 'Best Seller',
    'Whispers of the Wind' => "Editor's Pick",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/catalog.css')) ?>">
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <header class="header">
        <a href="<?= e(url('index.php')) ?>" class="logo">
            <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 56px; width: auto; display: block; border-radius: 14px; background: #fff; padding: 6px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
        </a>
        <nav>
            <?php foreach ($navLinks as $link): ?>
                <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <div class="nav-auth">
                    <a href="<?= e(url('user_dashboard.php')) ?>" class="btn-nav-login">Dashboard</a>
                    <?php if ($admin): ?>
                        <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
                    <?php else: ?>
                        <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="nav-auth">
                    <a href="<?= e(url('login.php')) ?>" class="btn-nav-login">Login</a>
                    <a href="<?= e(url('signup.php')) ?>" class="btn-nav-signup">Sign Up</a>
                    <?php if ($admin): ?>
                        <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
                    <?php else: ?>
                        <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="catalog-container">
        <div class="catalog-header">
            <h1>Books Catalog</h1>
            <p>Discover our extensive collection of world-class literature</p>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="margin-bottom: 24px; background: <?= $errorMessage ? 'rgba(230,57,70,0.1)' : 'rgba(46,196,182,0.12)' ?>; border: 1px solid <?= $errorMessage ? 'rgba(230,57,70,0.3)' : 'rgba(46,196,182,0.28)' ?>; color: #fff; padding: 15px 18px; border-radius: 14px;">
                <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <form class="search-filter-bar department-filter-bar" method="GET" action="<?= e(url('catalog.php')) ?>">
            <select class="filter-select department-only-select" name="department_id" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $departmentRow): ?>
                    <option value="<?= e((string) $departmentRow['id']) ?>" <?= selected((string) $departmentRow['id'], (string) $departmentId) ?>>
                        <?= e($departmentRow['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <?php
                $bookId = (int) $book['id'];
                $available = (int) $book['Available_copies'];
                $isIssued = isset($userIssued[$bookId]);
                $isWaitlisted = isset($userWaitlisted[$bookId]);
                $statusClass = $available > 0 ? 'status-available' : 'status-issued';
                $statusLabel = $available > 0 ? 'Available' : 'Currently Issued';
                $buttonLabel = $available > 0 ? 'Reserve' : 'Waitlist';
                ?>
                <div class="book-card">
                    <?php if (isset($badges[$book['Title']])): ?>
                        <div class="book-badge"><?= e($badges[$book['Title']]) ?></div>
                    <?php endif; ?>
                    <h3 class="book-title"><?= e($book['Title']) ?></h3>
                    <p class="book-author">By <?= e($book['Author']) ?></p>

                    <div class="book-meta">
                        <div class="book-status <?= e($statusClass) ?>">
                            <i class="fas <?= $available > 0 ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= e($statusLabel) ?>
                        </div>

                        <?php if (!$user): ?>
                            <a href="<?= e(url('login.php')) ?>" class="btn-reserve" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Login</a>
                        <?php elseif ($isIssued): ?>
                            <button class="btn-reserve" type="button" disabled>Borrowed</button>
                        <?php elseif ($isWaitlisted): ?>
                            <button class="btn-reserve" type="button" disabled>Queued</button>
                        <?php else: ?>
                            <form method="POST" action="<?= e(url($returnUrl)) ?>">
                                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                                <button class="btn-reserve" type="submit" name="reserve_book"><?= e($buttonLabel) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalBooks === 0): ?>
            <div style="margin-top: 40px; padding: 30px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); color: #fff; text-align: center;">
                No books matched the selected department.
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $query = $_GET;
                $query['page'] = max(1, $page - 1);
                ?>
                <a href="<?= e(url('catalog.php?' . http_build_query($query))) ?>" class="page-btn <?= $page === 1 ? 'active' : '' ?>" <?= $page === 1 ? 'style="pointer-events:none; opacity:0.5;"' : '' ?>><i class="fas fa-chevron-left"></i></a>
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <?php $query['page'] = $pageNumber; ?>
                    <a href="<?= e(url('catalog.php?' . http_build_query($query))) ?>" class="page-btn <?= $pageNumber === $page ? 'active' : '' ?>"><?= $pageNumber ?></a>
                <?php endfor; ?>
                <?php $query['page'] = min($totalPages, $page + 1); ?>
                <a href="<?= e(url('catalog.php?' . http_build_query($query))) ?>" class="page-btn <?= $page === $totalPages ? 'active' : '' ?>" <?= $page === $totalPages ? 'style="pointer-events:none; opacity:0.5;"' : '' ?>><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <p>Manage books, empower members, and streamline records efficiently with our state-of-the-art digital library management system.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Quick Links</h3>
                <a href="<?= e(url('index.php')) ?>">Home</a>
                <a href="<?= e(url('catalog.php')) ?>">Catalog Search</a>
                <a href="<?= e(url('about.php')) ?>">About the System</a>
                <a href="<?= e(url('contact.php')) ?>">Contact Support</a>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> bipe@gmail.com</p>
                <p><i class="fas fa-phone-alt"></i> +91 2234567654</p>
                <p><i class="fas fa-map-marker-alt"></i> Gajokhar, Pindra, Varanasi, Uttar Pradesh, India</p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 Library Management System | All Rights Reserved.
        </div>
    </footer>
</body>
</html>
