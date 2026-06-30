<?php
$filterTabs = [
    '' => 'Todos',
    'pending' => 'Pendentes',
    'overdue' => 'Vencidos',
    'scheduled' => 'Agendados',
    'completed' => 'Concluídos',
    'cancelled' => 'Cancelados',
];
$canSchedule = in_array(\App\Core\Auth::user()['role'] ?? '', ['admin', 'reception'], true);
$canEdit = in_array(\App\Core\Auth::user()['role'] ?? '', ['admin', 'reception', 'nurse', 'doctor'], true);
?>

<div class="card soft">
    <div class="card-title">
        <div>
            <h1 class="page-title">Gerenciamento de retornos</h1>
            <p class="muted">Acompanhe retornos pendentes, vencidos e agendados dos pacientes.</p>
        </div>
        <?php if ($canEdit): ?>
            <a class="btn small" style="width:auto;" href="<?= APP_URL ?>/?route=return.form">Novo retorno</a>
        <?php endif; ?>
    </div>
    <div class="stats">
        <div class="stat">
            <strong>Pendentes</strong>
            <p class="stat-value"><?= (int) ($counts['pending'] ?? 0) ?></p>
        </div>
        <div class="stat">
            <strong>Vencidos</strong>
            <p class="stat-value" style="color:<?= (int) ($counts['overdue'] ?? 0) > 0 ? '#b45309' : 'inherit' ?>;">
                <?= (int) ($counts['overdue'] ?? 0) ?>
            </p>
        </div>
    </div>
</div>

<div class="card">
    <div class="return-filter-tabs">
        <?php foreach ($filterTabs as $key => $label): ?>
            <?php
            $query = ['route' => 'returns'];
            if ($key !== '') {
                $query['filter'] = $key;
            }
            if (!empty($filters['q'])) {
                $query['q'] = $filters['q'];
            }
            if (!empty($filters['from'])) {
                $query['from'] = $filters['from'];
            }
            if (!empty($filters['to'])) {
                $query['to'] = $filters['to'];
            }
            $isActive = ($filters['filter'] ?? '') === $key;
            ?>
            <a class="return-tab <?= $isActive ? 'active' : '' ?>" href="<?= APP_URL ?>/?<?= http_build_query($query) ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="<?= APP_URL ?>" style="margin-top:14px;">
        <input type="hidden" name="route" value="returns">
        <?php if (!empty($filters['filter'])): ?>
            <input type="hidden" name="filter" value="<?= e($filters['filter']) ?>">
        <?php endif; ?>
        <div class="grid grid-2">
            <div>
                <label>Buscar paciente</label>
                <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nome, CPF ou telefone">
            </div>
            <div>
                <label>Previsão de</label>
                <input type="date" name="from" value="<?= e($filters['from'] ?? '') ?>">
            </div>
            <div>
                <label>Previsão até</label>
                <input type="date" name="to" value="<?= e($filters['to'] ?? '') ?>">
            </div>
            <div class="actions" style="align-self:end;">
                <button class="btn small" style="width:auto;">Filtrar</button>
                <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=returns">Limpar</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Previsão</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Motivo</th>
                <th>Status</th>
                <th>Agenda</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($returns)): ?>
                <tr>
                    <td colspan="7" class="empty-state-cell">
                        <p class="empty-state-title">Nenhum retorno encontrado</p>
                        <p class="empty-state-hint">
                            <?php if ($canEdit): ?>
                                <a href="<?= APP_URL ?>/?route=return.form">Registrar retorno</a>
                            <?php else: ?>
                                Ajuste os filtros para ver outros registros.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($returns as $item): ?>
                <?php $effectiveStatus = (string) ($item['effective_status'] ?? $item['status']); ?>
                <tr>
                    <td><?= e(formatDateBr($item['return_due_date'])) ?></td>
                    <td>
                        <strong><?= e($item['patient_name']) ?></strong>
                        <?php if (!empty($item['patient_phone'])): ?>
                            <br><span class="muted" style="font-size:12px;"><?= e($item['patient_phone']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($item['professional_name'] ?? '-') ?></td>
                    <td><?= e($item['reason'] ?? '-') ?></td>
                    <td>
                        <span class="<?= e(returnVisitStatusBadgeClass($effectiveStatus)) ?>">
                            <?= e(returnVisitStatusLabel($effectiveStatus)) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($item['appointment_scheduled_at'])): ?>
                            <?= e(formatDateTimeBr($item['appointment_scheduled_at'])) ?>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if ($canEdit && !in_array($item['status'], ['completed', 'cancelled'], true)): ?>
                            <a class="link" href="<?= APP_URL ?>/?route=return.form&id=<?= (int) $item['id'] ?>">Editar</a>
                        <?php endif; ?>
                        <?php if ($canSchedule && $item['status'] === 'pending'): ?>
                            <a class="link" href="<?= APP_URL ?>/?route=return.schedule.form&id=<?= (int) $item['id'] ?>">Agendar</a>
                        <?php endif; ?>
                        <?php if ($item['status'] === 'scheduled' && !empty($item['appointment_id'])): ?>
                            <a class="link" href="<?= APP_URL ?>/?route=appointment.form&id=<?= (int) $item['appointment_id'] ?>">Ver consulta</a>
                        <?php endif; ?>
                        <?php if ($canEdit && in_array($item['status'], ['pending', 'scheduled'], true)): ?>
                            <form method="post" action="<?= APP_URL ?>/?route=return.status" style="display:inline;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="link-btn">Concluir</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($canEdit && $item['status'] !== 'cancelled' && $item['status'] !== 'completed'): ?>
                            <form method="post" action="<?= APP_URL ?>/?route=return.status" style="display:inline;" onsubmit="return confirm('Cancelar este retorno?');">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="link-btn danger">Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
