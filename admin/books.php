<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$admin = require_admin();
$activePage = 'books';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$departments = departments_all();
$csvTemplateHeaders = books_csv_template_headers();

if (isset($_GET['download']) && (string) $_GET['download'] === 'books-csv-template') {
    output_books_csv_template();
}

if (is_post() && isset($_POST['save_book'])) {
    $bookId = isset($_POST['book_id']) && $_POST['book_id'] !== '' ? (int) $_POST['book_id'] : null;
    $result = save_book($_POST, $bookId);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/books.php');
}

if (is_post() && isset($_POST['delete_book'])) {
    $result = delete_book((int) ($_POST['book_id'] ?? 0));
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/books.php');
}

if (is_post() && isset($_POST['import_books_csv'])) {
    $result = import_books_from_csv($_FILES['books_csv'] ?? []);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('admin/books.php');
}

$editBook = $editId
    ? db_one(
        'SELECT b.*, d.name AS Department
         FROM books_table b
         LEFT JOIN departments d ON d.id = b.department_id
         WHERE b.id = ?',
        [$editId],
        'i'
    )
    : null;
$books = db_all(
    'SELECT b.*, d.name AS Department
     FROM books_table b
     LEFT JOIN departments d ON d.id = b.department_id
     ORDER BY b.created_at DESC, b.id DESC'
);
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books | LMS</title>
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
                <h2 style="color:#0f172a;">Book Inventory</h2>
                <p style="color:#94a3b8;">Add, edit, and organize the catalog.</p>
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

        <div class="stats-grid" style="grid-template-columns: minmax(320px, 430px) minmax(0, 1fr); align-items:start;">
            <div style="display:grid; gap:20px;">
                <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                    <h3 style="margin-top:0; color:#0f172a; font-family:'Playfair Display', serif;"><?= $editBook ? 'Edit Book' : 'Add New Book' ?></h3>
                    <form method="POST" action="<?= e(url('admin/books.php')) ?>" style="display:grid; gap:14px;">
                        <input type="hidden" name="book_id" value="<?= e((string) ($editBook['id'] ?? '')) ?>">
                        <input type="text" name="title" placeholder="Book Title" value="<?= e((string) ($editBook['Title'] ?? '')) ?>" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                        <input type="text" name="author" placeholder="Author" value="<?= e((string) ($editBook['Author'] ?? '')) ?>" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                        <select name="department_id" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                            <option value="" disabled <?= empty($editBook['department_id']) ? 'selected' : '' ?>>Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= e((string) $department['id']) ?>" <?= selected((string) $department['id'], (string) ($editBook['department_id'] ?? '')) ?>>
                                    <?= e($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" min="1" name="total_copies" placeholder="Total Copies" value="<?= e((string) ($editBook['Total_copies'] ?? '1')) ?>" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;">
                        <textarea name="description" placeholder="Description" rows="4" style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px;"><?= e((string) ($editBook['Description'] ?? '')) ?></textarea>
                        <button type="submit" name="save_book" class="btn-gold" style="background: linear-gradient(135deg, #c5a059, #a38244); color:#fff; justify-content:center;">Save Book</button>
                    </form>
                </div>

                <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
                        <div>
                            <h3 style="margin:0 0 6px 0; color:#0f172a; font-family:'Playfair Display', serif;">Bulk Book Upload</h3>
                            <p style="margin:0; color:#64748b; font-size:14px; line-height:1.6;">Download the CSV template, fill the same exact columns only, then upload the file to import all books at once.</p>
                        </div>
                        <a href="<?= e(url('admin/books.php?download=books-csv-template')) ?>" class="btn-gold" style="background: linear-gradient(135deg, #0f172a, #1e293b); color:#fff; justify-content:center;">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>

                    <div style="margin-bottom:18px; padding:14px 16px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0;">
                        <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; font-weight:700; color:#475569; margin-bottom:10px;">CSV Template Columns</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <?php foreach ($csvTemplateHeaders as $header): ?>
                                <span style="display:inline-flex; align-items:center; padding:7px 10px; border-radius:999px; background:#fff; border:1px solid #cbd5e1; color:#0f172a; font-size:12px; font-weight:600;"><?= e($header) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin-bottom:18px; color:#64748b; font-size:13px; line-height:1.7;">
                        <strong style="color:#0f172a;">Important:</strong> CSV me sirf yehi exact columns hone chahiye: <code style="background:#eff6ff; color:#1d4ed8; padding:2px 6px; border-radius:6px;">Title, Author, Department, department_id, Description, Total_copies, Available_copies</code>. <code style="background:#eff6ff; color:#1d4ed8; padding:2px 6px; border-radius:6px;">Department</code> aur <code style="background:#eff6ff; color:#1d4ed8; padding:2px 6px; border-radius:6px;">department_id</code> match karne chahiye.
                    </div>

                    <form method="POST" action="<?= e(url('admin/books.php')) ?>" enctype="multipart/form-data" style="display:grid; gap:14px;">
                        <input type="file" name="books_csv" accept=".csv,text/csv" required style="padding:12px 14px; border:1px solid #dbe3ee; border-radius:10px; background:#fff;">
                        <button type="submit" name="import_books_csv" class="btn-gold" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color:#fff; justify-content:center;">
                            <i class="fas fa-file-import"></i> Upload CSV and Import Books
                        </button>
                    </form>
                </div>
            </div>

            <div class="table-container" style="background:#fff; border:1px solid #e2e8f0;">
                <div class="table-controls">
                    <h3 style="margin:0; color:#0f172a; font-family:'Playfair Display', serif;">Catalog List</h3>
                    <span style="color:#94a3b8; font-size:14px;"><?= e(count($books)) ?> books</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="color:#94a3b8;">Title</th>
                                <th style="color:#94a3b8;">Department</th>
                                <th style="color:#94a3b8;">Copies</th>
                                <th style="color:#94a3b8;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td style="color:#0f172a; font-weight:600;"><?= e($book['Title']) ?><br><span style="color:#94a3b8; font-size:12px;">by <?= e($book['Author']) ?></span></td>
                                    <td style="color:#334155;"><?= e($book['Department']) ?></td>
                                    <td style="color:#334155;"><?= e((string) $book['Available_copies']) ?> / <?= e((string) $book['Total_copies']) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a class="btn-icon edit" href="<?= e(url('admin/books.php?edit=' . (int) $book['id'])) ?>" style="color:#c5a059; border-color:#e2e8f0; text-decoration:none;"><i class="fas fa-pen"></i></a>
                                            <form method="POST" action="<?= e(url('admin/books.php')) ?>" onsubmit="return confirm('Delete this book from the catalog?');">
                                                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
                                                <button class="btn-icon delete" type="submit" name="delete_book" style="color:#ef4444; border-color:#e2e8f0;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/admin_portal_footer.php'; ?>
    </div>
</body>
</html>

