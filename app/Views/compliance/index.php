<?php
$policy = $policy ?? [];
?>
<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Compliance Enterprise</h2>
            <p class="muted">Hardening de LGPD, retenção e anonimização assistida.</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Política de retenção LGPD</h3>
    <form method="post" action="<?= APP_URL ?>/?route=compliance.policy.save">
        <?= csrfInput() ?>
        <div class="grid grid-2">
            <div>
                <label>Retenção (dias)</label>
                <input type="number" min="30" max="3650" name="retention_days" value="<?= (int) ($policy['retention_days'] ?? LGPD_RETENTION_DAYS_DEFAULT) ?>">
            </div>
            <div style="display:flex;align-items:flex-end;">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="auto_anonymize" value="1" <?= !empty($policy['auto_anonymize']) ? 'checked' : '' ?> style="width:auto;">
                    Ativar anonimização automática por política
                </label>
            </div>
        </div>
        <div style="margin-top:12px;">
            <button class="btn small" style="width:auto;">Salvar política</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Execução manual de retenção</h3>
    <p class="muted">Executa anonimização em lote conforme janela de retenção definida.</p>
    <form method="post" action="<?= APP_URL ?>/?route=compliance.retention.run" onsubmit="return confirm('Executar anonimização de retenção agora?');">
        <?= csrfInput() ?>
        <button class="btn secondary small" style="width:auto;">Executar agora</button>
    </form>
</div>

