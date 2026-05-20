<?php
$user = $user ?? ['name' => '', 'role' => ''];
$stats = $stats ?? [];
?>

<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Painel Principal</h2>
            <p>Bem-vindo, <?= e($user['name']) ?>.</p>
        </div>
        <span class="pill"><?= e(roleLabel($user['role'])) ?></span>
    </div>
    <div class="stats">
        <div class="stat"><strong>Pacientes ativos</strong><p><?= (int) ($stats['patients'] ?? 0) ?></p></div>
        <div class="stat"><strong>Consultas hoje</strong><p><?= (int) ($stats['appointments_today'] ?? 0) ?></p></div>
        <div class="stat"><strong>Fila aguardando</strong><p><?= (int) ($stats['queue_waiting'] ?? 0) ?></p></div>
        <div class="stat"><strong>Registros hoje</strong><p><?= (int) ($stats['records_today'] ?? 0) ?></p></div>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="stat"><strong>Faturas em aberto</strong><p><?= (int) ($stats['open_invoices'] ?? 0) ?></p></div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Ações rápidas</h3>
        <div class="actions">
            <a class="btn small" href="<?= APP_URL ?>/?route=patients">Pacientes</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=appointments">Agenda</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=queue">Fila</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=queue.panel">Painel TV</a>
        </div>
    </div>
    <div class="card">
        <h3>Operação do dia</h3>
        <?php if ((int) ($stats['queue_waiting'] ?? 0) > 0): ?>
            <p class="info"><?= (int) $stats['queue_waiting'] ?> paciente(s) na fila agora.</p>
        <?php else: ?>
            <p class="muted">Nenhum paciente aguardando na fila.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (($user['role'] ?? '') === 'admin'): ?>
    <div class="card">
        <h3>Administração</h3>
        <div class="actions">
            <a class="btn small" href="<?= APP_URL ?>/?route=admin.users">Usuários</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=admin.api">API</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=reports.executive">Relatórios</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=admin.audit">Auditoria</a>
        </div>
    </div>
<?php endif; ?>
