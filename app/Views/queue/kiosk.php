<?php
/** @var string $clinicName */
/** @var string $tenantSlug */
/** @var string $kioskToken */
/** @var string $kioskUrl */
/** @var string $error */
/** @var string $info */
$tenantParam = $tenantSlug !== '' ? ['tenant' => $tenantSlug] : [];
$scheduledUrl = APP_URL . '/?' . http_build_query(['route' => 'queue.kiosk.scheduled', 'token' => $kioskToken] + $tenantParam);
$walkInAction = APP_URL . '/?' . http_build_query(['route' => 'queue.kiosk.walkin', 'token' => $kioskToken] + $tenantParam);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($kioskUrl);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Totem — <?= e($clinicName) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/kiosk.css">
</head>
<body class="kiosk-body" data-kiosk-idle-seconds="45">
    <div class="kiosk-shell">
        <header class="kiosk-header">
            <h1><?= e($clinicName) ?></h1>
            <p>Toque na opção desejada para retirar sua senha</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="kiosk-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($info !== ''): ?>
            <div class="kiosk-info" role="status"><?= e($info) ?></div>
        <?php endif; ?>

        <div class="kiosk-actions">
            <a class="kiosk-btn kiosk-btn-primary" href="<?= e($scheduledUrl) ?>">
                <span class="kiosk-btn-icon" aria-hidden="true">📅</span>
                <span>Atendimento agendado</span>
                <small>Informe seu CPF · senha <strong>A</strong></small>
            </a>

            <form method="post" action="<?= e($walkInAction) ?>">
                <input type="hidden" name="token" value="<?= e($kioskToken) ?>">
                <input type="hidden" name="tenant" value="<?= e($tenantSlug) ?>">
                <button type="submit" class="kiosk-btn kiosk-btn-secondary" style="width:100%; min-height:200px;">
                    <span class="kiosk-btn-icon" aria-hidden="true">🎫</span>
                    <span>Não tenho agendamento</span>
                    <small>Emitir senha na hora · senha <strong>B</strong></small>
                </button>
            </form>
        </div>

        <div class="kiosk-qr">
            <img src="<?= e($qrUrl) ?>" width="120" height="120" alt="QR Code do totem">
            <p class="kiosk-footer-hint">Após imprimir, aguarde ser chamado no painel.<br>Sem uso, esta tela reinicia em alguns segundos.</p>
        </div>
    </div>
    <script src="<?= APP_URL ?>/js/kiosk.js" defer></script>
</body>
</html>
