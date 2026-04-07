<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    private static ?array $resolvedUser = null;

    public static function user(): array
    {
        if (self::$resolvedUser !== null) {
            return self::$resolvedUser;
        }

        $token = Request::bearerToken();
        if (!$token) {
            Response::json(['message' => 'Authentication required'], 401);
        }

        $stmt = Database::connection()->prepare(
            'SELECT users.id, users.name, users.email, users.role, users.phone_encrypted
             FROM access_tokens
             INNER JOIN users ON users.id = access_tokens.user_id
             WHERE access_tokens.token_hash = :token_hash
               AND access_tokens.revoked_at IS NULL
               AND (access_tokens.expires_at IS NULL OR access_tokens.expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::json(['message' => 'Invalid or expired token'], 401);
        }

        $user['phone'] = Encryption::decryptNullable($user['phone_encrypted'] ?? null);
        unset($user['phone_encrypted']);

        self::$resolvedUser = $user;
        return $user;
    }

    public static function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = Database::connection()->prepare(
            'INSERT INTO access_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
        ]);

        return $token;
    }

    public static function revokeCurrentToken(): void
    {
        $token = Request::bearerToken();
        if (!$token) {
            return;
        }

        Database::connection()->prepare(
            'UPDATE access_tokens SET revoked_at = NOW() WHERE token_hash = :token_hash'
        )->execute(['token_hash' => hash('sha256', $token)]);
    }
}

