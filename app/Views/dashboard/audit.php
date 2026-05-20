<div class="card soft">
    <div class="card-title"><h2>Auditoria do sistema</h2></div>
    <form method="get" class="row" style="margin-bottom:12px;">
        <input type="hidden" name="route" value="admin.audit">
        <div style="flex:1;">
            <label>Filtrar ação</label>
            <input name="action" value="<?= e($action ?? '') ?>" placeholder="ex.: auth.login">
        </div>
        <button class="btn small" style="width:auto;align-self:flex-end;">Filtrar</button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Detalhes</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e(formatDateTimeBr($log['created_at'])) ?></td>
                    <td><?= e($log['user_name'] ?? '-') ?></td>
                    <td><code><?= e($log['action']) ?></code></td>
                    <td><?= e($log['details']) ?></td>
                    <td><?= e($log['ip_address'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
