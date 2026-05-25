<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DeviceTokens;
use App\Core\TenantSettings;
use App\Core\PasswordPolicy;
use App\Core\View;
use App\Models\Billing;
use App\Models\Tenant;
use App\Models\User;

final class AdminController
{
    private static function normalizeClinicSlug(string $raw): string
    {
        $slug = strtolower((string) (preg_replace('/[^a-z0-9\-]+/', '-', $raw) ?? ''));
        return trim($slug, '-');
    }

    public function users(): void
    {
        Auth::requireRole(['admin']);
        $users = (new User())->all();
        View::render('dashboard/users', ['users' => $users, 'panelToken' => $this->currentPanelToken()]);
    }

    public function userForm(): void
    {
        Auth::requireRole(['admin']);
        $id = (int) ($_GET['id'] ?? 0);
        $user = $id > 0 ? (new User())->find($id) : null;
        View::render('dashboard/user_form', ['editUser' => $user]);
    }

    public function userSave(): void
    {
        Auth::requireRole(['admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $allowedRoles = ['admin', 'reception', 'nurse', 'doctor'];
        if ($name === '' || $username === '' || !in_array($role, $allowedRoles, true)) {
            View::render('dashboard/user_form', [
                'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role],
                'error' => 'Preencha nome, usuário e perfil validos.',
            ]);
            return;
        }

        $userModel = new User();
        if ($id === 0 && !(new Billing())->canCreateUser(tenantId())) {
            View::render('dashboard/user_form', [
                'editUser' => ['name' => $name, 'username' => $username, 'role' => $role, 'is_active' => $isActive],
                'error' => 'Limite de usuários do plano atingido. Atualize seu plano em Billing.',
            ]);
            return;
        }
        if ($userModel->existsByUsername($username, $id > 0 ? $id : null)) {
            View::render('dashboard/user_form', [
                'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role],
                'error' => 'Este nome de usuário ja esta em uso.',
            ]);
            return;
        }

        if ($id > 0) {
            $existingUser = $userModel->find($id);
            if (!$existingUser) {
                redirect('/?route=admin.users');
            }

            if ((int) $existingUser['id'] === (int) Auth::user()['id'] && $isActive === 0) {
                View::render('dashboard/user_form', [
                    'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'is_active' => 1],
                    'error' => 'Voce não pode inativar a propria conta.',
                ]);
                return;
            }

            if ($existingUser['role'] === 'admin' && $role !== 'admin' && $userModel->activeAdminCount() <= 1) {
                View::render('dashboard/user_form', [
                    'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'is_active' => $isActive],
                    'error' => 'Deve existir ao menos um admin ativo.',
                ]);
                return;
            }

            if ($existingUser['role'] === 'admin' && $isActive === 0 && $userModel->activeAdminCount() <= 1) {
                View::render('dashboard/user_form', [
                    'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'is_active' => 1],
                    'error' => 'Deve existir ao menos um admin ativo.',
                ]);
                return;
            }

            $userModel->update($id, ['name' => $name, 'username' => $username, 'role' => $role, 'is_active' => $isActive]);
            if ($password !== '') {
                $policyError = PasswordPolicy::validate($password);
                if ($policyError !== null) {
                    View::render('dashboard/user_form', [
                        'editUser' => ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'is_active' => $isActive],
                        'error' => $policyError,
                    ]);
                    return;
                }
                $userModel->updatePassword($id, PasswordPolicy::hash($password), true);
            }
            auditLog('admin.user.update', 'Usuário ID ' . $id . ' atualizado');
            flash('success', 'Usuário atualizado com sucesso.');
        } else {
            $policyError = PasswordPolicy::validate($password);
            if ($policyError !== null) {
                View::render('dashboard/user_form', [
                    'editUser' => ['name' => $name, 'username' => $username, 'role' => $role],
                    'error' => $policyError,
                ]);
                return;
            }

            $userModel->create([
                'name' => $name,
                'username' => $username,
                'password_hash' => PasswordPolicy::hash($password),
                'role' => $role,
                'is_active' => $isActive,
            ]);
            auditLog('admin.user.create', 'Usuário ' . $username . ' criado');
            flash('success', 'Usuário criado com sucesso.');
        }

        redirect('/?route=admin.users');
    }

    public function panelSettings(): void
    {
        Auth::requireRole(['admin']);
        $tenant = (new Tenant())->find(tenantId());
        View::render('dashboard/panel_settings', [
            'panelToken' => DeviceTokens::panelToken(tenantId()),
            'kioskToken' => DeviceTokens::kioskToken(tenantId()),
            'tenantSlug' => (string) ($tenant['slug'] ?? ''),
            'panelHideNames' => TenantSettings::panelHideNames(),
            'defaultTokenWarning' => APP_ENV === 'production' && TenantSettings::isDefaultPanelTokenInUse(),
        ]);
    }

    public function panelSettingsSave(): void
    {
        Auth::requireRole(['admin']);
        TenantSettings::setPanelHideNames(isset($_POST['panel_hide_names']));
        auditLog('admin.panel.settings', 'Configurações do painel/totem atualizadas');
        flash('success', 'Configurações salvas.');
        redirect('/?route=admin.panel');
    }

    public function clinicSlug(): void
    {
        Auth::requireRole(['admin']);
        $tenant = (new Tenant())->find(tenantId());
        if (!$tenant) {
            flash('error', 'Clínica não encontrada.');
            redirect('/?route=dashboard');
            return;
        }
        View::render('dashboard/clinic_slug', ['tenant' => $tenant]);
    }

    public function clinicSlugSave(): void
    {
        Auth::requireRole(['admin']);
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find(tenantId());
        if (!$tenant) {
            redirect('/?route=dashboard');
            return;
        }

        $slug = self::normalizeClinicSlug(trim((string) ($_POST['slug'] ?? '')));
        if ($slug === '' || strlen($slug) < 3) {
            View::render('dashboard/clinic_slug', [
                'tenant' => $tenant,
                'error' => 'Informe um slug válido (apenas letras minúsculas sem acento, números e hífens, mínimo 3 caracteres).',
            ]);
            return;
        }

        if ($tenantModel->slugTakenByOtherTenant($slug, tenantId())) {
            View::render('dashboard/clinic_slug', [
                'tenant' => $tenant,
                'error' => 'Este slug já está em uso por outra clínica.',
            ]);
            return;
        }

        $tenantModel->updateSlug(tenantId(), $slug);
        auditLog('admin.clinic.slug', 'Slug da clínica atualizado para ' . $slug);
        flash('success', 'Slug da clínica atualizado com sucesso. Informe a equipe para usar o novo valor no login.');
        redirect('/?route=admin.clinic');
    }

    public function rotatePanelToken(): void
    {
        Auth::requireRole(['admin']);
        DeviceTokens::rotate('panel_access_token', tenantId());
        auditLog('admin.panel.rotate_token', 'Token do painel rotacionado');
        flash('success', 'Token do painel atualizado com sucesso.');
        redirect('/?route=admin.panel');
    }

    public function rotateKioskToken(): void
    {
        Auth::requireRole(['admin']);
        DeviceTokens::rotate('kiosk_access_token', tenantId());
        auditLog('admin.kiosk.rotate_token', 'Token do totem rotacionado');
        flash('success', 'Token do totem atualizado com sucesso.');
        redirect('/?route=admin.panel');
    }

    public function apiTokens(): void
    {
        $created = $_SESSION['api_token_plain'] ?? null;
        unset($_SESSION['api_token_plain']);
        View::render('dashboard/api_tokens', [
            'tokens' => (new \App\Models\ApiToken())->all(),
            'createdToken' => $created,
        ]);
    }

    public function apiTokenCreate(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Informe um nome para o token.');
            redirect('/?route=admin.api');
            return;
        }
        $plain = (new \App\Models\ApiToken())->create($name);
        $_SESSION['api_token_plain'] = $plain;
        auditLog('admin.api.create', 'Token API criado: ' . $name);
        flash('success', 'Token criado. Copie agora — não será exibido novamente.');
        redirect('/?route=admin.api');
    }

    public function apiTokenRevoke(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            (new \App\Models\ApiToken())->revoke($id);
            auditLog('admin.api.revoke', 'Token API ID ' . $id . ' revogado');
            flash('success', 'Token revogado.');
        }
        redirect('/?route=admin.api');
    }

    private function currentPanelToken(): string
    {
        $stmt = Database::connection()->prepare('SELECT `value` FROM app_settings WHERE `key` = "panel_access_token" AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => tenantId()]);
        $token = (string) ($stmt->fetchColumn() ?: '');
        return $token !== '' ? $token : PANEL_ACCESS_TOKEN;
    }
}

