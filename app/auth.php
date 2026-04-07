<?php

declare(strict_types=1);

const REMEMBER_ME_LIFETIME = 2592000;
const REMEMBER_ME_COOKIE_STUDENT = 'lms_remember_student';
const REMEMBER_ME_COOKIE_ADMIN = 'lms_remember_admin';

function login_user(array $student): void
{
    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = (int) $student['id'];
    $_SESSION['user_email'] = $student['Email_Address'];
    $_SESSION['user_name'] = $student['Name'];
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
}

function remember_me_cookie_name(string $userType): string
{
    return match ($userType) {
        'student' => REMEMBER_ME_COOKIE_STUDENT,
        'admin' => REMEMBER_ME_COOKIE_ADMIN,
        default => throw new InvalidArgumentException('Unsupported remember-me user type.'),
    };
}

function remember_me_cookie_path(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = trim($scriptDir, '/');

    if ($scriptDir === '' || $scriptDir === '.') {
        return '/';
    }

    $segments = array_values(array_filter(explode('/', $scriptDir), static fn (string $segment): bool => $segment !== ''));
    $stripDirs = ['admin', 'user', 'includes', 'app', 'assets', 'config', 'database'];

    while ($segments !== [] && in_array((string) end($segments), $stripDirs, true)) {
        array_pop($segments);
    }

    if ($segments === []) {
        return '/';
    }

    return '/' . implode('/', $segments) . '/';
}

function remember_me_cookie_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function set_remember_me_cookie(string $cookieName, string $value, int $expiresAt): void
{
    setcookie($cookieName, $value, [
        'expires' => $expiresAt,
        'path' => remember_me_cookie_path(),
        'secure' => remember_me_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[$cookieName] = $value;
}

function clear_remember_me_cookie(string $cookieName): void
{
    setcookie($cookieName, '', [
        'expires' => time() - 3600,
        'path' => remember_me_cookie_path(),
        'secure' => remember_me_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE[$cookieName]);
}

function parse_remember_me_cookie(string $cookieValue): ?array
{
    $parts = explode(':', $cookieValue, 2);

    if (count($parts) !== 2) {
        return null;
    }

    [$selector, $validator] = $parts;

    if (strlen($selector) !== 32 || strlen($validator) !== 64) {
        return null;
    }

    if (!ctype_xdigit($selector) || !ctype_xdigit($validator)) {
        return null;
    }

    return [
        'selector' => strtolower($selector),
        'validator' => strtolower($validator),
    ];
}

function purge_expired_remember_tokens(): void
{
    db_execute('DELETE FROM remember_tokens WHERE expires_at < NOW()');
}

function enable_remember_me(string $userType, int $userId): void
{
    purge_expired_remember_tokens();

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $expiresAt = time() + REMEMBER_ME_LIFETIME;

    db_execute(
        'DELETE FROM remember_tokens WHERE user_type = ? AND user_id = ?',
        [$userType, $userId],
        'si'
    );

    db_execute(
        'INSERT INTO remember_tokens (user_type, user_id, selector, token_hash, expires_at, last_used_at) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), NOW())',
        [$userType, $userId, $selector, hash('sha256', $validator), $expiresAt],
        'sissi'
    );

    set_remember_me_cookie(remember_me_cookie_name($userType), $selector . ':' . $validator, $expiresAt);
}

function disable_remember_me(string $userType): void
{
    $cookieName = remember_me_cookie_name($userType);
    $cookieValue = $_COOKIE[$cookieName] ?? '';

    if (is_string($cookieValue) && $cookieValue !== '') {
        $parsed = parse_remember_me_cookie($cookieValue);

        if ($parsed !== null) {
            db_execute(
                'DELETE FROM remember_tokens WHERE user_type = ? AND selector = ?',
                [$userType, $parsed['selector']],
                'ss'
            );
        }
    }

    clear_remember_me_cookie($cookieName);
}

function sync_remember_me_preference(string $userType, int $userId, bool $remember): void
{
    if ($remember) {
        enable_remember_me($userType, $userId);

        return;
    }

    disable_remember_me($userType);
}

function attempt_remembered_login(string $userType): bool
{
    static $attempted = [
        'student' => false,
        'admin' => false,
    ];

    if (($attempted[$userType] ?? false) === true) {
        return false;
    }

    $attempted[$userType] = true;

    $cookieName = remember_me_cookie_name($userType);
    $cookieValue = $_COOKIE[$cookieName] ?? '';

    if (!is_string($cookieValue) || $cookieValue === '') {
        return false;
    }

    $parsed = parse_remember_me_cookie($cookieValue);

    if ($parsed === null) {
        clear_remember_me_cookie($cookieName);

        return false;
    }

    purge_expired_remember_tokens();

    $tokenRow = db_one(
        'SELECT user_id, token_hash FROM remember_tokens WHERE user_type = ? AND selector = ?',
        [$userType, $parsed['selector']],
        'ss'
    );

    if (!$tokenRow) {
        clear_remember_me_cookie($cookieName);

        return false;
    }

    if (!hash_equals((string) $tokenRow['token_hash'], hash('sha256', $parsed['validator']))) {
        db_execute(
            'DELETE FROM remember_tokens WHERE user_type = ? AND selector = ?',
            [$userType, $parsed['selector']],
            'ss'
        );
        clear_remember_me_cookie($cookieName);

        return false;
    }

    $account = $userType === 'student'
        ? db_one('SELECT * FROM student_table WHERE id = ?', [(int) $tokenRow['user_id']], 'i')
        : db_one('SELECT * FROM admins WHERE id = ?', [(int) $tokenRow['user_id']], 'i');

    if (!$account) {
        db_execute(
            'DELETE FROM remember_tokens WHERE user_type = ? AND selector = ?',
            [$userType, $parsed['selector']],
            'ss'
        );
        clear_remember_me_cookie($cookieName);

        return false;
    }

    db_execute(
        'DELETE FROM remember_tokens WHERE user_type = ? AND selector = ?',
        [$userType, $parsed['selector']],
        'ss'
    );

    if ($userType === 'student') {
        login_user($account);
    } else {
        login_admin($account);
    }

    enable_remember_me($userType, (int) $account['id']);

    return true;
}

function logout_all(): void
{
    disable_remember_me('student');
    disable_remember_me('admin');
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        attempt_remembered_login('student');

        if (empty($_SESSION['user_id'])) {
            $user = null;

            return null;
        }
    }

    $user = db_one(
        'SELECT s.*, d.name AS Department, d.id AS department_id
         FROM student_table s
         LEFT JOIN departments d ON d.id = s.department_id
         WHERE s.id = ?',
        [(int) $_SESSION['user_id']],
        'i'
    );

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['user_logged_in'], $_SESSION['user_email'], $_SESSION['user_name']);
    }

    return $user;
}

function current_admin(): ?array
{
    static $admin = false;

    if ($admin !== false) {
        return $admin;
    }

    if (empty($_SESSION['admin_id'])) {
        attempt_remembered_login('admin');

        if (empty($_SESSION['admin_id'])) {
            $admin = null;

            return null;
        }
    }

    $admin = db_one('SELECT * FROM admins WHERE id = ?', [(int) $_SESSION['admin_id']], 'i');

    if (!$admin) {
        unset($_SESSION['admin_id'], $_SESSION['admin_logged_in'], $_SESSION['admin_name'], $_SESSION['admin_email']);
    }

    return $admin;
}

function require_user(): array
{
    $user = current_user();

    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $admin = current_admin();

    if (!$admin) {
        flash('error', 'Admin access is required.');
        redirect('admin/login.php');
    }

    return $admin;
}
