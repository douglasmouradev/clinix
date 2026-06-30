<div class="card actions">
    <div>
        <h2>Pacientes</h2>
        <p class="muted">Cadastro e consulta de pacientes da clínica.</p>
    </div>
    <?php if (in_array((\App\Core\Auth::user()['role'] ?? ''), ['admin', 'reception'], true)): ?>
        <a href="<?= APP_URL ?>/?route=patient.form" style="text-decoration:none;"><button class="btn small">Novo paciente</button></a>
    <?php endif; ?>
</div>

<div class="card">
    <form method="get" action="<?= APP_URL ?>">
        <input type="hidden" name="route" value="patients">
        <div class="grid grid-2">
            <div>
                <label>Buscar por nome, CPF ou telefone</label>
                <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Ex.: Maria ou 12345678901">
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
                <button class="btn small" style="width:auto;">Pesquisar</button>
                <a class="btn secondary small" style="width:auto;" href="<?= APP_URL ?>/?route=patients">Limpar</a>
            </div>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Nome</th>
            <th>CPF</th>
            <th>Nascimento</th>
            <th>Telefone</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($patients)): ?>
            <tr>
                <td colspan="5" class="empty-state-cell">
                    <p class="empty-state-title">Nenhum paciente encontrado</p>
                    <p class="empty-state-hint">
                        <?php if (!empty($search)): ?>
                            Tente outro termo de busca ou <a href="<?= APP_URL ?>/?route=patients">limpe o filtro</a>.
                        <?php else: ?>
                            <a href="<?= APP_URL ?>/?route=patient.form">Cadastre o primeiro paciente</a>.
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        <?php endif; ?>
        <?php foreach ($patients as $patient): ?>
            <tr>
                <td><?= e($patient['full_name']) ?></td>
                <td><?= e($patient['cpf']) ?></td>
                <td><?= e(formatDateBr($patient['birth_date'])) ?></td>
                <td><?= e($patient['phone']) ?></td>
                <td class="actions">
                    <a class="link" href="<?= APP_URL ?>/?route=patient.history&id=<?= (int) $patient['id'] ?>">Histórico</a>
                    <?php if (in_array((\App\Core\Auth::user()['role'] ?? ''), ['admin', 'reception'], true)): ?>
                        <a class="link" href="<?= APP_URL ?>/?route=patient.form&id=<?= (int) $patient['id'] ?>">Editar</a>
                    <?php endif; ?>
                    <?php if (in_array((\App\Core\Auth::user()['role'] ?? ''), ['admin', 'nurse', 'doctor'], true)): ?>
                        <a class="link" href="<?= APP_URL ?>/?route=record.show&patient_id=<?= (int) $patient['id'] ?>">Prontuário</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$pagination = $pagination ?? null;
if (is_array($pagination) && ($pagination['total'] ?? 0) > ($pagination['per_page'] ?? 25)):
    $totalPages = (int) ceil($pagination['total'] / $pagination['per_page']);
    $currentPage = (int) ($pagination['page'] ?? 1);
    $queryBase = APP_URL . '/?route=patients' . ($search !== '' ? '&q=' . rawurlencode((string) $search) : '');
?>
<div class="card" style="margin-top:12px;">
    <div class="actions" style="justify-content:space-between;">
        <span class="muted">
            <?= (int) $pagination['total'] ?> paciente(s) — página <?= $currentPage ?> de <?= $totalPages ?>
        </span>
        <div class="actions">
            <?php if ($currentPage > 1): ?>
                <a class="btn secondary small" href="<?= $queryBase ?>&page=<?= $currentPage - 1 ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a class="btn secondary small" href="<?= $queryBase ?>&page=<?= $currentPage + 1 ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

