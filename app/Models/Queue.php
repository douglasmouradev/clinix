<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Queue
{
    public function generateToken(int $patientId, int $createdBy, ?string $room = null): void
    {
        $conn = Database::connection();
        $today = date('Y-m-d');
        $stmt = $conn->prepare('SELECT COUNT(*) FROM queue_tickets WHERE DATE(created_at) = :today AND tenant_id = :tenant_id');
        $stmt->execute(['today' => $today, 'tenant_id' => tenantId()]);
        $number = (int) $stmt->fetchColumn() + 1;

        $insert = $conn->prepare('INSERT INTO queue_tickets (tenant_id, patient_id, ticket_number, status, room, created_by) VALUES (:tenant_id, :patient_id, :ticket_number, :status, :room, :created_by)');
        $insert->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'ticket_number' => str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'status' => 'waiting',
            'room' => $room,
            'created_by' => $createdBy,
        ]);
    }

    public function waiting(): array
    {
        $sql = 'SELECT qt.*, p.full_name FROM queue_tickets qt
                INNER JOIN patients p ON p.id = qt.patient_id
                WHERE qt.status IN ("waiting", "called")
                  AND qt.tenant_id = :tenant_id
                ORDER BY 
                    (qt.status = "called") DESC,
                    CASE WHEN qt.status = "called" THEN qt.called_at END DESC,
                    qt.id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }

    public function call(int $ticketId, string $room, int $calledBy): void
    {
        $sql = 'UPDATE queue_tickets 
                SET status = "called", room = :room, called_by = :called_by, called_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $ticketId, 'room' => $room, 'called_by' => $calledBy, 'tenant_id' => tenantId()]);
    }

    public function finish(int $ticketId): void
    {
        $sql = 'UPDATE queue_tickets SET status = "done" WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $ticketId, 'tenant_id' => tenantId()]);
    }
}

