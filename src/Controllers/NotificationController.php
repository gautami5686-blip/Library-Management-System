<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Services\ActivityLogger;
use PDO;

final class NotificationController extends Controller
{
    public function index(array $params = []): never
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, message, book_id, is_read, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => (int) $this->currentUser()['id']]);

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function subscribeAvailabilityAlert(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['book_id']);
        $user = $this->currentUser();

        $bookStmt = $this->db->prepare(
            'SELECT id, title, copies_available FROM books WHERE id = :id LIMIT 1'
        );
        $bookStmt->execute(['id' => (int) $payload['book_id']]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $this->ok(['message' => 'Book not found'], 404);
        }

        if ((int) $book['copies_available'] > 0) {
            $this->ok(['message' => 'Book is already available; no alert needed'], 422);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO availability_alerts (user_id, book_id)
             VALUES (:user_id, :book_id)
             ON DUPLICATE KEY UPDATE fulfilled_at = NULL, created_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'user_id' => (int) $user['id'],
            'book_id' => (int) $payload['book_id'],
        ]);

        ActivityLogger::log((int) $user['id'], 'subscribed', 'availability_alert', (int) $payload['book_id']);
        $this->ok(['message' => 'Availability alert subscribed successfully'], 201);
    }
}
