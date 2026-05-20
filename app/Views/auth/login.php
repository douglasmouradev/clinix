<div class="login-shell">
    <div class="login-wrap">
        <section class="login-hero">
            <h2>Clinix</h2>
            <p>Gestao inteligente para equipes de atendimento clinico.</p>
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
                <button>Entrar</button>
            </form>
            <p style="margin-top:12px;text-align:center;"><a class="link" href="<?= APP_URL ?>/?route=password.forgot&tenant=<?= urlencode($tenant_slug ?? '') ?>">Esqueci minha senha</a></p>
        </div>
    </div>
</div>

