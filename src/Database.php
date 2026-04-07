<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $db = Config::get('DB_NAME', 'lms');
        $user = Config::get('DB_USER', 'root');
        $pass = Config::get('DB_PASS', 'Sakshi@1o1');

        try {
            self::$connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            Response::json([
                'message' => 'Database connection failed',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return self::$connection;
    }
}

