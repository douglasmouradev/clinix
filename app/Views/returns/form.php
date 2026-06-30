<div class="card">
    <div class="card-title">
        <div>
            <h2><?= !empty($returnVisit['id']) ? 'Editar retorno' : 'Novo retorno' ?></h2>
            <p class="muted">Registre a previsão de retorno do paciente após consulta ou procedimento.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=returns">Voltar</a>
    </div>

    <form method="post" action="<?= APP_URL ?>/?route=return.save">
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= (int) ($returnVisit['id'] ?? 0) ?>">
        <?php if (!empty($returnVisit['source_appointment_id'])): ?>
            <input type="hidden" name="source_appointment_id" value="<?= (int) $returnVisit['source_appointment_id'] ?>">
        <?php endif; ?>
        <div class="grid grid-2">
            <div>
                <?php
                $selectedPatientId = (int) ($returnVisit['patient_id'] ?? 0);
                $selectedPatientName = (string) ($selectedPatientName ?? '');
                $inputId = 'return-patient-picker';
                include __DIR__ . '/../partials/patient_picker.php';
                ?>
            </div>
            <div>
                <label>Profissional</label>
                <select name="professional_id">
                    <option value="">Não definido</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= (int) $doctor['id'] ?>" <?= ((int) ($returnVisit['professional_id'] ?? 0) === (int) $doctor['id']) ? 'selected' : '' ?>>
                            <?= e($doctor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Data prevista do retorno</label>
                <input type="date" name="return_due_date" required value="<?= e($returnVisit['return_due_date'] ?? '') ?>">
            </div>
            <div>
                <label>Motivo</label>
                <input name="reason" value="<?= e($returnVisit['reason'] ?? 'Retorno') ?>" placeholder="Ex.: Retorno pós-cirurgia">
            </div>
        </div>
        <div style="margin-top:10px;">
            <label>Observações internas</label>
            <textarea name="notes" placeholder="Instruções para recepção ou equipe clínica"><?= e($returnVisit['notes'] ?? '') ?></textarea>
        </div>
        <div style="margin-top:12px;">
            <button>Salvar retorno</button>
        </div>
    </form>
</div>
<script src="<?= APP_URL ?>/js/patient-picker.js?v=1"></script>
