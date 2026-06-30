<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\PortalRateLimiter;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReturnVisit;
use App\Models\Tenant;

final class PortalController
{
    private const SESSION_TIMEOUT_SECONDS = 7200;

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

        if (PortalRateLimiter::isLocked($tenantSlug, $cpf)) {
            View::renderBare('portal/login', [
                'error' => 'Muitas tentativas. Aguarde 15 minutos e tente novamente.',
                'tenant_slug' => $tenantSlug,
            ]);
            return;
        }

        $tenant = (new Tenant())->findBySlug($tenantSlug);
        if (!$tenant || strlen($cpf) !== 11 || $birthDate === '') {
            PortalRateLimiter::registerFailure($tenantSlug, $cpf);
            View::renderBare('portal/login', [
                'error' => 'Informe clínica, CPF e data de nascimento válidos.',
                'tenant_slug' => $tenantSlug,
            ]);
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $tenant['id'];
        $patient = (new Patient())->findByCpfAndBirthDate($cpf, $birthDate);
        if ($patient === null) {
            PortalRateLimiter::registerFailure($tenantSlug, $cpf);
            View::renderBare('portal/login', [
                'error' => 'Paciente não encontrado. Verifique os dados ou procure a recepção.',
                'tenant_slug' => $tenantSlug,
            ]);
            return;
        }

        PortalRateLimiter::clear($tenantSlug, $cpf);
        $_SESSION['portal_patient'] = [
            'id' => (int) $patient['id'],
            'tenant_id' => (int) $tenant['id'],
            'full_name' => (string) $patient['full_name'],
            'tenant_slug' => $tenantSlug,
        ];
        $_SESSION['portal_login_at'] = time();

        redirect('/?route=portal.home');
    }

    public function home(): void
    {
        $portal = $this->requirePortalSession();
        $_SESSION['tenant_context_id'] = (int) $portal['tenant_id'];
        $patientId = (int) $portal['id'];

        View::renderBare('portal/home', [
            'patient' => $portal,
            'appointments' => (new Appointment())->upcomingForPatient($patientId),
            'returns' => (new ReturnVisit())->pendingForPatient($patientId),
        ]);
    }

    public function confirmAppointment(): void
    {
        $portal = $this->requirePortalSession();
        $_SESSION['tenant_context_id'] = (int) $portal['tenant_id'];

        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $appointment = (new Appointment())->findForPatient($appointmentId, (int) $portal['id']);
        if ($appointment === null || (string) ($appointment['status'] ?? '') !== 'scheduled') {
            flash('error', 'Consulta não encontrada ou já confirmada.');
            redirect('/?route=portal.home');
            return;
        }

        (new Appointment())->updateStatus($appointmentId, 'checked_in');
        flash('success', 'Consulta confirmada com sucesso.');
        redirect('/?route=portal.home');
    }

    public function cancelAppointment(): void
    {
        $portal = $this->requirePortalSession();
        $_SESSION['tenant_context_id'] = (int) $portal['tenant_id'];

        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $appointment = (new Appointment())->findForPatient($appointmentId, (int) $portal['id']);
        if ($appointment === null || !in_array((string) ($appointment['status'] ?? ''), ['scheduled', 'checked_in'], true)) {
            flash('error', 'Não foi possível cancelar esta consulta.');
            redirect('/?route=portal.home');
            return;
        }

        (new Appointment())->updateStatus($appointmentId, 'cancelled');
        flash('success', 'Consulta cancelada. Entre em contato com a clínica se precisar reagendar.');
        redirect('/?route=portal.home');
    }

    public function logout(): void
    {
        unset($_SESSION['portal_patient'], $_SESSION['portal_login_at']);
        $tenantSlug = trim((string) ($_POST['tenant_slug'] ?? ''));
        $redirect = '/?route=portal' . ($tenantSlug !== '' ? '&tenant=' . rawurlencode($tenantSlug) : '');
        redirect($redirect);
    }

    /** @return array<string, mixed> */
    private function requirePortalSession(): array
    {
        $portal = $_SESSION['portal_patient'] ?? null;
        if (!is_array($portal)) {
            redirect('/?route=portal');
            exit;
        }

        $loginAt = (int) ($_SESSION['portal_login_at'] ?? 0);
        if ($loginAt > 0 && (time() - $loginAt) > self::SESSION_TIMEOUT_SECONDS) {
            unset($_SESSION['portal_patient'], $_SESSION['portal_login_at']);
            redirect('/?route=portal');
            exit;
        }

        return $portal;
    }
}
