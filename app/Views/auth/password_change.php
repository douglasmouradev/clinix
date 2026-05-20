<div class="login-shell">
    <div class="card login-card soft" style="max-width:480px;margin:0 auto;">
        <h2>Alterar senha</h2>
        <?php if (!empty($forced)): ?>
            <p class="info">Por segurança, defina uma nova senha antes de continuar.</p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= APP_URL ?>/?route=password.change.submit">
            <?= csrfInput() ?>
            <?php if (empty($forced)): ?>
                <label>Senha atual</label>
                <input type="password" name="current_password" required>
            <?php endif; ?>
            <label style="margin-top:10px;">Nova senha</label>
            <input type="password" name="new_password" required minlength="10">
            <label style="margin-top:10px;">Confirmar nova senha</label>
            <input type="password" name="confirm_password" required minlength="10">
            <p class="muted" style="margin-top:8px;">Mínimo 10 caracteres, com maiúscula, minúscula e número.</p>
            <button style="margin-top:12px;">Salvar senha</button>
        </form>
    </div>
</div>
