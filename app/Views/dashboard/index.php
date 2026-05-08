<?php $user = $user ?? ['name' => '', 'role' => '']; ?>

<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Painel Principal</h2>
            <p>Bem-vindo, <?= e($user['name']) ?>.</p>
        </div>
        <span class="pill"><?= e(roleLabel($user['role'])) ?></span>
    </div>
    <div class="stats">
        <div class="stat">
            <strong>Prontuário Unificado</strong>
            <p class="muted">Histórico clinico centralizado por paciente.</p>
        </div>
        <div class="stat">
            <strong>Fila em Tempo Real</strong>
            <p class="muted">Controle de chamados e fluxo de atendimento.</p>
        </div>
        <div class="stat">
            <strong>Controle por Perfil</strong>
            <p class="muted">Permissoes alinhadas a cada funcao da clínica.</p>
        </div>
        <div class="stat">
            <strong>Confianca e Compliance</strong>
            <p class="muted">LGPD, trilha de auditoria e retenção governada por tenant.</p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-title">
            <h3>Ações rapidas</h3>
        </div>
        <div class="actions">
            <a class="btn small" href="<?= APP_URL ?>/?route=patients">Ver pacientes</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=appointments">Abrir agenda</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=queue">Abrir fila</a>
        </div>
    </div>
    <div class="card">
        <div class="card-title">
            <h3>Produtividade da equipe</h3>
        </div>
        <p class="muted">Use a barra lateral para navegar rapido entre pacientes, histórico e fila de atendimento.</p>
    </div>
</div>

<?php if ($user['role'] === 'admin'): ?>
    <div class="card">
        <h3>Administrador</h3>
        <p>Gerencie usuários, perfis, compliance LGPD e indicadores executivos em um unico painel.</p>
        <div class="actions">
            <a class="btn small" href="<?= APP_URL ?>/?route=admin.users">Abrir administração</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=reports.executive">Ver relatórios</a>
        </div>
    </div>
<?php elseif ($user['role'] === 'reception'): ?>
    <div class="card">
        <h3>Recepção</h3>
        <p>Cadastre pacientes, gere senhas e acompanhe a fila.</p>
    </div>
<?php elseif ($user['role'] === 'nurse'): ?>
    <div class="card">
        <h3>Enfermagem</h3>
        <p>Realize triagem e registre pre-atendimento no prontuário compartilhado.</p>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Médico</h3>
        <p>Acesse histórico completo, registre consulta, diagnóstico e prescrição.</p>
    </div>
<?php endif; ?>

