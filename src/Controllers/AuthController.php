<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Controller;
use App\Encryption;
use App\Services\ActivityLogger;
use PDO;

final class AuthController extends Controller
{
    public function register(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['name', 'email', 'password', 'role']);

        $role = $payload['role'];
        if (!in_array($role, ['librarian', 'student'], true)) {
            $this->ok(['message' => 'Role must be librarian or student'], 422);
        }

        $email = strtolower(trim((string) $payload['email']));
        $existing = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetch(PDO::FETCH_ASSOC)) {
            $this->ok(['message' => 'Email already exists'], 409);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, phone_encrypted)
             VALUES (:name, :email, :password_hash, :role, :phone_encrypted)'
        );
        $stmt->execute([
            'name' => trim((string) $payload['name']),
            'email' => $email,
            'password_hash' => password_hash((string) $payload['password'], PASSWORD_BCRYPT),
            'role' => $role,
            'phone_encrypted' => Encryption::encrypt($payload['phone'] ?? null),
        ]);

        $userId = (int) $this->db->lastInsertId();
        ActivityLogger::log($userId, 'registered', 'user', $userId, ['role' => $role]);

        $token = Auth::issueToken($userId);
        $this->ok([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => trim((string) $payload['name']),
                'email' => $email,
                'role' => $role,
            ],
        ], 201);
    }

    public function login(array $params = []): never
    {
        $payload = $this->input();
        $this->requireFields($payload, ['email', 'password']);

        $email = strtolower(trim((string) $payload['email']));
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify((string) $payload['password'], $user['password_hash'])) {
            $this->ok(['message' => 'Invalid credentials'], 401);
        }

        $token = Auth::issueToken((int) $user['id']);
        ActivityLogger::log((int) $user['id'], 'login', 'user', (int) $user['id']);

        $this->ok([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'phone' => Encryption::decryptNullable($user['phone_encrypted']),
            ],
        ]);
    }

    public function logout(array $params = []): never
    {
        $user = $this->currentUser();
        Auth::revokeCurrentToken();
        ActivityLogger::log((int) $user['id'], 'logout', 'user', (int) $user['id']);

        $this->ok(['message' => 'Logout successful']);
    }
}
