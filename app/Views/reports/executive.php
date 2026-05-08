<?php
$kpis = $kpis ?? [];
$appointmentsByStatus = $appointmentsByStatus ?? [];
?>
<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Relatórios Executivos</h2>
            <p class="muted">Indicadores operacionais e de atendimento da clínica.</p>
        </div>
    </div>
    <form method="get" class="actions">
        <input type="hidden" name="route" value="reports.executive">
        <div>
            <label>De</label>
            <input type="date" name="date_from" value="<?= e($dateFrom ?? '') ?>">
        </div>
        <div>
            <label>Ate</label>
            <input type="date" name="date_to" value="<?= e($dateTo ?? '') ?>">
        </div>
        <div style="align-self:flex-end;">
            <button class="btn small" style="width:auto;">Filtrar</button>
            <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=reports.executive.csv&date_from=<?= urlencode((string) ($dateFrom ?? '')) ?>&date_to=<?= urlencode((string) ($dateTo ?? '')) ?>">Exportar CSV</a>
        </div>
    </form>
</div>

<div class="stats">
    <div class="stat"><strong>Pacientes ativos</strong><p><?= (int) ($kpis['active_patients'] ?? 0) ?></p></div>
    <div class="stat"><strong>Agendamentos</strong><p><?= (int) ($kpis['appointments_total'] ?? 0) ?></p></div>
    <div class="stat"><strong>Senhas geradas</strong><p><?= (int) ($kpis['queue_total'] ?? 0) ?></p></div>
    <div class="stat"><strong>Registros clinicos</strong><p><?= (int) ($kpis['records_total'] ?? 0) ?></p></div>
    <div class="stat"><strong>Usuários ativos</strong><p><?= (int) ($kpis['active_users'] ?? 0) ?></p></div>
</div>

<div class="card">
    <h3>Agendamentos por status</h3>
    <p class="muted">Visao consolidada para tomada de decisao operacional.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($appointmentsByStatus as $row): ?>
                <tr>
                    <td><?= e($row['status']) ?></td>
                    <td><?= (int) $row['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

