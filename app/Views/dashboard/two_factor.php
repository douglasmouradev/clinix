<div class="card">
    <h2>Autenticação em dois fatores (2FA)</h2>
    <?php if ($enabled): ?>
        <p class="success">2FA está ativo na sua conta.</p>
        <form method="post" action="<?= APP_URL ?>/?route=admin.2fa.disable" style="margin-top:14px;">
            <?= csrfInput() ?>
            <label>Senha atual para desativar</label>
            <input type="password" name="password" required>
            <button class="btn secondary" style="margin-top:10px;">Desativar 2FA</button>
        </form>
    <?php else: ?>
        <p class="muted">Configure um app autenticador (Google Authenticator, Authy, etc.) com o segredo abaixo.</p>
        <p><strong>Segredo:</strong> <code><?= e($secret) ?></code></p>
        <form method="post" action="<?= APP_URL ?>/?route=admin.2fa.enable">
            <?= csrfInput() ?>
            <input type="hidden" name="secret" value="<?= e($secret) ?>">
            <label>Código de teste</label>
            <input name="code" required maxlength="6">
            <button style="margin-top:10px;">Ativar 2FA</button>
        </form>
    <?php endif; ?>
</div>
