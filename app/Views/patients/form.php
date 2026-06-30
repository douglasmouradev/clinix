<div class="card">
    <div class="card-title">
        <div>
            <h2><?= !empty($patient['id']) ? 'Editar paciente' : 'Novo paciente' ?></h2>
            <p class="muted">Preencha os dados cadastrais obrigatorios para atendimento.</p>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= APP_URL ?>/?route=patient.save" enctype="multipart/form-data">
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= (int) ($patient['id'] ?? 0) ?>">
        <div class="grid grid-2">
            <div>
                <label>Nome completo</label>
                <input name="full_name" required value="<?= e($patient['full_name'] ?? '') ?>">
            </div>
            <div>
                <label>CPF (somente numeros)</label>
                <input name="cpf" id="cpf-input" required maxlength="14" value="<?= e(\App\Core\CpfValidator::format((string) ($patient['cpf'] ?? ''))) ?>" placeholder="000.000.000-00">
                <script src="<?= APP_URL ?>/js/cpf-mask.js"></script>
                <small class="muted">Use apenas os 11 digitos, sem pontuacao.</small>
            </div>
            <div>
                <label>Data de nascimento</label>
                <input type="date" name="birth_date" required value="<?= e($patient['birth_date'] ?? '') ?>">
            </div>
            <div>
                <label>Sexo</label>
                <select name="sex" required>
                    <option value="">Selecione</option>
                    <?php foreach (['Feminino', 'Masculino', 'Outro'] as $sex): ?>
                        <option value="<?= e($sex) ?>" <?= (($patient['sex'] ?? '') === $sex) ? 'selected' : '' ?>><?= e($sex) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Telefone</label>
                <input name="phone" value="<?= e($patient['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>
        </div>

        <div class="address-block" style="margin-top:10px;">
            <h3 class="address-block-title">Endereço</h3>
            <div class="grid grid-2">
                <div>
                    <label>CEP</label>
                    <input
                        type="text"
                        name="cep"
                        id="cep-input"
                        inputmode="numeric"
                        maxlength="9"
                        value="<?= e(formatCep($patient['cep'] ?? '')) ?>"
                        placeholder="00000-000"
                    >
                    <small id="cep-status" class="cep-status" aria-live="polite"></small>
                </div>
                <div>
                    <label>Logradouro</label>
                    <input name="address_street" id="address-street" value="<?= e($patient['address_street'] ?? ($patient['address'] ?? '')) ?>" placeholder="Rua, avenida...">
                </div>
                <div>
                    <label>Número</label>
                    <input name="address_number" id="address-number" value="<?= e($patient['address_number'] ?? '') ?>" placeholder="Ex.: 36">
                </div>
                <div>
                    <label>Complemento</label>
                    <input name="address_complement" id="address-complement" value="<?= e($patient['address_complement'] ?? '') ?>" placeholder="Apto, bloco...">
                </div>
                <div>
                    <label>Bairro</label>
                    <input name="address_neighborhood" id="address-neighborhood" value="<?= e($patient['address_neighborhood'] ?? '') ?>">
                </div>
                <div>
                    <label>Cidade</label>
                    <input name="address_city" id="address-city" value="<?= e($patient['address_city'] ?? '') ?>">
                </div>
                <div>
                    <label>UF</label>
                    <input name="address_state" id="address-state" maxlength="2" value="<?= e($patient['address_state'] ?? '') ?>" placeholder="RJ">
                </div>
            </div>
        </div>
        <script src="<?= APP_URL ?>/js/cep-autofill.js?v=1"></script>

        <div style="margin-top:10px;">
            <label>Histórico médico basico</label>
            <textarea name="medical_history"><?= e($patient['medical_history'] ?? '') ?></textarea>
        </div>
        <div style="margin-top:10px;">
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="lgpd_consent" value="1" <?= !empty($patient['lgpd_consent_at']) ? 'checked' : '' ?> style="width:auto;">
                Paciente autorizou tratamento de dados (LGPD v1.0)
            </label>
            <?php if (!empty($patient['lgpd_consent_at'])): ?>
                <small class="muted">Consentimento registrado em <?= e(formatDateTimeBr($patient['lgpd_consent_at'])) ?></small>
            <?php endif; ?>
        </div>
        <div style="margin-top:10px;">
            <label>Anexar documento do paciente (PDF, JPG, PNG, DOC, DOCX ate 5MB)</label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            <small class="muted">Arquivos de identificacao, guias e laudos podem ser anexados aqui.</small>
        </div>
        <?php if (!empty($documents)): ?>
            <div style="margin-top:10px;">
                <label>Documentos anexados</label>
                <?php foreach ($documents as $doc): ?>
                    <div class="actions" style="margin-bottom:6px;">
                        <a class="link" href="<?= APP_URL ?>/?route=patient.document&id=<?= (int) $doc['id'] ?>"><?= e($doc['original_name']) ?></a>
                        <small class="muted"><?= e(formatDateTimeBr($doc['created_at'])) ?> (<?= e(number_format(((int) $doc['file_size']) / 1024, 1, ',', '.')) ?> KB)</small>
                        <form method="post" action="<?= APP_URL ?>/?route=patient.document.delete">
                            <?= csrfInput() ?>
                            <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
                            <input type="hidden" name="patient_id" value="<?= (int) ($patient['id'] ?? 0) ?>">
                            <button class="btn secondary small" style="width:auto;">Excluir</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="margin-top:12px;">
            <button>Salvar paciente</button>
        </div>
    </form>
</div>

