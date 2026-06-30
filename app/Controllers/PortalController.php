<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReturnVisit;
use App\Models\Tenant;

final class PortalController
{
    public function loginForm(): void
    {
        View::renderBare('portal/login', [
            'tenant_slug' => trim((string) ($_GET['tenant'] ?? '')),
        ]);
    }

    public function login(): void
    {
        $tenantSlug = trim((string) ($_POST['tenant_slug'] ?? ''));
        $cpf = preg_replace('/\D+/', '', (string) ($_POST['cpf'] ?? ''));
        $birthDate = trim((string) ($_POST['birth_date'] ?? ''));

        $tenant = (new Tenant())->findBySlug($tenantSlug);
        if (!$tenant || strlen($cpf) !== 11 || $birthDate === '') {
            View::renderBare('portal/login', [
                'error' => 'Informe clínica, CPF e data de nascimento válidos.',
                'tenant_slug' => $tenantSlug,
            ]);
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $tenant['id'];
        $patient = (new Patient())->findByCpfAndBirthDate($cpf, $birthDate);
        if ($patient === null) {
            View::renderBare('portal/login', [
                'error' => 'Paciente não encontrado. Verifique os dados ou procure a recepção.',
                'tenant_slug' => $tenantSlug,
            ]);
            return;
        }

        $_SESSION['portal_patient'] = [
            'id' => (int) $patient['id'],
            'tenant_id' => (int) $tenant['id'],
            'full_name' => (string) $patient['full_name'],
        ];

        redirect('/?route=portal.home');
    }

    public function home(): void
    {
        $portal = $_SESSION['portal_patient'] ?? null;
        if (!is_array($portal)) {
            redirect('/?route=portal');
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $portal['tenant_id'];
        $patientId = (int) $portal['id'];

        View::renderBare('portal/home', [
            'patient' => $portal,
            'appointments' => (new Appointment())->upcomingForPatient($patientId),
            'returns' => (new ReturnVisit())->pendingForPatient($patientId),
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['portal_patient']);
        redirect('/?route=portal');
    }
}
