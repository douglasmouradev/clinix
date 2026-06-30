<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Mailer;

final class Notification
{
    public function schedule(string $channel, string $recipient, string $subject, string $body, string $scheduledAt): void
    {
        $sql = 'INSERT INTO notification_outbox (tenant_id, channel, recipient, subject, body, status, scheduled_at)
                VALUES (:tenant_id, :channel, :recipient, :subject, :body, "pending", :scheduled_at)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function processDue(): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM notification_outbox
             WHERE status = "pending" AND scheduled_at <= NOW()
             ORDER BY scheduled_at ASC LIMIT 100'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $sent = 0;

        foreach ($rows as $row) {
            $ok = $this->deliver($row);
            $update = Database::connection()->prepare(
                'UPDATE notification_outbox SET status = :status, sent_at = NOW() WHERE id = :id'
            );
            $update->execute([
                'id' => (int) $row['id'],
                'status' => $ok ? 'sent' : 'failed',
            ]);
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    /** @param array<string, mixed> $row */
    private function deliver(array $row): bool
    {
        $channel = (string) $row['channel'];
        $recipient = (string) $row['recipient'];
        $subject = (string) $row['subject'];
        $body = (string) $row['body'];

        $logLine = sprintf(
            "[%s] tenant=%d channel=%s to=%s subject=%s\n%s\n",
            date('c'),
            (int) $row['tenant_id'],
            $channel,
            $recipient,
            $subject,
            $body
        );
        @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/notifications.log', $logLine, FILE_APPEND);

        if ($channel === 'email' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return Mailer::send($recipient, $subject, $body);
        }

        if ($channel === 'whatsapp') {
            return false;
        }

        return $channel === 'log';
    }
}
