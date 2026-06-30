<?php
/** @var int $selectedPatientId */
/** @var string $selectedPatientName */
/** @var string $inputId */
$selectedPatientId = (int) ($selectedPatientId ?? 0);
$selectedPatientName = (string) ($selectedPatientName ?? '');
$inputId = (string) ($inputId ?? 'patient-picker');
?>
<div class="patient-picker" data-patient-picker data-app-url="<?= e(APP_URL) ?>" data-selected-label="<?= e($selectedPatientName) ?>">
    <label>Paciente</label>
    <input type="hidden" name="patient_id" data-patient-id value="<?= $selectedPatientId > 0 ? (string) $selectedPatientId : '' ?>" required>
    <input
        type="search"
        id="<?= e($inputId) ?>"
        data-patient-search
        placeholder="Digite nome, CPF ou telefone"
        autocomplete="off"
        value="<?= e($selectedPatientName) ?>"
    >
    <div class="patient-picker-results" data-patient-results hidden></div>
</div>
