<?php
$roles = ['admin', 'reception', 'nurse', 'doctor'];
?>

<div class="card">
    <div class="card-title">
        <div>
            <h2><?= !empty($editUser['id']) ? 'Editar usuário' : 'Novo usuário' ?></h2>
            <p class="muted">Defina o perfil e credenciais de acesso do colaborador.</p>
        </div>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=admin.users">Voltar</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= APP_URL ?>/?route=admin.user.save">
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= (int) ($editUser['id'] ?? 0) ?>">

        <div class="grid grid-2">
            <div>
                <label>Nome completo</label>
                <input type="text" name="name" required value="<?= e($editUser['name'] ?? '') ?>">
            </div>
            <div>
                <label>Usuário</label>
                <input type="text" name="username" required value="<?= e($editUser['username'] ?? '') ?>">
            </div>
            <div>
                <label>Perfil</label>
                <select name="role" required>
                    <option value="">Selecione</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= (($editUser['role'] ?? '') === $role) ? 'selected' : '' ?>>
                            <?= e(roleLabel($role)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Senha <?= !empty($editUser['id']) ? '(deixe em branco para manter)' : '' ?></label>
                <input type="password" name="password" <?= empty($editUser['id']) ? 'required' : '' ?>>
            </div>
            <div>
                <label>Status da conta</label>
                <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                    <input type="checkbox" name="is_active" value="1" <?= ((int) ($editUser['is_active'] ?? 1) === 1) ? 'checked' : '' ?> style="width:auto;">
                    Conta ativa
                </label>
            </div>
        </div>

        <div style="margin-top:14px;">
            <button>Salvar usuário</button>
        </div>
    </form>
</div>

