<div class="card">
    <div class="card-title">
        <div>
            <h2>Agendar retorno</h2>
            <p class="muted">Criar consulta na agenda a partir do retorno de <strong><?= e($returnVisit['patient_name']) ?></strong>.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=returns">Voltar</a>
    </div>

    <div class="card soft" style="margin-bottom:14px;">
        <p><strong>Previsão:</strong> <?= e(formatDateBr($returnVisit['return_due_date'])) ?></p>
        <p><strong>Motivo:</strong> <?= e($returnVisit['reason'] ?? 'Retorno') ?></p>
        <?php if (!empty($returnVisit['professional_name'])): ?>
            <p><strong>Profissional:</strong> <?= e($returnVisit['professional_name']) ?></p>
        <?php endif; ?>
        <?php if (!empty($returnVisit['notes'])): ?>
            <p><strong>Observações:</strong> <?= e($returnVisit['notes']) ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= APP_URL ?>/?route=return.schedule">
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= (int) $returnVisit['id'] ?>">
        <div class="grid grid-2">
            <div>
                <label>Data e hora da consulta</label>
                <input
                    type="datetime-local"
                    name="scheduled_at"
                    required
                    value="<?= e(date('Y-m-d\TH:i', strtotime((string) $returnVisit['return_due_date'] . ' 09:00'))) ?>"
                >
            </div>
        </div>
        <div style="margin-top:12px;">
            <button>Confirmar agendamento</button>
        </div>
    </form>
</div>
