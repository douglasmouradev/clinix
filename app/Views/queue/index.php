<div id="queue-flash" class="queue-flash" hidden></div>

<?php if (!empty($kioskUrl)): ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title">
            <h3>Totem de autoatendimento</h3>
            <span class="pill">Senhas P / A / B</span>
        </div>
        <p class="muted">Abra no tablet em tela cheia. Prioritário (P), agendado com CPF (A) ou sem agendamento (B).</p>
        <div class="queue-kiosk-url-row">
            <input id="queue-kiosk-url" readonly value="<?= e($kioskUrl) ?>">
            <button type="button" class="btn secondary small" id="queue-kiosk-copy">Copiar URL</button>
            <a class="btn secondary small" style="width:auto;" href="<?= e($kioskUrl) ?>" target="_blank" rel="noopener">Abrir totem</a>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <?php if (in_array($role, ['admin', 'reception'], true)): ?>
        <div class="card">
            <h3>Gerar senha de atendimento</h3>
            <form class="queue-ajax-form" method="post" action="<?= APP_URL ?>/?route=queue.generate" data-queue-action="generate">
                <?= csrfInput() ?>
                <label>Paciente</label>
                <select name="patient_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= (int) $patient['id'] ?>"><?= e($patient['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="queue-field-label">Sala prevista</label>
                <input name="room" placeholder="Ex.: Triagem 1 ou Consultorio 2">
                <div class="queue-print-option">
                    <input type="checkbox" id="queue-auto-print" checked>
                    <label for="queue-auto-print">Imprimir senha automaticamente</label>
                </div>
                <div class="queue-form-actions">
                    <button type="submit">Gerar senha</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'nurse', 'doctor'], true)): ?>
        <div class="card">
            <h3>Chamar paciente</h3>
            <form class="queue-ajax-form" method="post" action="<?= APP_URL ?>/?route=queue.call" data-queue-action="call">
                <?= csrfInput() ?>
                <label>Senha</label>
                <select name="ticket_id" required id="queue-call-select">
                    <option value="">Selecione</option>
                    <?php foreach ($queue as $ticket): ?>
                        <?php if ($ticket['status'] === 'waiting'): ?>
                            <option value="<?= (int) $ticket['id'] ?>" data-room="<?= e($ticket['room'] ?: ($role === 'nurse' ? 'Triagem 1' : 'Consultorio 1')) ?>">
                                #<?= e($ticket['ticket_number']) ?> - <?= e($ticket['full_name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <label class="queue-field-label">Sala ou profissional</label>
                <input name="room" id="queue-call-room" value="<?= $role === 'nurse' ? 'Triagem 1' : 'Consultorio 1' ?>">
                <div class="queue-form-actions">
                    <button type="submit">Chamar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">
        <h3>Fila atual</h3>
        <span class="pill" id="queue-count-pill"><?= count($queue) ?> na fila</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Senha</th><th>Paciente</th><th>Status</th><th>Destino</th><th>Ações</th></tr>
            </thead>
            <tbody id="queue-table-body">
            <?php include __DIR__ . '/_table_rows.php'; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .queue-flash {
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        font-weight: 600;
    }
    .queue-flash.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .queue-flash.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .queue-row-calling { opacity: .55; pointer-events: none; }
    .queue-field-label { margin-top: 10px; }
    .queue-print-option {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
        padding: 10px 12px;
        background: #f8fbfc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .queue-print-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    .queue-print-option label {
        display: inline;
        margin: 0;
        font-weight: 500;
        line-height: 1.4;
        cursor: pointer;
    }
    .queue-form-actions {
        margin-top: 12px;
    }
    .queue-form-actions button {
        width: 100%;
    }
    .queue-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .queue-actions .btn,
    .queue-actions button {
        width: auto;
        margin: 0;
    }
    .grid.grid-2 > .card {
        min-width: 0;
    }
    .queue-kiosk-url-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: stretch;
        margin-top: 10px;
    }
    .queue-kiosk-url-row input {
        flex: 1;
        min-width: 220px;
    }
</style>
<script>
    (function () {
        var btn = document.getElementById('queue-kiosk-copy');
        var input = document.getElementById('queue-kiosk-url');
        if (!btn || !input) {
            return;
        }
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(input.value).then(function () {
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar URL'; }, 2000);
            }).catch(function () {
                input.select();
                document.execCommand('copy');
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar URL'; }, 2000);
            });
        });
    })();
</script>
<script>
    window.CLINIX_QUEUE = {
        appUrl: <?= json_encode(APP_URL, JSON_UNESCAPED_UNICODE) ?>,
        csrfToken: <?= json_encode($csrfToken ?? csrfToken(), JSON_UNESCAPED_UNICODE) ?>,
        role: <?= json_encode($role, JSON_UNESCAPED_UNICODE) ?>,
        clinicName: <?= json_encode($clinicName ?? APP_NAME, JSON_UNESCAPED_UNICODE) ?>,
        canCall: <?= json_encode(in_array($role, ['admin', 'reception', 'nurse', 'doctor'], true)) ?>,
        canDone: <?= json_encode(in_array($role, ['admin', 'nurse', 'doctor'], true)) ?>,
        canPrint: <?= json_encode(in_array($role, ['admin', 'reception'], true)) ?>,
        defaultRoom: <?= json_encode($role === 'reception' ? 'Recepção' : ($role === 'nurse' ? 'Triagem' : 'Triagem'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= APP_URL ?>/js/queue-manage.js?v=2"></script>
