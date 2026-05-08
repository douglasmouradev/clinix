<div class="grid grid-2">
    <?php if (in_array($role, ['admin', 'reception'], true)): ?>
        <div class="card">
            <h3>Gerar senha de atendimento</h3>
            <form method="post" action="<?= APP_URL ?>/?route=queue.generate">
                <?= csrfInput() ?>
                <label>Paciente</label>
                <select name="patient_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= (int) $patient['id'] ?>"><?= e($patient['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label style="margin-top:10px;">Sala prevista</label>
                <input name="room" placeholder="Ex.: Triagem 1 ou Consultorio 2">
                <div style="margin-top:10px;">
                    <button>Gerar senha</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'nurse', 'doctor'], true)): ?>
        <div class="card">
            <h3>Chamar paciente</h3>
            <form method="post" action="<?= APP_URL ?>/?route=queue.call">
                <?= csrfInput() ?>
                <label>Senha</label>
                <select name="ticket_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($queue as $ticket): ?>
                        <option value="<?= (int) $ticket['id'] ?>">#<?= e($ticket['ticket_number']) ?> - <?= e($ticket['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label style="margin-top:10px;">Sala ou profissional</label>
                <input name="room" value="<?= $role === 'nurse' ? 'Triagem 1' : 'Consultorio 1' ?>">
                <div style="margin-top:10px;">
                    <button>Chamar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">
        <h3>Fila atual</h3>
        <span class="pill"><?= count($queue) ?> na fila</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Senha</th><th>Paciente</th><th>Status</th><th>Destino</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php foreach ($queue as $ticket): ?>
                <tr>
                    <td>#<?= e($ticket['ticket_number']) ?></td>
                    <td><?= e($ticket['full_name']) ?></td>
                    <td><?= e($ticket['status']) ?></td>
                    <td><?= e($ticket['room'] ?? '-') ?></td>
                    <td>
                        <?php if (in_array($role, ['admin', 'reception', 'nurse', 'doctor'], true) && $ticket['status'] === 'waiting'): ?>
                            <form method="post" action="<?= APP_URL ?>/?route=queue.call" style="margin-bottom:6px;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                                <input type="hidden" name="room" value="<?= e($ticket['room'] ?: ($role === 'reception' ? 'Recepção' : 'Triagem')) ?>">
                                <button class="btn small" style="width:auto;">Chamar senha</button>
                            </form>
                        <?php endif; ?>
                        <?php if (in_array($role, ['admin', 'nurse', 'doctor'], true) && $ticket['status'] === 'called'): ?>
                            <form method="post" action="<?= APP_URL ?>/?route=queue.done">
                                <?= csrfInput() ?>
                                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                                <button class="btn small" style="width:auto;">Finalizar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

