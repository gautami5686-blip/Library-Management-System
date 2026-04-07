<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use PDO;

final class ReportController extends Controller
{
    public function issuedBooks(array $params = []): never
    {
        $stmt = $this->db->query(
            'SELECT cr.id, b.title, b.author, u.name AS member_name, u.email, cr.issued_at, cr.due_date, cr.status
             FROM circulation_records cr
             INNER JOIN books b ON b.id = cr.book_id
             INNER JOIN users u ON u.id = cr.user_id
             WHERE cr.status = "issued"
             ORDER BY cr.due_date ASC'
        );

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function overdueBooks(array $params = []): never
    {
        $stmt = $this->db->query(
            'SELECT cr.id, b.title, u.name AS member_name, u.email, cr.due_date,
                    DATEDIFF(CURDATE(), cr.due_date) AS days_overdue
             FROM circulation_records cr
             INNER JOIN books b ON b.id = cr.book_id
             INNER JOIN users u ON u.id = cr.user_id
             WHERE cr.status = "issued" AND cr.due_date < CURDATE()
             ORDER BY cr.due_date ASC'
        );

        $this->ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function activityLogs(array $params = []): never
    {
        $stmt = $this->db->query(
            'SELECT activity_logs.id, users.name AS actor_name, users.role, activity_logs.action,
                    activity_logs.entity_type, activity_logs.entity_id, activity_logs.meta_json, activity_logs.created_at
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             ORDER BY activity_logs.created_at DESC
             LIMIT 200'
        );

        $logs = array_map(function (array $log): array {
            $log['meta'] = $log['meta_json'] ? json_decode($log['meta_json'], true) : null;
            unset($log['meta_json']);
            return $log;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        $this->ok(['data' => $logs]);
    }
}
