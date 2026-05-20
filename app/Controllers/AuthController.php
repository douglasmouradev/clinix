<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Totp;
use App\Core\View;
use App\Models\Tenant;
use App\Models\User;

final class AuthController
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCK_MINUTES = 15;

    public function loginForm(): void
    {
        View::render('auth/login', ['tenant_slug' => $_GET['tenant'] ?? '']);
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $tenantSlug = trim((string) ($_POST['tenant_slug'] ?? ''));

        if ($tenantSlug === '' || $username === '' || $password === '') {
            View::render('auth/login', ['error' => 'Informe usuário e senha.', 'tenant_slug' => $tenantSlug]);
            return;
        }

        $tenant = (new Tenant())->findBySlug($tenantSlug);
        if (!$tenant) {
            View::render('auth/login', ['error' => 'Clínica não encontrada.', 'tenant_slug' => $tenantSlug]);
            return;
        }
        $_SESSION['tenant_context_id'] = (int) $tenant['id'];

        if ($this->isLocked($username)) {
            View::render('auth/login', ['error' => 'Muitas tentativas. Aguarde 15 minutos e tente novamente.', 'tenant_slug' => $tenantSlug]);
            return;
        }

        $user = (new User())->findByUsername($username);
        if ($user && (int) ($user['is_active'] ?? 1) === 0) {
            View::render('auth/login', ['error' => 'Conta inativa. Procure o administrador.', 'tenant_slug' => $tenantSlug]);
            return;
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->registerFailure($username);
            View::render('auth/login', ['error' => 'Credenciais invalidas.', 'tenant_slug' => $tenantSlug]);
            return;
        }

        $this->clearAttempts($username);

        if ((int) ($user['two_factor_enabled'] ?? 0) === 1) {
            $_SESSION['pending_2fa_user'] = $user;
            redirect('/?route=login.2fa');
            return;
        }

        $this->completeLogin($user);
    }

    public function twoFactorForm(): void
    {
        if (empty($_SESSION['pending_2fa_user'])) {
            redirect('/?route=login');
            return;
        }

        View::render('auth/two_factor', []);
    }

    public function twoFactorVerify(): void
    {
        $user = $_SESSION['pending_2fa_user'] ?? null;
        if (!$user) {
            redirect('/?route=login');
            return;
        }

        $code = trim((string) ($_POST['code'] ?? ''));
        $secret = (string) ($user['two_factor_secret'] ?? '');
        if ($secret === '' || !Totp::verify($secret, $code)) {
            View::render('auth/two_factor', ['error' => 'Código inválido.']);
            return;
        }

        unset($_SESSION['pending_2fa_user']);
        $this->completeLogin($user);
    }

    public function logout(): void
    {
        if (Auth::check()) {
            auditLog('auth.logout', 'Usuário encerrou sessão');
        }
        Auth::logout();
        redirect('/?route=login');
    }

    private function completeLogin(array $user): void
    {
        Auth::login($user);
        auditLog('auth.login', 'Usuário autenticado');
        if (Auth::mustChangePassword()) {
            flash('info', 'Defina uma nova senha para continuar.');
            redirect('/?route=password.change');
            return;
        }

        flash('success', 'Login realizado com sucesso.');
        redirect('/?route=dashboard');
    }

    private function getIpAddress(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    private function isLocked(string $username): bool
    {
        $sql = 'SELECT locked_until FROM auth_login_attempts WHERE username = :username AND ip_address = :ip AND tenant_id = :tenant_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['username' => $username, 'ip' => $this->getIpAddress(), 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();

        return $row && !empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time();
    }

    private function registerFailure(string $username): void
    {
        $lockMinutes = self::LOGIN_LOCK_MINUTES;
        $sql = 'INSERT INTO auth_login_attempts (tenant_id, username, ip_address, attempts, last_attempt_at, locked_until)
                VALUES (:tenant_id, :username, :ip_address, 1, NOW(), NULL)
                ON DUPLICATE KEY UPDATE
                    attempts = attempts + 1,
                    last_attempt_at = NOW(),
                    locked_until = IF(attempts + 1 >= :max_attempts, DATE_ADD(NOW(), INTERVAL ' . $lockMinutes . ' MINUTE), NULL)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'username' => $username,
            'ip_address' => $this->getIpAddress(),
            'max_attempts' => self::LOGIN_MAX_ATTEMPTS,
        ]);
    }

    private function clearAttempts(string $username): void
    {
        $sql = 'DELETE FROM auth_login_attempts WHERE username = :username AND ip_address = :ip AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['username' => $username, 'ip' => $this->getIpAddress(), 'tenant_id' => tenantId()]);
    }
}
