<?php require __DIR__ . '/_layout_start.php'; ?>
<div class="login-shell">
    <div class="login-wrap auth-single">
        <div class="card login-card soft">
            <h2>Portal do paciente</h2>
            <p class="muted">Consulte seus agendamentos e retornos previstos.</p>
            <?php if (!empty($error)): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= APP_URL ?>/?route=portal.login">
                <?= csrfInput() ?>
                <label>Clínica (slug)</label>
                <input name="tenant_slug" required value="<?= e($tenant_slug ?? '') ?>" placeholder="clinica-demo">
                <label>CPF</label>
                <input name="cpf" required inputmode="numeric" placeholder="000.000.000-00">
                <label>Data de nascimento</label>
                <input type="date" name="birth_date" required>
                <button class="btn-block" style="margin-top:12px;">Entrar</button>
            </form>
            <p style="margin-top:12px;"><a class="link" href="<?= APP_URL ?>/?route=login">Área da clínica</a></p>
        </div>
    </div>
</div>
<script src="<?= APP_URL ?>/js/cpf-mask.js"></script>
<?php require __DIR__ . '/_layout_end.php'; ?>
