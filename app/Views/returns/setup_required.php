<div class="card soft">
    <div class="card-title">
        <div>
            <h1 class="page-title">Retornos — configuração pendente</h1>
            <p class="muted">O módulo foi instalado no código, mas a tabela do banco ainda não foi criada neste servidor.</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>O que fazer na VPS</h3>
    <p>Conecte-se ao servidor e execute:</p>
    <pre style="background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;overflow:auto;font-size:13px;line-height:1.5;">cd /opt/clinix-app
git pull origin main
docker compose -f docker-compose.prod.yml exec -T app php database/migrate.php
docker compose -f docker-compose.prod.yml restart app</pre>
    <p class="muted" style="margin-top:12px;">
        Ou use o script: <code>./scripts/post-deploy-vps.sh</code>
    </p>
    <div class="actions" style="margin-top:16px;">
        <a class="btn small" style="width:auto;" href="<?= APP_URL ?>/?route=returns">Tentar novamente</a>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=dashboard">Voltar ao painel</a>
    </div>
</div>
