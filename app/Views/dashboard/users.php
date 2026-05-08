<div class="card soft">
    <div class="card-title">
        <div>
            <h2>Administração de Usuários</h2>
            <p class="muted">Gerencie perfis de acesso da clínica.</p>
        </div>
        <div class="actions">
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=admin.clinic">Slug da clínica</a>
            <a class="btn secondary small" href="<?= APP_URL ?>/?route=admin.panel">Token do painel</a>
            <a class="btn small" href="<?= APP_URL ?>/?route=admin.user.form">Novo usuário</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Nome</th>
                <th>Usuário</th>
                <th>Perfil</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $listedUser): ?>
                <tr>
                    <td><?= e($listedUser['name']) ?></td>
                    <td><?= e($listedUser['username']) ?></td>
                    <td><span class="pill"><?= e(roleLabel($listedUser['role'])) ?></span></td>
                    <td>
                        <span class="pill"><?= (int) $listedUser['is_active'] === 1 ? 'Ativo' : 'Inativo' ?></span>
                    </td>
                    <td><?= e($listedUser['created_at']) ?></td>
                    <td>
                        <a class="link" href="<?= APP_URL ?>/?route=admin.user.form&id=<?= (int) $listedUser['id'] ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

