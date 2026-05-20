<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Totp;
use App\Core\View;
use App\Models\User;

final class TwoFactorController
{
    public function settings(): void
    {
        $user = (new User())->findWithSecrets((int) Auth::user()['id']);
        $secret = (string) ($user['two_factor_secret'] ?? '');
        if ($secret === '') {
            $secret = Totp::generateSecret();
            $_SESSION['pending_2fa_setup_secret'] = $secret;
        }

        View::render('dashboard/two_factor', [
            'enabled' => (int) ($user['two_factor_enabled'] ?? 0) === 1,
            'secret' => $secret,
        ]);
    }

    public function enable(): void
    {
        $code = trim((string) ($_POST['code'] ?? ''));
        $secret = (string) ($_SESSION['pending_2fa_setup_secret'] ?? ($_POST['secret'] ?? ''));
        if ($secret === '' || !Totp::verify($secret, $code)) {
            flash('error', 'Código inválido. Tente novamente.');
            redirect('/?route=admin.2fa');
            return;
        }

        (new User())->setTwoFactor((int) Auth::user()['id'], $secret, true);
        unset($_SESSION['pending_2fa_setup_secret']);
        auditLog('auth.2fa.enable', '2FA ativado');
        flash('success', 'Autenticação em dois fatores ativada.');
        redirect('/?route=admin.2fa');
    }

    public function disable(): void
    {
        $password = (string) ($_POST['password'] ?? '');
        $user = (new User())->findWithSecrets((int) Auth::user()['id']);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            flash('error', 'Senha incorreta.');
            redirect('/?route=admin.2fa');
            return;
        }

        (new User())->setTwoFactor((int) Auth::user()['id'], null, false);
        auditLog('auth.2fa.disable', '2FA desativado');
        flash('success', '2FA desativado.');
        redirect('/?route=admin.2fa');
    }
}
