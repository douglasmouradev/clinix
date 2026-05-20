<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const SESSION_TIMEOUT_SECONDS = 1800;

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/?route=login');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();

        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, $roles, true)) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'tenant_id' => (int) ($user['tenant_id'] ?? DEFAULT_TENANT_ID),
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
            'two_factor_enabled' => (int) ($user['two_factor_enabled'] ?? 0),
        ];
        $_SESSION['last_activity_at'] = time();
    }

    public static function mustChangePassword(): bool
    {
        return self::check() && (int) ($_SESSION['user']['must_change_password'] ?? 0) === 1;
    }

    public static function clearMustChangePassword(): void
    {
        if (self::check()) {
            $_SESSION['user']['must_change_password'] = 0;
        }
    }

    /** @var list<string> */
    private const PASSWORD_CHANGE_ROUTES = [
        'password.change', 'password.change.submit', 'logout',
    ];

    public static function enforcePasswordChange(string $route): void
    {
        if (!self::mustChangePassword()) {
            return;
        }

        if (in_array($route, self::PASSWORD_CHANGE_ROUTES, true)) {
            return;
        }

        redirect('/?route=password.change');
    }

    public static function enforceSessionSecurity(): void
    {
        if (!self::check()) {
            return;
        }

        $lastActivity = (int) ($_SESSION['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) > self::SESSION_TIMEOUT_SECONDS) {
            self::logout();
            redirect('/?route=login');
        }

        $_SESSION['last_activity_at'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

