<div class="card soft">
    <div class="card-title">
        <h2>Tokens de API</h2>
        <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=admin.users">Voltar</a>
    </div>
    <p class="muted">Use o header <code>X-Api-Token</code> nas rotas <code>api.v1.*</code></p>
</div>

<?php if (!empty($createdToken)): ?>
    <div class="info">
        <strong>Token criado (copie agora):</strong>
        <code style="word-break:break-all;"><?= e($createdToken) ?></code>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post" action="<?= APP_URL ?>/?route=admin.api.create">
        <?= csrfInput() ?>
        <label>Nome do token</label>
        <input name="name" required placeholder="Ex.: Integração laboratório">
        <button style="margin-top:10px;">Gerar token</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nome</th><th>Status</th><th>Criado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($tokens as $token): ?>
                <tr>
                    <td><?= e($token['name']) ?></td>
                    <td><?= (int) $token['is_active'] === 1 ? 'Ativo' : 'Revogado' ?></td>
                    <td><?= e(formatDateTimeBr($token['created_at'])) ?></td>
                    <td>
                        <?php if ((int) $token['is_active'] === 1): ?>
                            <form method="post" action="<?= APP_URL ?>/?route=admin.api.revoke">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $token['id'] ?>">
                                <button class="btn secondary small" style="width:auto;">Revogar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
