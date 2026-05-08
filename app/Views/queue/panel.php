<div class="card">
    <div class="card-title">
        <div>
            <h2>Painel de Chamada</h2>
            <p class="muted">Atualizacao automatica a cada 10 segundos.</p>
        </div>
    </div>
    <div id="panel-content">
        <?php if (empty($queue)): ?>
            <p>Sem pacientes na fila.</p>
        <?php else: ?>
            <?php $last = $queue[0]; ?>
            <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:16px;padding:24px;">
                <h1 style="font-size:52px;margin:0;color:#1e3a8a;">Senha <?= e($last['ticket_number']) ?></h1>
                <p style="font-size:24px;color:#0f172a;margin-top:8px;">Paciente: <?= e($last['full_name']) ?></p>
                <p style="font-size:20px;color:#334155;">Sala: <?= e($last['room'] ?? 'A definir') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    setInterval(function () {
        fetch(window.location.pathname + window.location.search, {headers: {'X-Panel-Refresh': '1'}})
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var content = doc.querySelector('#panel-content');
                if (content) document.querySelector('#panel-content').innerHTML = content.innerHTML;
            });
    }, 10000);
</script>

