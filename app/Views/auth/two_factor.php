<div class="login-shell">
    <div class="card login-card soft" style="max-width:420px;margin:0 auto;">
        <h2>Verificação 2FA</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <p class="muted">Informe o código de 6 dígitos do seu aplicativo autenticador.</p>
        <form method="post" action="<?= APP_URL ?>/?route=login.2fa.submit">
            <?= csrfInput() ?>
            <label>Código</label>
            <input name="code" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code">
            <button style="margin-top:12px;">Validar</button>
        </form>
    </div>
</div>
