<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use PDO;

final class NotificationService
{
    public static function create(int $userId, string $type, string $message, ?int $bookId = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO notifications (user_id, type, message, book_id)
             VALUES (:user_id, :type, :message, :book_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'book_id' => $bookId,
        ]);
    }

    public static function createAvailabilityAlertsForBook(int $bookId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT availability_alerts.user_id, books.title
             FROM availability_alerts
             INNER JOIN books ON books.id = availability_alerts.book_id
             WHERE availability_alerts.book_id = :book_id AND availability_alerts.fulfilled_at IS NULL'
        );
        $stmt->execute(['book_id' => $bookId]);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($alerts as $alert) {
            self::create(
                (int) $alert['user_id'],
                'availability_alert',
                "The book \"{$alert['title']}\" is available again.",
                $bookId
            );
        }

        $pdo->prepare(
            'UPDATE availability_alerts
             SET fulfilled_at = NOW()
             WHERE book_id = :book_id AND fulfilled_at IS NULL'
        )->execute(['book_id' => $bookId]);
    }
}
