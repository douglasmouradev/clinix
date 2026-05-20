<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\PasswordPolicy;
use App\Core\View;
use App\Models\User;

final class PasswordController
{
    public function form(): void
    {
        Auth::requireLogin();
        View::render('auth/password_change', [
            'forced' => Auth::mustChangePassword(),
        ]);
    }

    public function submit(): void
    {
        Auth::requireLogin();
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $userId = (int) Auth::user()['id'];
        $user = (new User())->findWithSecrets($userId);
        if (!$user) {
            redirect('/?route=login');
            return;
        }

        if (!Auth::mustChangePassword() && !password_verify($current, (string) $user['password_hash'])) {
            View::render('auth/password_change', ['error' => 'Senha atual incorreta.', 'forced' => false]);
            return;
        }

        if ($password !== $confirm) {
            View::render('auth/password_change', ['error' => 'Confirmação não confere.', 'forced' => Auth::mustChangePassword()]);
            return;
        }

        $policyError = PasswordPolicy::validate($password);
        if ($policyError !== null) {
            View::render('auth/password_change', ['error' => $policyError, 'forced' => Auth::mustChangePassword()]);
            return;
        }

        (new User())->updatePassword($userId, PasswordPolicy::hash($password), false);
        Auth::clearMustChangePassword();
        auditLog('auth.password.change', 'Senha alterada pelo usuário');
        flash('success', 'Senha atualizada com sucesso.');
        redirect('/?route=dashboard');
    }
}
