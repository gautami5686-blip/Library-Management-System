<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/library.php';

try {
    ensure_schema(database_connection());
    sync_overdue_items();
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Database Setup Required</title><style>body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}.card{max-width:720px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:18px;padding:28px;box-shadow:0 20px 50px rgba(0,0,0,0.35)}h1{margin:0 0 12px;font-size:30px}p{line-height:1.7;color:#cbd5e1}code{background:rgba(15,23,42,0.9);padding:3px 8px;border-radius:8px;color:#f8fafc}strong{color:#f8fafc}</style></head><body><div class="card"><h1>Database Setup Required</h1><p>The application could not connect to MySQL, so the backend cannot start yet.</p><p><strong>What to check:</strong><br>1. Start your MySQL service in XAMPP/WAMP/MAMP.<br>2. Confirm the credentials in <code>config/database.php</code>.<br>3. If your MySQL runs on another port, set <code>LMS_DB_PORT</code> or update the config.</p><p><strong>Connection error:</strong><br>' . e($exception->getMessage()) . '</p></div></body></html>';
    exit;
}
