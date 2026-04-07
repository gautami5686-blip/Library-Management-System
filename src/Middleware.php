<?php

declare(strict_types=1);

namespace App;

final class Middleware
{
    public static function handle(array $middleware): void
    {
        foreach ($middleware as $item) {
            if ($item === 'auth') {
                Auth::user();
            }

            if ($item === 'librarian') {
                $user = Auth::user();
                if (($user['role'] ?? '') !== 'librarian') {
                    Response::json(['message' => 'Forbidden'], 403);
                }
            }
        }
    }
}

