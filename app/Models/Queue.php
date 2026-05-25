<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Queue
{
    public function generateToken(int $patientId, int $createdBy, ?string $room = null): ?array
    {
        $conn = Database::connection();
        $today = date('Y-m-d');
        $prefix = self::ticketPrefixForRoom($room);

        if ($prefix !== null) {
            $stmt = $conn->prepare(
                'SELECT COUNT(*) FROM queue_tickets
                 WHERE DATE(created_at) = :today AND tenant_id = :tenant_id AND ticket_number LIKE :prefix'
            );
            $stmt->execute(['today' => $today, 'tenant_id' => tenantId(), 'prefix' => $prefix . '%']);
            $number = (int) $stmt->fetchColumn() + 1;
            $ticketNumber = $prefix . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        } else {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM queue_tickets WHERE DATE(created_at) = :today AND tenant_id = :tenant_id');
            $stmt->execute(['today' => $today, 'tenant_id' => tenantId()]);
            $number = (int) $stmt->fetchColumn() + 1;
            $ticketNumber = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        }

        $insert = $conn->prepare('INSERT INTO queue_tickets (tenant_id, patient_id, ticket_number, status, room, created_by) VALUES (:tenant_id, :patient_id, :ticket_number, :status, :room, :created_by)');
        $insert->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'ticket_number' => $ticketNumber,
            'status' => 'waiting',
            'room' => $room,
            'created_by' => $createdBy,
        ]);

        return $this->findTicket((int) $conn->lastInsertId());
    }

    public static function ticketPrefixForRoom(?string $room): ?string
    {
        return match ($room) {
            'Agendado' => 'A',
            'Sem agendamento' => 'B',
            default => null,
        };
    }

    public static function queuePriorityForRoom(?string $room): int
    {
        return match ($room) {
            'Agendado' => 0,
            'Sem agendamento' => 1,
            default => 2,
        };
    }

    public function findWaitingToday(int $patientId): ?array
    {
        $sql = 'SELECT qt.*, p.full_name FROM queue_tickets qt
                INNER JOIN patients p ON p.id = qt.patient_id
                WHERE qt.patient_id = :patient_id
                  AND qt.tenant_id = :tenant_id
                  AND qt.status = "waiting"
                  AND DATE(qt.created_at) = CURDATE()
                ORDER BY qt.id DESC
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['patient_id' => $patientId, 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function kioskActorUserId(): int
    {
        $sql = 'SELECT id FROM users
                WHERE tenant_id = :tenant_id AND is_active = 1
                ORDER BY FIELD(role, "reception", "admin", "nurse", "doctor")
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        $id = (int) $stmt->fetchColumn();

        if ($id <= 0) {
            throw new \RuntimeException('Nenhum usuário ativo para registrar senhas no totem.');
        }

        return $id;
    }

    public function findTicket(int $id): ?array
    {
        $sql = 'SELECT qt.*, p.full_name FROM queue_tickets qt
                INNER JOIN patients p ON p.id = qt.patient_id
                WHERE qt.id = :id AND qt.tenant_id = :tenant_id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();

        return $row ?: null;
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
                    CASE
                        WHEN qt.room = "Agendado" THEN 0
                        WHEN qt.room = "Sem agendamento" THEN 1
                        ELSE 2
                    END,
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

    public function currentCalled(): ?array
    {
        $sql = 'SELECT qt.*, p.full_name FROM queue_tickets qt
                INNER JOIN patients p ON p.id = qt.patient_id
                WHERE qt.status = "called"
                  AND qt.tenant_id = :tenant_id
                  AND DATE(qt.called_at) = CURDATE()
                ORDER BY qt.called_at DESC
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function waitingCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM queue_tickets
                WHERE status = "waiting" AND tenant_id = :tenant_id AND DATE(created_at) = CURDATE()';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function ticketsForManage(): array
    {
        return $this->waiting();
    }

    /** @return list<array<string, mixed>> */
    public function recentCalls(int $limit = 10): array
    {
        $sql = 'SELECT qt.id, qt.ticket_number, qt.room, qt.called_at, qt.status, p.full_name
                FROM queue_tickets qt
                INNER JOIN patients p ON p.id = qt.patient_id
                WHERE qt.tenant_id = :tenant_id AND qt.status IN ("called", "done")
                  AND qt.called_at IS NOT NULL AND DATE(qt.called_at) = CURDATE()
                ORDER BY qt.called_at DESC
                LIMIT ' . max(1, min(20, $limit));
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }

    /**
     * Dados do painel em poucas consultas (evita N+1 no polling).
     *
     * @return array{display: ?array, recent: list<array>, waiting_count: int}
     */
    public function panelSnapshot(int $recentLimit = 10): array
    {
        $recent = $this->recentCalls($recentLimit);
        $display = null;

        foreach ($recent as $row) {
            if ((string) ($row['status'] ?? '') === 'called') {
                $row['panel_live'] = true;
                $display = $row;
                break;
            }
        }

        if ($display === null && $recent !== []) {
            $display = $recent[0];
            $display['panel_live'] = false;
        }

        return [
            'display' => $display,
            'recent' => $recent,
            'waiting_count' => $this->waitingCount(),
        ];
    }
}

