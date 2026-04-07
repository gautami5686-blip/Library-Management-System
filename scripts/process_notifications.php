<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Database;
use App\Services\NotificationService;

$pdo = Database::connection();

$dueSoonStmt = $pdo->query(
    'SELECT cr.user_id, cr.book_id, b.title, cr.due_date
     FROM circulation_records cr
     INNER JOIN books b ON b.id = cr.book_id
     WHERE cr.status = "issued"
       AND cr.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
       AND NOT EXISTS (
           SELECT 1
           FROM notifications n
           WHERE n.user_id = cr.user_id
             AND n.book_id = cr.book_id
             AND n.type = "due_reminder"
             AND DATE(n.created_at) = CURDATE()
       )'
);

foreach ($dueSoonStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    NotificationService::create(
        (int) $row['user_id'],
        'due_reminder',
        "Reminder: \"{$row['title']}\" is due on {$row['due_date']}.",
        (int) $row['book_id']
    );
}

$overdueStmt = $pdo->query(
    'SELECT cr.user_id, cr.book_id, b.title, cr.due_date
     FROM circulation_records cr
     INNER JOIN books b ON b.id = cr.book_id
     WHERE cr.status = "issued"
       AND cr.due_date < CURDATE()
       AND NOT EXISTS (
           SELECT 1
           FROM notifications n
           WHERE n.user_id = cr.user_id
             AND n.book_id = cr.book_id
             AND n.type = "overdue_reminder"
             AND DATE(n.created_at) = CURDATE()
       )'
);

foreach ($overdueStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    NotificationService::create(
        (int) $row['user_id'],
        'overdue_reminder',
        "Overdue notice: \"{$row['title']}\" was due on {$row['due_date']}.",
        (int) $row['book_id']
    );
}

echo "Notification processing completed.\n";

