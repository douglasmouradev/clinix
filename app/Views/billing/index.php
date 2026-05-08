<?php
$current = $current ?? null;
$plans = $plans ?? [];
$invoices = $invoices ?? [];
?>

<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Billing e Planos</h2>
            <p class="muted">Gerencie assinatura, limites e faturas do tenant.</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Plano atual</h3>
    <?php if ($current): ?>
        <p><strong><?= e($current['plan_name']) ?></strong> (<?= e($current['plan_code']) ?>)</p>
        <p class="muted">Limite de usuários: <?= (int) $current['max_users'] ?> | Limite de pacientes: <?= (int) $current['max_patients'] ?></p>
        <p class="muted">Valor mensal: R$ <?= e(number_format(((int) $current['monthly_price_cents']) / 100, 2, ',', '.')) ?></p>
    <?php else: ?>
        <p class="muted">Sem assinatura ativa.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Alterar plano</h3>
    <div class="grid grid-2">
        <?php foreach ($plans as $plan): ?>
            <div class="stat">
                <strong><?= e($plan['name']) ?></strong>
                <p class="muted">R$ <?= e(number_format(((int) $plan['monthly_price_cents']) / 100, 2, ',', '.')) ?>/mes</p>
                <p class="muted">Usuários: <?= (int) $plan['max_users'] ?> | Pacientes: <?= (int) $plan['max_patients'] ?></p>
                <form method="post" action="<?= APP_URL ?>/?route=billing.plan.change">
                    <?= csrfInput() ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                    <button class="btn small" style="width:auto;">Escolher plano</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h3>Faturas</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Vencimento</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?= e(formatDateBr($invoice['due_date'])) ?></td>
                    <td>R$ <?= e(number_format(((int) $invoice['amount_cents']) / 100, 2, ',', '.')) ?></td>
                    <td><span class="pill"><?= e($invoice['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

