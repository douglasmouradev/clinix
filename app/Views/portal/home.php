<?php $portalWide = true; require __DIR__ . '/_layout_start.php'; ?>
<?php $flash = pullFlash(); ?>
<?php if ($flash): ?>
    <div class="<?= e($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info')) ?>" style="margin-bottom:12px;">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>
<div class="card soft">
    <div class="card-title">
        <div>
            <h1 class="page-title">Olá, <?= e($patient['full_name'] ?? 'Paciente') ?></h1>
            <p class="muted">Suas próximas consultas e retornos previstos.</p>
        </div>
        <form method="post" action="<?= APP_URL ?>/?route=portal.logout">
            <?= csrfInput() ?>
            <input type="hidden" name="tenant_slug" value="<?= e($patient['tenant_slug'] ?? '') ?>">
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
                        <?php if (($appointment['status'] ?? '') === 'scheduled'): ?>
                            <div class="actions" style="margin-top:8px;">
                                <form method="post" action="<?= APP_URL ?>/?route=portal.appointment.confirm" style="display:inline;">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <button class="btn small" style="width:auto;">Confirmar</button>
                                </form>
                                <form method="post" action="<?= APP_URL ?>/?route=portal.appointment.cancel" style="display:inline;">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <button class="btn secondary small" style="width:auto;">Cancelar</button>
                                </form>
                            </div>
                        <?php elseif (($appointment['status'] ?? '') === 'checked_in'): ?>
                            <span class="pill" style="margin-top:6px;display:inline-block;">Confirmada</span>
                            <form method="post" action="<?= APP_URL ?>/?route=portal.appointment.cancel" style="margin-top:8px;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                <button class="btn secondary small" style="width:auto;">Cancelar consulta</button>
                            </form>
                        <?php endif; ?>
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
