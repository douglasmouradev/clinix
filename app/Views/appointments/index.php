<?php $statusOptions = ['scheduled' => 'Agendado', 'checked_in' => 'Check-in', 'in_progress' => 'Em atendimento', 'completed' => 'Concluido', 'cancelled' => 'Cancelado']; ?>

<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Agenda</h2>
            <p class="muted">Gestao de consultas e fluxo de atendimento.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=appointments.week">Visão semanal</a>
        <?php if (in_array((\App\Core\Auth::user()['role'] ?? ''), ['admin', 'reception'], true)): ?>
            <a class="btn small" style="width:auto;" href="<?= APP_URL ?>/?route=appointment.form">Novo agendamento</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <form method="get" action="<?= APP_URL ?>">
        <input type="hidden" name="route" value="appointments">
        <div class="grid grid-2">
            <div>
                <label>Data</label>
                <input type="date" name="date" value="<?= e($filters['date'] ?? '') ?>">
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= (($filters['status'] ?? '') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="actions">
                <button class="btn small" style="width:auto;">Filtrar</button>
                <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=appointments">Limpar</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Status</th>
                <th>Motivo</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= e(formatDateTimeBr($appointment['scheduled_at'])) ?></td>
                    <td><?= e($appointment['patient_name']) ?></td>
                    <td><?= e($appointment['professional_name'] ?? '-') ?></td>
                    <td><span class="pill"><?= e($statusOptions[$appointment['status']] ?? $appointment['status']) ?></span></td>
                    <td><?= e($appointment['reason'] ?? '-') ?></td>
                    <td class="actions">
                        <?php if (in_array((\App\Core\Auth::user()['role'] ?? ''), ['admin', 'reception'], true)): ?>
                            <a class="link" href="<?= APP_URL ?>/?route=appointment.form&id=<?= (int) $appointment['id'] ?>">Editar</a>
                        <?php endif; ?>
                        <form method="post" action="<?= APP_URL ?>/?route=appointment.status">
                            <?= csrfInput() ?>
                            <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $appointment['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

