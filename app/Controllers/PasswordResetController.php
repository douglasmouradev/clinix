<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Mailer;
use App\Core\PasswordPolicy;
use App\Core\View;
use App\Models\PasswordReset;
use App\Models\Tenant;
use App\Models\User;

final class PasswordResetController
{
    public function forgotForm(): void
    {
        View::render('auth/forgot_password', ['tenant_slug' => $_GET['tenant'] ?? '']);
    }

    public function forgotSubmit(): void
    {
        $tenantSlug = trim((string) ($_POST['tenant_slug'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        $tenant = (new Tenant())->findBySlug($tenantSlug);
        if (!$tenant || $username === '') {
            View::render('auth/forgot_password', ['error' => 'Informe clínica e usuário.', 'tenant_slug' => $tenantSlug]);
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $tenant['id'];
        $user = (new User())->findByUsername($username);
        if ($user) {
            $token = (new PasswordReset())->createToken((int) $user['id'], (int) $tenant['id']);
            $link = APP_URL . '/?route=password.reset&token=' . urlencode($token) . '&tenant=' . urlencode($tenantSlug);
            $body = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha no Clinix.\n\nAcesse o link (válido por 1 hora):\n{$link}\n\nSe não foi você, ignore este e-mail.";
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mailer::send($email, 'Redefinição de senha - Clinix', $body);
            }
            if (APP_ENV !== 'production') {
                @file_put_contents(
                    dirname(__DIR__, 2) . '/storage/logs/password-reset.log',
                    date('c') . " user={$username} link={$link}\n",
                    FILE_APPEND
                );
            }
        }

        $successMessage = APP_ENV === 'production'
            ? 'Se os dados estiverem corretos, você receberá instruções por e-mail.'
            : 'Se os dados estiverem corretos, você receberá instruções por e-mail (ou verifique storage/logs/password-reset.log em ambiente local).';

        View::render('auth/forgot_password', [
            'success' => $successMessage,
            'tenant_slug' => $tenantSlug,
        ]);
    }

    public function resetForm(): void
    {
        View::render('auth/reset_password', [
            'token' => $_GET['token'] ?? '',
            'tenant_slug' => $_GET['tenant'] ?? '',
        ]);
    }

    public function resetSubmit(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $tenantSlug = trim((string) ($_POST['tenant_slug'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $tenant = (new Tenant())->findBySlug($tenantSlug);
        if (!$tenant) {
            View::render('auth/reset_password', ['error' => 'Clínica não encontrada.', 'token' => $token, 'tenant_slug' => $tenantSlug]);
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $tenant['id'];

        $userId = (new PasswordReset())->findValidUserId($token, (int) $tenant['id']);
        if ($userId === null) {
            View::render('auth/reset_password', ['error' => 'Link inválido ou expirado.', 'token' => $token, 'tenant_slug' => $tenantSlug]);
            return;
        }

        if ($password !== $confirm) {
            View::render('auth/reset_password', ['error' => 'Senhas não conferem.', 'token' => $token, 'tenant_slug' => $tenantSlug]);
            return;
        }

        $policyError = PasswordPolicy::validate($password);
        if ($policyError !== null) {
            View::render('auth/reset_password', ['error' => $policyError, 'token' => $token, 'tenant_slug' => $tenantSlug]);
            return;
        }

        (new User())->updatePassword($userId, PasswordPolicy::hash($password), false);
        (new PasswordReset())->markUsed($token);
        flash('success', 'Senha redefinida. Faça login.');
        redirect('/?route=login&tenant=' . urlencode($tenantSlug));
    }
}
