<?php

declare(strict_types=1);

namespace App;

use PDO;

abstract class Controller
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function input(): array
    {
        return Request::body();
    }

    protected function query(): array
    {
        return Request::query();
    }

    protected function currentUser(): array
    {
        return Auth::user();
    }

    protected function ok(array $payload, int $status = 200): never
    {
        Response::json($payload, $status);
    }

    protected function requireFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === '' || $payload[$field] === null) {
                Response::json(['message' => "Field '{$field}' is required"], 422);
            }
        }
    }
}

