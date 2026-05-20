<div class="card soft">
    <div class="card-title">
        <h2>Agenda semanal</h2>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=appointments">Visão diária</a>
    </div>
    <form method="get" class="row">
        <input type="hidden" name="route" value="appointments.week">
        <label>Semana iniciando em</label>
        <input type="date" name="start" value="<?= e($start) ?>">
        <button class="btn small" style="width:auto;">Atualizar</button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data/Hora</th><th>Paciente</th><th>Profissional</th><th>Status</th><th>Motivo</th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $item): ?>
                <tr>
                    <td><?= e(formatDateTimeBr($item['scheduled_at'])) ?></td>
                    <td><?= e($item['patient_name']) ?></td>
                    <td><?= e($item['professional_name'] ?? '-') ?></td>
                    <td><?= e($item['status']) ?></td>
                    <td><?= e($item['reason'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
