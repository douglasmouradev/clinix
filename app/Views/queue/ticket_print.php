<?php
/** @var array<string, mixed> $ticketData */
/** @var string $clinicName */
/** @var string $ticketKind */
$ticketKind = $ticketKind ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Senha #<?= e((string) $ticketData['ticket_number']) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/queue-ticket-print.css">
</head>
<body class="ticket-print-body" onload="window.print()">
    <article class="ticket-slip">
        <p class="ticket-clinic"><?= e($clinicName) ?></p>
        <p class="ticket-label">Senha de atendimento</p>
        <?php if ($ticketKind !== ''): ?>
            <p class="ticket-kind"><?= e($ticketKind) ?></p>
        <?php endif; ?>
        <p class="ticket-number">#<?= e((string) $ticketData['ticket_number']) ?></p>
        <p class="ticket-patient"><?= e((string) $ticketData['full_name']) ?></p>
        <?php if ((string) ($ticketData['room'] ?? '') !== ''): ?>
            <p class="ticket-room">Destino: <?= e((string) $ticketData['room']) ?></p>
        <?php endif; ?>
        <p class="ticket-date"><?= e((string) ($ticketData['created_label'] ?? '')) ?></p>
        <p class="ticket-hint">Aguarde ser chamado no painel</p>
    </article>
    <script>
        window.onafterprint = function () {
            var params = new URLSearchParams(window.location.search);
            var tenant = params.get('tenant') || '';
            var token = params.get('token') || '';
            var back = '<?= e(APP_URL) ?>/?route=queue.kiosk&token=' + encodeURIComponent(token);
            if (tenant) {
                back += '&tenant=' + encodeURIComponent(tenant);
            }
            window.location.replace(back);
        };
    </script>
</body>
</html>
