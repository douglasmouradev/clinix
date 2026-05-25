<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Configuracoes do Painel Publico</h2>
            <p class="muted">Gerencie o token de acesso da TV/senha de exibicao.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=admin.users">Voltar</a>
    </div>
</div>

<div class="card">
    <label>URL atual do painel</label>
    <?php
    $panelUrl = APP_URL . '/?route=queue.panel&token=' . rawurlencode($panelToken);
    if (!empty($tenantSlug)) {
        $panelUrl .= '&tenant=' . rawurlencode($tenantSlug);
    }
    ?>
    <input readonly value="<?= e($panelUrl) ?>">
    <p class="muted" style="margin-top:8px;">Compartilhe esta URL apenas com dispositivos autorizados.</p>

    <label style="margin-top:16px;display:block;">URL do totem (tablet)</label>
    <?php
    $kioskUrl = APP_URL . '/?route=queue.kiosk&token=' . rawurlencode($panelToken);
    if (!empty($tenantSlug)) {
        $kioskUrl .= '&tenant=' . rawurlencode($tenantSlug);
    }
    ?>
    <input readonly value="<?= e($kioskUrl) ?>">
    <p class="muted" style="margin-top:8px;">Abra esta URL em tela cheia no tablet. Opções: agendado (CPF) ou sem agendamento (senha na hora).</p>

    <form method="post" action="<?= APP_URL ?>/?route=admin.panel.rotate" style="margin-top:12px;">
        <?= csrfInput() ?>
        <button class="btn small" style="width:auto;">Rotacionar token do painel</button>
    </form>
</div>

