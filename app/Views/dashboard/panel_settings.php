<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Painel e totem</h2>
            <p class="muted">URLs e tokens para TV de senhas e tablet de autoatendimento.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=admin.users">Voltar</a>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Painel TV</h3>
    <label>URL do painel</label>
    <?php
    $panelUrl = APP_URL . '/?route=queue.panel&token=' . rawurlencode($panelToken);
    if (!empty($tenantSlug)) {
        $panelUrl .= '&tenant=' . rawurlencode($tenantSlug);
    }
    ?>
    <div class="device-url-row">
        <input id="panel-url" readonly value="<?= e($panelUrl) ?>">
        <button type="button" class="btn secondary small device-copy-btn" data-copy-target="panel-url">Copiar</button>
    </div>
    <p class="muted" style="margin-top:8px;">Abra em tela cheia na TV. O token abaixo é exclusivo do painel.</p>
    <p class="muted"><strong>Token:</strong> <code><?= e($panelToken) ?></code></p>

    <form method="post" action="<?= APP_URL ?>/?route=admin.panel.rotate" style="margin-top:12px;">
        <?= csrfInput() ?>
        <button class="btn small" style="width:auto;">Rotacionar token do painel</button>
    </form>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Totem (tablet)</h3>
    <label>URL do totem</label>
    <?php
    $kioskUrl = APP_URL . '/?route=queue.kiosk&token=' . rawurlencode($kioskToken);
    if (!empty($tenantSlug)) {
        $kioskUrl .= '&tenant=' . rawurlencode($tenantSlug);
    }
    ?>
    <div class="device-url-row">
        <input id="kiosk-url" readonly value="<?= e($kioskUrl) ?>">
        <button type="button" class="btn secondary small device-copy-btn" data-copy-target="kiosk-url">Copiar</button>
    </div>
    <p class="muted" style="margin-top:8px;">
        Senhas <strong>A</strong> (agendado, CPF) e <strong>B</strong> (sem agendamento). Token separado do painel TV.
    </p>
    <p class="muted"><strong>Token:</strong> <code><?= e($kioskToken) ?></code></p>

    <form method="post" action="<?= APP_URL ?>/?route=admin.panel.rotate.kiosk" style="margin-top:12px;">
        <?= csrfInput() ?>
        <button class="btn small secondary" style="width:auto;">Rotacionar token do totem</button>
    </form>
</div>

<style>
    .device-url-row {
        display: flex;
        gap: 10px;
        align-items: stretch;
        flex-wrap: wrap;
    }
    .device-url-row input {
        flex: 1;
        min-width: 200px;
    }
    .device-copy-btn {
        flex-shrink: 0;
    }
</style>
<script>
    document.querySelectorAll('.device-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-copy-target');
            var input = document.getElementById(id);
            if (!input) {
                return;
            }
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(function () {
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar'; }, 2000);
            }).catch(function () {
                document.execCommand('copy');
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar'; }, 2000);
            });
        });
    });
</script>
