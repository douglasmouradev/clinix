<div class="login-shell">
    <div class="login-wrap">
        <section class="login-hero">
            <div class="login-hero-logo-wrap">
                <img
                    src="<?= APP_URL ?>/img/clinix-logo-transparent.png"
                    srcset="<?= APP_URL ?>/img/clinix-logo-transparent.png 1x, <?= APP_URL ?>/img/clinix-logo@2x.png 2x"
                    sizes="220px"
                    alt="Clinix"
                    class="login-hero-logo"
                    width="276"
                    height="108"
                    decoding="async"
                >
            </div>
            <p class="login-hero-tagline">Gestão inteligente para equipes de atendimento clínico.</p>
            <div class="hero-list">
                <div class="hero-item">Prontuário compartilhado por equipe</div>
                <div class="hero-item">Fila de atendimento com chamada em tempo real</div>
                <div class="hero-item">Controle por perfil com trilha de histórico</div>
            </div>
        </section>
        <div class="card login-card soft">
            <div class="card-title">
                <div>
                    <h2>Login da Clínica</h2>
                    <p class="muted">Acesse com seu perfil profissional.</p>
                </div>
            </div>
            <?php if (!empty($error)): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= APP_URL ?>/?route=login.submit">
                <?= csrfInput() ?>
                <div style="margin-bottom:12px;">
                    <label>Clínica (slug)</label>
                    <input type="text" name="tenant_slug" required value="<?= e($tenant_slug ?? '') ?>" placeholder="ex.: clinica-demo">
                </div>
                <div style="margin-bottom:12px;">
                    <label>Usuário</label>
                    <input type="text" name="username" required>
                </div>
                <div style="margin-bottom:14px;">
                    <label>Senha</label>
                    <input type="password" name="password" required>
                </div>
                <button class="btn-block">Entrar</button>
            </form>
            <p style="margin-top:12px;text-align:center;">
                <a class="link" href="<?= APP_URL ?>/?route=password.forgot&tenant=<?= urlencode($tenant_slug ?? '') ?>">Esqueci minha senha</a>
                ·
                <a class="link" href="<?= APP_URL ?>/?route=portal&tenant=<?= urlencode($tenant_slug ?? '') ?>">Portal do paciente</a>
            </p>
        </div>
    </div>
</div>

