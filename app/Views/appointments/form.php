<?php $statusOptions = ['scheduled' => 'Agendado', 'checked_in' => 'Check-in', 'in_progress' => 'Em atendimento', 'completed' => 'Concluido', 'cancelled' => 'Cancelado']; ?>

<div class="card">
    <div class="card-title">
        <div>
            <h2><?= !empty($appointment['id']) ? 'Editar agendamento' : 'Novo agendamento' ?></h2>
            <p class="muted">Defina paciente, profissional, horario e status do atendimento.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=appointments">Voltar</a>
    </div>

    <form method="post" action="<?= APP_URL ?>/?route=appointment.save">
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= (int) ($appointment['id'] ?? 0) ?>">
        <div class="grid grid-2">
            <div>
                <?php
                $selectedPatientId = (int) ($appointment['patient_id'] ?? 0);
                $selectedPatientName = (string) ($selectedPatientName ?? '');
                $inputId = 'appointment-patient-picker';
                include __DIR__ . '/../partials/patient_picker.php';
                ?>
            </div>
            <div>
                <label>Profissional</label>
                <select name="professional_id">
                    <option value="">Não definido</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= (int) $doctor['id'] ?>" <?= ((int) ($appointment['professional_id'] ?? 0) === (int) $doctor['id']) ? 'selected' : '' ?>>
                            <?= e($doctor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Data e hora</label>
                <input type="datetime-local" name="scheduled_at" required value="<?= !empty($appointment['scheduled_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $appointment['scheduled_at']))) : '' ?>">
            </div>
            <div>
                <label>Status</label>
                <select name="status" required>
                    <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= (($appointment['status'] ?? 'scheduled') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top:10px;">
            <label>Motivo</label>
            <input name="reason" value="<?= e($appointment['reason'] ?? '') ?>">
        </div>
        <div style="margin-top:10px;">
            <label>Observacoes internas</label>
            <textarea name="notes"><?= e($appointment['notes'] ?? '') ?></textarea>
        </div>
        <div style="margin-top:12px;">
            <button>Salvar agendamento</button>
        </div>
    </form>
</div>
<script src="<?= APP_URL ?>/js/patient-picker.js?v=1"></script>

