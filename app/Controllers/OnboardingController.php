<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Billing;
use App\Models\Tenant;

final class OnboardingController
{
    public function form(): void
    {
        View::render('auth/onboarding');
    }

    public function submit(): void
    {
        $clinicName = trim((string) ($_POST['clinic_name'] ?? ''));
        $slug = trim((string) ($_POST['clinic_slug'] ?? ''));
        $adminName = trim((string) ($_POST['admin_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($clinicName === '' || $slug === '' || $adminName === '' || $username === '' || strlen($password) < 6) {
            View::render('auth/onboarding', ['error' => 'Preencha os dados obrigatorios e senha com no mínimo 6 caracteres.']);
            return;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '');
        $tenantModel = new Tenant();
        if ($tenantModel->findBySlug($slug)) {
            View::render('auth/onboarding', ['error' => 'Slug da clínica ja esta em uso.']);
            return;
        }

        $conn = Database::connection();
        $conn->beginTransaction();
        try {
            $tenantId = $tenantModel->create($clinicName, $slug);
            $tenantModel->seedDefaultSettings($tenantId);

            $userStmt = $conn->prepare('INSERT INTO users (tenant_id, name, username, password_hash, role, is_active)
                    VALUES (:tenant_id, :name, :username, :password_hash, "admin", 1)');
            $userStmt->execute([
                'tenant_id' => $tenantId,
                'name' => $adminName,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $conn->lastInsertId();

            (new Billing())->createInitialSubscription($tenantId);
            $conn->commit();

            $_SESSION['tenant_context_id'] = $tenantId;
            Auth::login(['id' => $userId, 'name' => $adminName, 'role' => 'admin', 'tenant_id' => $tenantId]);
            flash('success', 'Clínica criada com sucesso. Bem-vindo ao Clinix.');
            redirect('/?route=dashboard');
        } catch (\Throwable $exception) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            View::render('auth/onboarding', ['error' => 'Não foi possivel concluir onboarding.']);
        }
    }
}

