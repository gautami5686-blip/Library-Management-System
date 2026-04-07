<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Encryption;
use PDO;

final class UserController extends Controller
{
    public function dashboard(array $params = []): never
    {
        $user = $this->currentUser();

        $stats = [
            'total_books' => (int) $this->db->query('SELECT COUNT(*) FROM books')->fetchColumn(),
            'available_books' => (int) $this->db->query('SELECT COALESCE(SUM(copies_available), 0) FROM books')->fetchColumn(),
            'active_issues' => (int) $this->db->query('SELECT COUNT(*) FROM circulation_records WHERE status = "issued"')->fetchColumn(),
            'overdue_issues' => (int) $this->db->query('SELECT COUNT(*) FROM circulation_records WHERE status = "issued" AND due_date < CURDATE()')->fetchColumn(),
        ];

        $historyStmt = $this->db->prepare(
            'SELECT cr.id, b.title, cr.issued_at, cr.due_date, cr.returned_at, cr.fine_amount, cr.status
             FROM circulation_records cr
             INNER JOIN books b ON b.id = cr.book_id
             WHERE cr.user_id = :user_id
             ORDER BY cr.issued_at DESC
             LIMIT 10'
        );
        $historyStmt->execute(['user_id' => (int) $user['id']]);

        $this->ok([
            'user' => $user,
            'stats' => $stats,
            'recent_activity' => $historyStmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function index(array $params = []): never
    {
        $stmt = $this->db->query(
            'SELECT id, name, email, role, created_at, updated_at
             FROM users
             ORDER BY created_at DESC'
        );

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function show(array $params): never
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $params['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->ok(['message' => 'User not found'], 404);
        }

        $user['phone'] = Encryption::decryptNullable($user['phone_encrypted'] ?? null);
        unset($user['password_hash'], $user['phone_encrypted']);

        $this->ok(['data' => $user]);
    }
}
