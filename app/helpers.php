<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function path_prefix(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = trim($scriptDir, '/');

    if ($scriptDir === '' || $scriptDir === '.') {
        return '';
    }

    return str_repeat('../', substr_count($scriptDir, '/') + 1);
}

function url(string $path = ''): string
{
    return path_prefix() . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $key, ?string $message = null): ?string
{
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    if (!array_key_exists($key, $_SESSION['_flash'])) {
        return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    if (!isset($_SESSION['_old']) || !is_array($_SESSION['_old'])) {
        return $default;
    }

    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function remember_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function forget_input(): void
{
    unset($_SESSION['_old']);
}

function selected(string $value, ?string $current): string
{
    return $value === (string) $current ? 'selected' : '';
}

function departments_all(): array
{
    return db_all('SELECT id, name FROM departments ORDER BY name ASC, id ASC');
}

function department_by_id(int $departmentId): ?array
{
    if ($departmentId < 1) {
        return null;
    }

    return db_one('SELECT id, name FROM departments WHERE id = ?', [$departmentId], 'i');
}

function department_by_name(string $name): ?array
{
    $name = trim($name);

    if ($name === '') {
        return null;
    }

    return db_one('SELECT id, name FROM departments WHERE name = ?', [$name]);
}

function resolve_department(array $input): ?array
{
    $departmentId = (int) ($input['department_id'] ?? 0);

    if ($departmentId > 0) {
        return department_by_id($departmentId);
    }

    return department_by_name((string) ($input['department'] ?? ''));
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function db_types(array $params): string
{
    $types = '';

    foreach ($params as $param) {
        $types .= match (true) {
            is_int($param) => 'i',
            is_float($param) => 'd',
            default => 's',
        };
    }

    return $types;
}

function db_bind(mysqli_stmt $statement, array $params, ?string $types = null): void
{
    if ($params === []) {
        return;
    }

    $bindTypes = $types ?? db_types($params);
    $bindValues = [$bindTypes];

    foreach ($params as $index => $value) {
        $bindValues[] = &$params[$index];
    }

    $statement->bind_param(...$bindValues);
}

function db_execute(string $sql, array $params = [], ?string $types = null): mysqli_stmt
{
    $statement = database_connection()->prepare($sql);
    db_bind($statement, $params, $types);
    $statement->execute();

    return $statement;
}

function db_all(string $sql, array $params = [], ?string $types = null): array
{
    return db_execute($sql, $params, $types)->get_result()->fetch_all(MYSQLI_ASSOC);
}

function db_one(string $sql, array $params = [], ?string $types = null): ?array
{
    $result = db_execute($sql, $params, $types)->get_result()->fetch_assoc();

    return $result ?: null;
}

function db_value(string $sql, array $params = [], ?string $types = null): mixed
{
    $row = db_one($sql, $params, $types);

    return $row ? array_shift($row) : null;
}
