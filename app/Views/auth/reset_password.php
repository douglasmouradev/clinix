<div class="login-shell">
    <div class="card login-card soft" style="max-width:480px;margin:0 auto;">
        <h2>Nova senha</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= APP_URL ?>/?route=password.reset.submit">
            <?= csrfInput() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant_slug ?? '') ?>">
            <label>Nova senha</label>
            <input type="password" name="password" required minlength="10">
            <label style="margin-top:10px;">Confirmar senha</label>
            <input type="password" name="confirm_password" required minlength="10">
            <button style="margin-top:12px;">Salvar</button>
        </form>
    </div>
</div>
