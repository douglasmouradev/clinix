<?php
$recentCalls = $recentCalls ?? [];
$displayCalled = $displayCalled ?? null;
?>
<div class="card panel-display-card">
    <div class="card-title">
        <div>
            <h2>Painel de Chamada</h2>
            <p class="muted">Atualização automática a cada <?= (int) (($panelPollMs ?? 4000) / 1000) ?> segundos.</p>
        </div>
        <span class="pill" id="panel-waiting-count"><?= (int) ($waiting_count ?? 0) ?> aguardando</span>
    </div>
    <div id="panel-content" class="panel-content" aria-live="polite" aria-atomic="true">
        <?php if ($displayCalled): ?>
            <div class="panel-call-card <?= !empty($displayCalled['live']) ? 'panel-call-active' : 'panel-call-last' ?>">
                <p class="panel-call-label"><?= !empty($displayCalled['live']) ? 'Senha chamada agora' : 'Última chamada' ?></p>
                <h1 class="panel-call-number">#<?= e((string) $displayCalled['ticket_number']) ?></h1>
                <p class="panel-call-name"><?= e((string) $displayCalled['full_name']) ?></p>
                <p class="panel-call-room">Dirija-se a: <strong><?= e((string) ($displayCalled['room'] ?: 'A definir')) ?></strong></p>
            </div>
        <?php else: ?>
            <div class="panel-call-card panel-call-idle">
                <p class="panel-call-label">Aguardando</p>
                <h1 class="panel-call-number">—</h1>
                <p class="panel-call-name">Nenhuma senha chamada hoje</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel-recent" id="panel-recent">
        <h3>Últimas pessoas chamadas</h3>
        <?php if ($recentCalls === []): ?>
            <p class="muted panel-recent-empty">Nenhuma chamada registrada hoje.</p>
        <?php else: ?>
            <ul class="panel-recent-list">
                <?php foreach ($recentCalls as $item): ?>
                    <li class="panel-recent-item">
                        <span class="panel-recent-number">#<?= e((string) $item['ticket_number']) ?></span>
                        <span class="panel-recent-name"><?= e((string) $item['full_name']) ?></span>
                        <span class="panel-recent-meta">
                            <?= e((string) ($item['room'] ?: '-')) ?>
                            <?php if (!empty($item['time_label'])): ?>
                                · <?= e((string) $item['time_label']) ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<script>
    window.CLINIX_PANEL = {
        dataUrl: <?= json_encode($panelDataUrl, JSON_UNESCAPED_UNICODE) ?>,
        useSse: <?= !empty($panelUseSse) ? 'true' : 'false' ?>,
        initial: <?= json_encode($panelInitialPayload ?? null, JSON_UNESCAPED_UNICODE) ?>,
        pollMs: <?= (int) ($panelPollMs ?? 4000) ?>
    };
</script>
<script src="<?= APP_URL ?>/js/queue-panel.js?v=6" defer></script>
