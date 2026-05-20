<div class="login-shell">
    <div class="card login-card soft" style="max-width:480px;margin:0 auto;">
        <h2>Esqueci minha senha</h2>
        <?php if (!empty($success)): ?>
            <div class="success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= APP_URL ?>/?route=password.forgot.submit">
            <?= csrfInput() ?>
            <label>Clínica (slug)</label>
            <input name="tenant_slug" required value="<?= e($tenant_slug ?? '') ?>" placeholder="clinica-demo">
            <label style="margin-top:10px;">Usuário</label>
            <input name="username" required>
            <label style="margin-top:10px;">E-mail (para receber o link)</label>
            <input type="email" name="email" required>
            <button style="margin-top:12px;">Enviar link</button>
        </form>
        <p style="margin-top:12px;"><a class="link" href="<?= APP_URL ?>/?route=login">Voltar ao login</a></p>
    </div>
</div>
