<?php

declare(strict_types=1);

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

function database_config(): array
{
    return [
        'host' => getenv('LMS_DB_HOST') ?: 'localhost',
        'username' => getenv('LMS_DB_USER') ?: 'root',
        'password' => getenv('LMS_DB_PASSWORD') ?: 'M4a1..,.,.,@',
        'database' => getenv('LMS_DB_NAME') ?: 'library',
        'port' => (int) (getenv('LMS_DB_PORT') ?: 3306),
    ];
}

function database_connection()
{
    static $connection = null;

    if (!extension_loaded('mysqli') || !class_exists('mysqli') || !function_exists('mysqli_init')) {
        throw new RuntimeException(
            'The PHP mysqli extension is not enabled. Enable "extension=mysqli" in php.ini, then restart Apache/PHP.'
        );
    }

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $config = database_config();
    $hosts = array_unique([$config['host'], 'localhost', '127.0.0.1']);
    $lastException = null;

    foreach ($hosts as $host) {
        try {
            $candidate = mysqli_init();
            $candidate->real_connect(
                $host,
                $config['username'],
                $config['password'],
                null,
                $config['port']
            );
            $candidate->set_charset('utf8mb4');
            $candidate->query(
                sprintf(
                    "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    $candidate->real_escape_string($config['database'])
                )
            );
            $candidate->select_db($config['database']);
            $connection = $candidate;

            return $connection;
        } catch (Throwable $exception) {
            $lastException = $exception;
        }
    }

    throw $lastException ?? new RuntimeException('Unable to connect to the MySQL server.');
}
