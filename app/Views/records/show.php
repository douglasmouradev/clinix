<div class="card">
    <div class="card-title">
        <div>
            <h2>Prontuário de <?= e($patient['full_name']) ?></h2>
            <p class="muted">Registro clinico compartilhado entre equipe assistencial.</p>
        </div>
    </div>
    <p><strong>CPF:</strong> <?= e($patient['cpf']) ?> | <strong>Nascimento:</strong> <?= e(formatDateBr($patient['birth_date'])) ?></p>
    <p><strong>Histórico base:</strong> <?= e($patient['medical_history']) ?></p>
</div>

<div class="card">
    <h3>Novo registro</h3>
    <form method="post" action="<?= APP_URL ?>/?route=record.add" enctype="multipart/form-data">
        <?= csrfInput() ?>
        <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
        <label>Tipo de registro</label>
        <select name="entry_type" required>
            <?php if ($role === 'nurse'): ?>
                <option value="triage">Pre-atendimento (sinais vitais e observacoes)</option>
            <?php else: ?>
                <option value="consultation">Consulta</option>
                <option value="diagnosis">Diagnóstico</option>
                <option value="prescription">Prescrição</option>
                <option value="medical_note">Observacao medica</option>
            <?php endif; ?>
        </select>
        <label style="margin-top:10px;">Conteudo</label>
        <textarea name="content" required placeholder="Descreva os dados clinicos de forma objetiva..."></textarea>
        <?php if ($role === 'nurse'): ?>
            <div class="grid grid-2" style="margin-top:10px;">
                <div><label>PA</label><input name="blood_pressure" placeholder="Ex.: 120x80"></div>
                <div><label>FC (bpm)</label><input name="heart_rate" placeholder="Ex.: 78"></div>
                <div><label>Temperatura (C)</label><input name="temperature" placeholder="Ex.: 36.7"></div>
                <div><label>SpO2 (%)</label><input name="spo2" placeholder="Ex.: 98"></div>
                <div><label>Glicemia (mg/dL)</label><input name="glucose" placeholder="Ex.: 102"></div>
                <div><label>Dor (0 a 10)</label><input name="pain_scale" placeholder="Ex.: 3"></div>
            </div>
        <?php endif; ?>
        <label style="margin-top:10px;">Anexar documento (PDF, JPG, PNG, DOC, DOCX ate 5MB)</label>
        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <div style="margin-top:10px;">
            <button>Salvar no prontuário</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Histórico clinico</h3>
    <?php foreach ($timeline as $item): ?>
        <div class="timeline-item">
            <strong style="text-transform:capitalize;"><?= e($item['entry_type']) ?></strong>
            <p style="margin:6px 0;white-space:pre-wrap;"><?= e($item['content']) ?></p>
            <?php if (!empty($item['structured_data'])): ?>
                <?php $triage = json_decode((string) $item['structured_data'], true) ?: []; ?>
                <?php if (!empty($triage)): ?>
                    <div class="info">
                        <strong>Triagem estruturada:</strong>
                        <?php foreach ($triage as $k => $v): ?>
                            <div><?= e($k) ?>: <?= e((string) $v) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php $recordDocs = $documentsByRecord[(int) $item['id']] ?? []; ?>
            <?php if (!empty($recordDocs)): ?>
                <div style="margin:8px 0;">
                    <strong>Anexos:</strong>
                    <?php foreach ($recordDocs as $doc): ?>
                        <div class="actions">
                            <a class="link" href="<?= APP_URL ?>/?route=record.document&id=<?= (int) $doc['id'] ?>"><?= e($doc['original_name']) ?></a>
                            <small class="muted"><?= e(formatDateTimeBr($doc['created_at'])) ?> (<?= e(number_format(((int) $doc['file_size']) / 1024, 1, ',', '.')) ?> KB)</small>
                            <form method="post" action="<?= APP_URL ?>/?route=record.document.delete">
                                <?= csrfInput() ?>
                                <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
                                <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
                                <button class="btn secondary small" style="width:auto;">Excluir</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <small class="muted"><?= e($item['professional_name']) ?> (<?= e(roleLabel($item['role'])) ?>) - <?= e(formatDateTimeBr($item['created_at'])) ?></small>
            <?php if (in_array($role, ['admin', 'doctor'], true)): ?>
                <form method="post" action="<?= APP_URL ?>/?route=record.amend" style="margin-top:10px;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="record_id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
                    <label>Retificar registro (gera versão no histórico)</label>
                    <textarea name="content" required><?= e($item['content']) ?></textarea>
                    <input name="change_reason" placeholder="Motivo da retificação" required style="margin-top:8px;">
                    <button class="btn small" style="width:auto;margin-top:8px;">Salvar retificação</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($timeline)): ?>
        <p>Nenhum registro ainda.</p>
    <?php endif; ?>
</div>

