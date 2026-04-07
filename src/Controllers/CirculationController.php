<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Controller;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use PDO;

final class CirculationController extends Controller
{
    public function issue(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['user_id', 'book_id']);

        $bookStmt = $this->db->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $bookStmt->execute(['id' => (int) $payload['book_id']]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $this->ok(['message' => 'Book not found'], 404);
        }

        if ((int) $book['copies_available'] < 1) {
            $this->ok(['message' => 'Book is not currently available'], 422);
        }

        $userStmt = $this->db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => (int) $payload['user_id']]);
        if (!$userStmt->fetch(PDO::FETCH_ASSOC)) {
            $this->ok(['message' => 'User not found'], 404);
        }

        $issueDays = max(1, (int) Config::get('DEFAULT_ISSUE_DAYS', 14));
        $dueDate = $payload['due_date'] ?? (new \DateTimeImmutable('today'))->modify("+{$issueDays} days")->format('Y-m-d');

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO circulation_records (user_id, book_id, issued_by, issued_at, due_date, status)
                 VALUES (:user_id, :book_id, :issued_by, NOW(), :due_date, "issued")'
            );
            $stmt->execute([
                'user_id' => (int) $payload['user_id'],
                'book_id' => (int) $payload['book_id'],
                'issued_by' => (int) $this->currentUser()['id'],
                'due_date' => $dueDate,
            ]);

            $recordId = (int) $this->db->lastInsertId();
            $this->db->prepare(
                'UPDATE books SET copies_available = copies_available - 1 WHERE id = :id'
            )->execute(['id' => (int) $payload['book_id']]);
            $this->db->commit();

            ActivityLogger::log((int) $this->currentUser()['id'], 'issued', 'circulation_record', $recordId, [
                'book_id' => (int) $payload['book_id'],
                'user_id' => (int) $payload['user_id'],
            ]);
        } catch (\Throwable $throwable) {
            $this->db->rollBack();
            $this->ok(['message' => 'Issue transaction failed', 'error' => $throwable->getMessage()], 500);
        }

        $this->ok(['message' => 'Book issued successfully', 'due_date' => $dueDate], 201);
    }

    public function returnBook(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['circulation_id']);

        $stmt = $this->db->prepare(
            'SELECT cr.*, b.title
             FROM circulation_records cr
             INNER JOIN books b ON b.id = cr.book_id
             WHERE cr.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => (int) $payload['circulation_id']]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            $this->ok(['message' => 'Circulation record not found'], 404);
        }

        if ($record['status'] !== 'issued') {
            $this->ok(['message' => 'This record has already been closed'], 422);
        }

        $today = new \DateTimeImmutable('today');
        $dueDate = new \DateTimeImmutable($record['due_date']);
        $daysLate = $today > $dueDate ? (int) $dueDate->diff($today)->days : 0;
        $finePerDay = (float) Config::get('DEFAULT_FINE_PER_DAY', 5);
        $fine = round($daysLate * $finePerDay, 2);

        $this->db->beginTransaction();
        try {
            $update = $this->db->prepare(
                'UPDATE circulation_records
                 SET returned_at = NOW(), returned_to = :returned_to, fine_amount = :fine_amount, status = "returned"
                 WHERE id = :id'
            );
            $update->execute([
                'id' => (int) $payload['circulation_id'],
                'returned_to' => (int) $this->currentUser()['id'],
                'fine_amount' => $fine,
            ]);

            $this->db->prepare(
                'UPDATE books SET copies_available = copies_available + 1 WHERE id = :id'
            )->execute(['id' => (int) $record['book_id']]);
            $this->db->commit();
        } catch (\Throwable $throwable) {
            $this->db->rollBack();
            $this->ok(['message' => 'Return transaction failed', 'error' => $throwable->getMessage()], 500);
        }

        NotificationService::createAvailabilityAlertsForBook((int) $record['book_id']);
        ActivityLogger::log((int) $this->currentUser()['id'], 'returned', 'circulation_record', (int) $payload['circulation_id'], [
            'fine_amount' => $fine,
        ]);

        $this->ok([
            'message' => 'Book returned successfully',
            'fine_amount' => $fine,
            'days_late' => $daysLate,
        ]);
    }

    public function history(array $params = []): never
    {
        $user = $this->currentUser();
        $query = $this->query();

        $sql = 'SELECT cr.id, cr.issued_at, cr.due_date, cr.returned_at, cr.fine_amount, cr.status,
                       b.title, b.author, issuer.name AS issued_by_name, receiver.name AS returned_to_name
                FROM circulation_records cr
                INNER JOIN books b ON b.id = cr.book_id
                INNER JOIN users issuer ON issuer.id = cr.issued_by
                LEFT JOIN users receiver ON receiver.id = cr.returned_to
                WHERE 1=1';
        $params = [];

        if ($user['role'] === 'student') {
            $sql .= ' AND cr.user_id = :user_id';
            $params['user_id'] = (int) $user['id'];
        } elseif (!empty($query['user_id'])) {
            $sql .= ' AND cr.user_id = :user_id';
            $params['user_id'] = (int) $query['user_id'];
        }

        $sql .= ' ORDER BY cr.issued_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
