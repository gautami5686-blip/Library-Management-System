<?php

declare(strict_types=1);

namespace App;

final class Encryption
{
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = hash('sha256', (string) Config::get('APP_KEY', 'fallback-key'), true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            return null;
        }

        return base64_encode($iv . $cipher);
    }

    public static function decryptNullable(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < 17) {
            return null;
        }

        $key = hash('sha256', (string) Config::get('APP_KEY', 'fallback-key'), true);
        $iv = substr($decoded, 0, 16);
        $cipher = substr($decoded, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? null : $plain;
    }
}

