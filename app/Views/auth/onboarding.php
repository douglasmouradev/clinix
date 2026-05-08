<div class="login-shell">
    <div class="card login-card">
        <h2>Onboarding da Clínica</h2>
        <p class="muted">Crie sua clínica, admin e comece a operar.</p>
        <?php if (!empty($error)): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= APP_URL ?>/?route=onboarding.submit">
            <?= csrfInput() ?>
            <label>Nome da clínica</label>
            <input name="clinic_name" required>
            <label style="margin-top:10px;">Slug da clínica (url)</label>
            <input name="clinic_slug" required placeholder="ex.: clínica-centro">
            <label style="margin-top:10px;">Nome do administrador</label>
            <input name="admin_name" required>
            <label style="margin-top:10px;">Usuário do admin</label>
            <input name="username" required>
            <label style="margin-top:10px;">Senha</label>
            <input type="password" name="password" required>
            <div style="margin-top:12px;">
                <button>Criar clínica</button>
            </div>
        </form>
    </div>
</div>

