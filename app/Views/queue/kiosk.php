<?php
/** @var string $clinicName */
/** @var string $tenantSlug */
/** @var string $panelToken */
/** @var string $error */
$tenantParam = $tenantSlug !== '' ? ['tenant' => $tenantSlug] : [];
$scheduledUrl = APP_URL . '/?' . http_build_query(['route' => 'queue.kiosk.scheduled', 'token' => $panelToken] + $tenantParam);
$walkInAction = APP_URL . '/?' . http_build_query(['route' => 'queue.kiosk.walkin', 'token' => $panelToken] + $tenantParam);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Totem — <?= e($clinicName) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/kiosk.css">
</head>
<body class="kiosk-body">
    <div class="kiosk-shell">
        <header class="kiosk-header">
            <h1><?= e($clinicName) ?></h1>
            <p>Toque na opção desejada para retirar sua senha</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="kiosk-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="kiosk-actions">
            <a class="kiosk-btn kiosk-btn-primary" href="<?= e($scheduledUrl) ?>">
                <span class="kiosk-btn-icon" aria-hidden="true">📅</span>
                <span>Atendimento agendado</span>
                <small>Informe seu CPF</small>
            </a>

            <form method="post" action="<?= e($walkInAction) ?>">
                <button type="submit" class="kiosk-btn kiosk-btn-secondary" style="width:100%; min-height:200px;">
                    <span class="kiosk-btn-icon" aria-hidden="true">🎫</span>
                    <span>Não tenho agendamento</span>
                    <small>Emitir senha na hora</small>
                </button>
            </form>
        </div>

        <p class="kiosk-footer-hint">Após imprimir, aguarde ser chamado no painel.</p>
    </div>
</body>
</html>
