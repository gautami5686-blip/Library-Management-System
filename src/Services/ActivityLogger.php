<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

final class ActivityLogger
{
    public static function log(?int $userId, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, meta_json)
             VALUES (:user_id, :action, :entity_type, :entity_id, :meta_json)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}

