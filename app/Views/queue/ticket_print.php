<?php
/** @var array<string, mixed> $ticketData */
/** @var string $clinicName */
/** @var string $ticketKind */
/** @var string $appointmentTime */
/** @var string $printNotice */
/** @var bool $isKiosk */
$ticketKind = $ticketKind ?? '';
$appointmentTime = $appointmentTime ?? '';
$printNotice = $printNotice ?? '';
$isKiosk = !empty($isKiosk);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Senha #<?= e((string) $ticketData['ticket_number']) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/queue-ticket-print.css">
    <style media="print">
        @page { size: 57.5mm 95mm; margin: 0; }
    </style>
</head>
<body class="ticket-print-body<?= $isKiosk ? ' ticket-print-kiosk' : '' ?>" onload="window.print()">
    <?php if ($isKiosk): ?>
        <div class="ticket-print-success" id="ticket-print-success" aria-live="polite">Senha emitida com sucesso</div>
    <?php endif; ?>
    <article class="ticket-slip">
        <p class="ticket-clinic"><?= e($clinicName) ?></p>
        <p class="ticket-label">Senha de atendimento</p>
        <?php if ($ticketKind !== ''): ?>
            <p class="ticket-kind"><?= e($ticketKind) ?></p>
        <?php endif; ?>
        <p class="ticket-number">#<?= e((string) $ticketData['ticket_number']) ?></p>
        <p class="ticket-patient"><?= e((string) $ticketData['full_name']) ?></p>
        <?php if ($appointmentTime !== ''): ?>
            <p class="ticket-appointment">Horário agendado: <?= e($appointmentTime) ?></p>
        <?php endif; ?>
        <?php if ((string) ($ticketData['room'] ?? '') !== '' && !in_array((string) $ticketData['room'], ['Prioritário', 'Agendado', 'Sem agendamento'], true)): ?>
            <p class="ticket-room">Destino: <?= e((string) $ticketData['room']) ?></p>
        <?php endif; ?>
        <p class="ticket-date"><?= e((string) ($ticketData['created_label'] ?? '')) ?></p>
        <?php if ($printNotice !== ''): ?>
            <p class="ticket-hint"><?= e($printNotice) ?></p>
        <?php else: ?>
            <p class="ticket-hint">Aguarde ser chamado no painel</p>
        <?php endif; ?>
    </article>
    <div class="ticket-cut-feed" aria-hidden="true"></div>
    <script>
        (function () {
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 660;
                gain.gain.value = 0.12;
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch (e) {}

            window.onafterprint = function () {
                <?php if ($isKiosk): ?>
                var params = new URLSearchParams(window.location.search);
                var tenant = params.get('tenant') || '';
                var token = params.get('token') || '';
                var back = '<?= e(APP_URL) ?>/?route=queue.kiosk&token=' + encodeURIComponent(token);
                if (tenant) {
                    back += '&tenant=' + encodeURIComponent(tenant);
                }
                window.location.replace(back);
                <?php else: ?>
                window.close();
                <?php endif; ?>
            };
        })();
    </script>
</body>
</html>
