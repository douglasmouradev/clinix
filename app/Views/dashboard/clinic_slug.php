<?php
$tenant = $tenant ?? [];
$error = $error ?? '';
?>
<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Slug da clínica</h2>
            <p class="muted">Identificador usado no login (campo &quot;Clínica (slug)&quot;). Use apenas letras sem acento, números e hífens.</p>
        </div>
        <div class="actions">
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=admin.users">Usuários</a>
        </div>
    </div>
</div>

<div class="card">
    <p class="muted"><strong>Nome da clínica:</strong> <?= e((string) ($tenant['name'] ?? '')) ?></p>
    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= APP_URL ?>/?route=admin.clinic.save">
        <?= csrfInput() ?>
        <div>
            <label for="clinic_slug_field">Slug</label>
            <input id="clinic_slug_field" name="slug" required value="<?= e((string) ($tenant['slug'] ?? '')) ?>" placeholder="ex.: clinica-centro" autocomplete="off">
            <small class="muted">Exemplo de login: <?= e(APP_URL) ?>/?route=login com este slug no formulário.</small>
        </div>
        <div style="margin-top:14px;">
            <button class="btn small" style="width:auto;">Salvar slug</button>
        </div>
    </form>
</div>
