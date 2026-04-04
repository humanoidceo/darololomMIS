<?php

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function with_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function auth_user(): ?array
{
    $user = $_SESSION['_auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_id(): int
{
    $user = auth_user();
    return (int) ($user['id'] ?? 0);
}

function auth_login(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['_auth_user'] = [
        'id' => (int) ($user['id'] ?? 0),
        'full_name' => (string) ($user['full_name'] ?? ''),
        'username' => (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => (string) ($user['role'] ?? 'admin'),
        'permissions' => $user['permissions'] ?? '[]',
        'can_register_students' => (int) ($user['can_register_students'] ?? 0),
        'can_register_teachers' => (int) ($user['can_register_teachers'] ?? 0),
        'student_id' => (int) ($user['student_id'] ?? 0),
        'teacher_id' => (int) ($user['teacher_id'] ?? 0),
    ];
}

function auth_logout(): void
{
    unset($_SESSION['_auth_user']);
    unset($_SESSION['_intended']);
}

function is_super_admin(): bool
{
    $user = auth_user();
    return (string) ($user['role'] ?? '') === 'super_admin';
}

function auth_role(): string
{
    $user = auth_user();
    return (string) ($user['role'] ?? '');
}

function is_teacher_user(): bool
{
    return auth_role() === 'teacher';
}

function is_student_user(): bool
{
    return auth_role() === 'student';
}

function permission_definitions(): array
{
    return [
        'access_students' => 'دسترسی به بخش شاگردان',
        'register_students' => 'ثبت شاگردان',
        'manage_students' => 'مدیریت شاگردان',
        'access_teachers' => 'دسترسی به بخش اساتید',
        'register_teachers' => 'ثبت اساتید',
        'manage_teachers' => 'ویرایش/حذف اساتید',
        'manage_classes' => 'مدیریت صنوف',
        'manage_subjects' => 'مدیریت مضامین',
        'manage_grades' => 'مدیریت نمرات',
        'manage_contracts' => 'مدیریت قراردادها',
        'manage_users' => 'مدیریت کاربران',
    ];
}

function user_permission_keys(?array $user = null): array
{
    $user ??= auth_user();
    if (!$user) {
        return [];
    }

    $definitions = permission_definitions();
    $keys = [];

    $raw = $user['permissions'] ?? null;
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $key = (string) $item;
                if ($key !== '' && array_key_exists($key, $definitions)) {
                    $keys[] = $key;
                }
            }
        }
    } elseif (is_array($raw)) {
        foreach ($raw as $item) {
            $key = (string) $item;
            if ($key !== '' && array_key_exists($key, $definitions)) {
                $keys[] = $key;
            }
        }
    }

    if ((int) ($user['can_register_students'] ?? 0) === 1) {
        $keys[] = 'register_students';
    }
    if ((int) ($user['can_register_teachers'] ?? 0) === 1) {
        $keys[] = 'register_teachers';
    }

    return array_values(array_unique($keys));
}

function can(string $permission): bool
{
    if (is_super_admin()) {
        return true;
    }

    if (!array_key_exists($permission, permission_definitions())) {
        return false;
    }

    return in_array($permission, user_permission_keys(), true);
}

function paginated_sizes(): array
{
    return (array) config('pagination.allowed', [10, 20, 50, 100]);
}

function to_persian_number(int|string $number): string
{
    $map = ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'];
    return strtr((string) $number, $map);
}

function upload_file(string $field, string $subDir, array $allowedExtensions = []): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = $_FILES[$field]['tmp_name'] ?? '';
    $original = $_FILES[$field]['name'] ?? '';
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if ($allowedExtensions !== [] && !in_array($ext, $allowedExtensions, true)) {
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
    $baseDir = dirname(__DIR__, 2) . '/public/assets/uploads/' . trim($subDir, '/');

    if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
        return null;
    }

    $target = $baseDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        return null;
    }

    return '/assets/uploads/' . trim($subDir, '/') . '/' . $filename;
}
