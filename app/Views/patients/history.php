<?php
$patient = $patient ?? [];
$timeline = $timeline ?? [];
$documentsByRecord = $documentsByRecord ?? [];
$filters = $filters ?? [];
$canViewClinicalContent = $canViewClinicalContent ?? false;
$role = $role ?? '';
$lgpdRequests = $lgpdRequests ?? [];
$consents = $consents ?? [];
?>

<div class="card">
    <div class="card-title">
        <div>
            <h2>Histórico do Paciente</h2>
            <p class="muted">Linha do tempo de atendimentos e registros por profissional.</p>
        </div>
        <div class="actions">
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=patients">Voltar</a>
            <a class="btn small" href="<?= APP_URL ?>/?route=patient.history.report&id=<?= (int) $patient['id'] ?>&entry_type=<?= urlencode((string) ($filters['entry_type'] ?? '')) ?>&date_from=<?= urlencode((string) ($filters['date_from'] ?? '')) ?>&date_to=<?= urlencode((string) ($filters['date_to'] ?? '')) ?>">Exportar CSV</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <strong><?= e($patient['full_name']) ?></strong>
            <p class="muted">Paciente</p>
        </div>
        <div class="stat">
            <strong><?= e($patient['cpf']) ?></strong>
            <p class="muted">CPF</p>
        </div>
        <div class="stat">
            <strong><?= e(formatDateBr($patient['birth_date'])) ?></strong>
            <p class="muted">Nascimento</p>
        </div>
        <div class="stat">
            <strong><?= e($patient['phone'] ?: '-') ?></strong>
            <p class="muted">Contato</p>
        </div>
    </div>
</div>

<?php if (($role ?? '') === 'admin'): ?>
<div class="card">
    <h3>LGPD</h3>
    <div class="actions" style="margin-bottom:10px;">
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=patient.lgpd.export&id=<?= (int) ($patient['id'] ?? 0) ?>">Exportar dados do paciente</a>
        <form method="post" action="<?= APP_URL ?>/?route=patient.lgpd.anonymize" onsubmit="return confirm('Confirmar anonimizar dados do paciente?');">
            <?= csrfInput() ?>
            <input type="hidden" name="id" value="<?= (int) ($patient['id'] ?? 0) ?>">
            <button class="btn small" style="width:auto;">Anonimizar dados</button>
        </form>
    </div>
    <?php if (!empty($lgpdRequests)): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Acao</th><th>Executor</th><th>Data</th><th>Detalhe</th></tr></thead>
                <tbody>
                <?php foreach ($lgpdRequests as $request): ?>
                    <tr>
                        <td><?= e($request['request_type']) ?></td>
                        <td><?= e($request['requested_by_name']) ?></td>
                        <td><?= e(formatDateTimeBr($request['created_at'])) ?></td>
                        <td><?= e($request['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!empty($consents)): ?>
        <h3 style="margin-top:14px;">Histórico de consentimentos</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Versao do termo</th><th>Coletado por</th><th>Data</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($consents as $consent): ?>
                    <tr>
                        <td><?= e($consent['term_version']) ?></td>
                        <td><?= e($consent['collected_by_name'] ?: 'Sistema') ?></td>
                        <td><?= e(formatDateTimeBr($consent['consented_at'])) ?></td>
                        <td><?= e($consent['ip_address'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <h3>Evolução clínica</h3>
    <form method="get" action="<?= APP_URL ?>" style="margin:12px 0 14px;">
        <input type="hidden" name="route" value="patient.history">
        <input type="hidden" name="id" value="<?= (int) $patient['id'] ?>">
        <div class="grid grid-2">
            <div>
                <label>Tipo de registro</label>
                <select name="entry_type">
                    <option value="">Todos</option>
                    <?php foreach (['triage', 'consultation', 'diagnosis', 'prescription', 'medical_note'] as $entryType): ?>
                        <option value="<?= e($entryType) ?>" <?= (($filters['entry_type'] ?? '') === $entryType) ? 'selected' : '' ?>>
                            <?= e($entryType) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Data inicial</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div>
                <label>Data final</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
                <button class="btn small" style="width:auto;">Filtrar</button>
                <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=patient.history&id=<?= (int) $patient['id'] ?>">Limpar</a>
            </div>
        </div>
    </form>

    <?php if (!$canViewClinicalContent): ?>
        <div class="info">
            Como perfil de recepção, voce visualiza o histórico de atendimentos sem o conteudo clinico detalhado.
        </div>
    <?php endif; ?>

    <?php if (empty($timeline)): ?>
        <p class="muted">Nenhum registro encontrado para este paciente.</p>
    <?php else: ?>
        <?php foreach ($timeline as $item): ?>
            <div class="timeline-item">
                <div class="row">
                    <strong style="text-transform: capitalize;"><?= e($item['entry_type']) ?></strong>
                    <span class="pill"><?= e(formatDateTimeBr($item['created_at'])) ?></span>
                </div>
                <p style="margin-top:8px;white-space:pre-wrap;">
                    <?php if ($canViewClinicalContent): ?>
                        <?= e($item['content']) ?>
                    <?php else: ?>
                        Conteudo clinico restrito. Registro feito por <?= e($item['professional_name']) ?> (<?= e(roleLabel($item['role'])) ?>).
                    <?php endif; ?>
                </p>
                <?php $recordDocs = $documentsByRecord[(int) $item['id']] ?? []; ?>
                <?php if (!empty($recordDocs)): ?>
                    <div style="margin:8px 0;">
                        <strong>Anexos:</strong>
                        <?php foreach ($recordDocs as $doc): ?>
                            <div><a class="link" href="<?= APP_URL ?>/?route=record.document&id=<?= (int) $doc['id'] ?>"><?= e($doc['original_name']) ?></a> <small class="muted">(<?= e(number_format(((int) $doc['file_size']) / 1024, 1, ',', '.')) ?> KB)</small></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <small class="muted">Responsavel: <?= e($item['professional_name']) ?> (<?= e(roleLabel($item['role'])) ?>)</small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (in_array($role, ['admin', 'nurse', 'doctor'], true)): ?>
    <div class="card">
        <div class="row">
            <div>
                <h3>Atalho para prontuário completo</h3>
                <p class="muted">Abra a tela clínica para registrar novos eventos.</p>
            </div>
            <a class="btn small" style="width:auto;" href="<?= APP_URL ?>/?route=record.show&patient_id=<?= (int) $patient['id'] ?>">Abrir prontuário</a>
        </div>
    </div>
<?php endif; ?>

