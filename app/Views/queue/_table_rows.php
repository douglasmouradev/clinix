<?php

/** @var list<array<string, mixed>> $queue */
/** @var string $role */

if (empty($queue)): ?>
    <tr><td colspan="5">Nenhuma senha na fila.</td></tr>
<?php else: ?>
    <?php foreach ($queue as $ticket): ?>
        <tr data-ticket-id="<?= (int) $ticket['id'] ?>">
            <td>#<?= e($ticket['ticket_number']) ?></td>
            <td><?= e($ticket['full_name']) ?></td>
            <td><?= e($ticket['status']) ?></td>
            <td><?= e($ticket['room'] ?? '-') ?></td>
            <td class="queue-actions">
                <?php if (in_array($role, ['admin', 'reception'], true)): ?>
                    <button
                        type="button"
                        class="btn secondary small queue-print-btn"
                        style="width:auto;"
                        data-ticket-id="<?= (int) $ticket['id'] ?>"
                    >Imprimir</button>
                <?php endif; ?>
                <?php if (in_array($role, ['admin', 'reception', 'nurse', 'doctor'], true) && $ticket['status'] === 'waiting'): ?>
                    <button
                        type="button"
                        class="btn small queue-call-btn"
                        style="width:auto;"
                        data-ticket-id="<?= (int) $ticket['id'] ?>"
                        data-room="<?= e($ticket['room'] ?: ($role === 'reception' ? 'Recepção' : 'Triagem')) ?>"
                    >Chamar senha</button>
                <?php endif; ?>
                <?php if (in_array($role, ['admin', 'nurse', 'doctor'], true) && $ticket['status'] === 'called'): ?>
                    <button
                        type="button"
                        class="btn small queue-done-btn"
                        style="width:auto;"
                        data-ticket-id="<?= (int) $ticket['id'] ?>"
                    >Finalizar</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
