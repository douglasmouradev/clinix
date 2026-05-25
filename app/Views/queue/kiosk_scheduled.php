<?php
/** @var string $clinicName */
/** @var string $tenantSlug */
/** @var string $panelToken */
/** @var string $error */
$backUrl = APP_URL . '/?' . http_build_query(
    ['route' => 'queue.kiosk', 'token' => $panelToken] + ($tenantSlug !== '' ? ['tenant' => $tenantSlug] : [])
);
$submitQuery = http_build_query(
    ['route' => 'queue.kiosk.scheduled.submit', 'token' => $panelToken] + ($tenantSlug !== '' ? ['tenant' => $tenantSlug] : [])
);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>CPF — <?= e($clinicName) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/kiosk.css">
</head>
<body class="kiosk-body">
    <div class="kiosk-shell">
        <header class="kiosk-header">
            <h1>Atendimento agendado</h1>
            <p>Digite o CPF do paciente para emitir a senha</p>
        </header>

        <div class="kiosk-form-card">
            <?php if ($error !== ''): ?>
                <div class="kiosk-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= APP_URL ?>/?<?= e($submitQuery) ?>" autocomplete="off">
                <input type="hidden" name="tenant" value="<?= e($tenantSlug) ?>">
                <label for="kiosk-cpf">CPF</label>
                <input
                    type="text"
                    id="kiosk-cpf"
                    name="cpf"
                    inputmode="numeric"
                    pattern="[0-9.\-]*"
                    placeholder="000.000.000-00"
                    required
                    autofocus
                >
                <div class="kiosk-form-actions">
                    <button type="submit" class="kiosk-submit">Imprimir senha</button>
                    <a class="kiosk-back" href="<?= e($backUrl) ?>">← Voltar</a>
                </div>
            </form>
        </div>
    </div>
    <script src="<?= APP_URL ?>/js/kiosk.js" defer></script>
</body>
</html>
