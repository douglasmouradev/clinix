<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiAuth;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\ReturnVisit;

final class ApiController
{
    public function patients(): void
    {
        if (ApiAuth::authorize('patients') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        jsonResponse([
            'data' => (new Patient())->search($search !== '' ? $search : null, 100),
        ]);
    }

    public function queue(): void
    {
        if (ApiAuth::authorize('queue') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        jsonResponse([
            'waiting' => (new Queue())->ticketsForManage(),
            'called' => (new Queue())->currentCalled(),
        ]);
    }

    public function appointments(): void
    {
        if (ApiAuth::authorize('appointments') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        jsonResponse([
            'data' => (new Appointment())->all($date, $status !== '' ? $status : null),
        ]);
    }

    public function returns(): void
    {
        if (ApiAuth::authorize('returns') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $filter = trim((string) ($_GET['filter'] ?? ''));
        jsonResponse([
            'data' => (new ReturnVisit())->list($filter !== '' ? $filter : null),
        ]);
    }
}
