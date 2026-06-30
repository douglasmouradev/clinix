<?php require __DIR__ . '/_layout_start.php'; ?>
<div class="card soft">
    <div class="card-title">
        <div>
            <h1 class="page-title">Olá, <?= e($patient['full_name'] ?? 'Paciente') ?></h1>
            <p class="muted">Suas próximas consultas e retornos previstos.</p>
        </div>
        <form method="post" action="<?= APP_URL ?>/?route=portal.logout">
            <?= csrfInput() ?>
            <button class="btn secondary small" style="width:auto;">Sair</button>
        </form>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Próximas consultas</h3>
        <?php if (empty($appointments)): ?>
            <p class="muted">Nenhuma consulta agendada no momento.</p>
        <?php else: ?>
            <ul class="portal-list">
                <?php foreach ($appointments as $appointment): ?>
                    <li>
                        <strong><?= e(formatDateTimeBr($appointment['scheduled_at'])) ?></strong>
                        <span><?= e($appointment['professional_name'] ?? 'Profissional a definir') ?></span>
                        <span class="muted"><?= e($appointment['reason'] ?? '') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Retornos previstos</h3>
        <?php if (empty($returns)): ?>
            <p class="muted">Nenhum retorno pendente.</p>
        <?php else: ?>
            <ul class="portal-list">
                <?php foreach ($returns as $item): ?>
                    <li>
                        <strong><?= e(formatDateBr($item['return_due_date'])) ?></strong>
                        <span><?= e($item['reason'] ?? 'Retorno') ?></span>
                        <span class="<?= e(returnVisitStatusBadgeClass((string) ($item['effective_status'] ?? $item['status']))) ?>">
                            <?= e(returnVisitStatusLabel((string) ($item['effective_status'] ?? $item['status']))) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/_layout_end.php'; ?>
