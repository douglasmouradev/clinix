<?php
/** @var string $clinicName */
/** @var int $waiting_count */
/** @var string $panelDataUrl */
/** @var string $panelStreamUrl */
/** @var array $panelInitialPayload */
/** @var list<array> $recentCalls */
/** @var ?array $displayCalled */
/** @var bool $panelUseSse */
/** @var int $panelPollMs */
/** @var bool $panelHideNames */
$recentCalls = $recentCalls ?? [];
$displayCalled = $displayCalled ?? null;
$panelHideNames = !empty($panelHideNames);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel — <?= e($clinicName) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/panel.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/panel-tv.css">
</head>
<body class="panel-tv-body">
    <header class="panel-tv-header">
        <h1><?= e($clinicName) ?></h1>
        <span class="panel-tv-waiting" id="panel-waiting-count"><?= (int) $waiting_count ?> aguardando</span>
    </header>

    <main id="panel-content" class="panel-tv-main" aria-live="polite" aria-atomic="true">
        <?php if ($displayCalled): ?>
            <div class="panel-call-card <?= !empty($displayCalled['live']) ? 'panel-call-active' : 'panel-call-last' ?>">
                <p class="panel-call-label"><?= !empty($displayCalled['live']) ? 'Senha chamada agora' : 'Última chamada' ?></p>
                <h1 class="panel-call-number">#<?= e((string) $displayCalled['ticket_number']) ?></h1>
                <?php if (!$panelHideNames): ?>
                    <p class="panel-call-name"><?= e((string) $displayCalled['full_name']) ?></p>
                <?php endif; ?>
                <p class="panel-call-room">Dirija-se a: <strong><?= e((string) ($displayCalled['room'] ?: 'A definir')) ?></strong></p>
            </div>
        <?php else: ?>
            <div class="panel-call-card panel-call-idle">
                <p class="panel-call-label">Aguardando</p>
                <h1 class="panel-call-number">—</h1>
                <p class="panel-call-name">Nenhuma senha chamada hoje</p>
            </div>
        <?php endif; ?>
    </main>

    <aside class="panel-tv-recent" id="panel-recent">
        <h3>Últimas chamadas</h3>
        <?php if ($recentCalls === []): ?>
            <p class="panel-recent-empty">Nenhuma chamada hoje.</p>
        <?php else: ?>
            <ul class="panel-recent-list">
                <?php foreach ($recentCalls as $item): ?>
                    <li class="panel-recent-item">
                        <span class="panel-recent-number">#<?= e((string) $item['ticket_number']) ?></span>
                        <?php if (!$panelHideNames): ?>
                            <span class="panel-recent-name"><?= e((string) $item['full_name']) ?></span>
                        <?php endif; ?>
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
    </aside>

    <p class="panel-voice-hint" id="panel-voice-hint">Toque na tela uma vez para ativar a voz</p>

    <script>
        window.CLINIX_PANEL = {
            dataUrl: <?= json_encode($panelDataUrl, JSON_UNESCAPED_UNICODE) ?>,
            streamUrl: <?= json_encode($panelStreamUrl, JSON_UNESCAPED_UNICODE) ?>,
            useSse: <?= !empty($panelUseSse) ? 'true' : 'false' ?>,
            hideNames: <?= $panelHideNames ? 'true' : 'false' ?>,
            initial: <?= json_encode($panelInitialPayload ?? null, JSON_UNESCAPED_UNICODE) ?>,
            pollMs: <?= (int) ($panelPollMs ?? 4000) ?>
        };
    </script>
    <script src="<?= APP_URL ?>/js/queue-panel.js?v=5" defer></script>
</body>
</html>
