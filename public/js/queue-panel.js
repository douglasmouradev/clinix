(function () {
    var config = window.CLINIX_PANEL || {};
    var contentEl = document.getElementById('panel-content');
    var waitingEl = document.getElementById('panel-waiting-count');
    var recentEl = document.getElementById('panel-recent');
    var lastRevision = config.initial ? (config.initial.revision || 'idle') : null;
    var pollTimer = null;
    var polling = false;

    if (!contentEl) {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function beep() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.08;
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
        } catch (e) {
            /* opcional */
        }
    }

    function renderCalled(called, isNew) {
        if (!called) {
            contentEl.innerHTML =
                '<div class="panel-call-card panel-call-idle">' +
                '<p class="panel-call-label">Aguardando</p>' +
                '<h1 class="panel-call-number">—</h1>' +
                '<p class="panel-call-name">Nenhuma senha chamada hoje</p></div>';
            return;
        }

        var isLive = called.live === true;
        var cardClass = 'panel-call-card ' + (isLive ? 'panel-call-active' : 'panel-call-last');
        if (isNew) {
            cardClass += ' is-new';
        }
        var label = isLive ? 'Senha chamada agora' : 'Última chamada';

        contentEl.innerHTML =
            '<div class="' + cardClass + '">' +
            '<p class="panel-call-label">' + label + '</p>' +
            '<h1 class="panel-call-number">#' + escapeHtml(called.ticket_number) + '</h1>' +
            '<p class="panel-call-name">' + escapeHtml(called.full_name) + '</p>' +
            '<p class="panel-call-room">Dirija-se a: <strong>' + escapeHtml(called.room || 'A definir') + '</strong></p></div>';

        if (isNew) {
            beep();
            window.setTimeout(function () {
                var card = contentEl.querySelector('.panel-call-card');
                if (card) {
                    card.classList.remove('is-new');
                }
            }, 700);
        }
    }

    function renderRecent(recent) {
        if (!recentEl) {
            return;
        }
        if (!recent || !recent.length) {
            recentEl.innerHTML =
                '<h3>Últimas pessoas chamadas</h3>' +
                '<p class="muted panel-recent-empty">Nenhuma chamada registrada hoje.</p>';
            return;
        }

        var html = '<h3>Últimas pessoas chamadas</h3><ul class="panel-recent-list">';
        recent.forEach(function (item) {
            var time = item.time_label ? ' · ' + escapeHtml(item.time_label) : '';
            html +=
                '<li class="panel-recent-item">' +
                '<span class="panel-recent-number">#' + escapeHtml(item.ticket_number) + '</span>' +
                '<span class="panel-recent-name">' + escapeHtml(item.full_name) + '</span>' +
                '<span class="panel-recent-meta">' + escapeHtml(item.room || '-') + time + '</span>' +
                '</li>';
        });
        html += '</ul>';
        recentEl.innerHTML = html;
    }

    function applyPayload(data, fromPoll) {
        if (!data || data.ok === false) {
            return;
        }
        if (data.ok !== true && data.revision === undefined && data.called === undefined) {
            return;
        }

        var revision = data.revision || 'idle';
        var isNew = fromPoll && lastRevision !== null && revision !== lastRevision;
        lastRevision = revision;
        renderCalled(data.called, isNew && !!data.called);
        renderRecent(data.recent || []);
        if (waitingEl) {
            waitingEl.textContent = (data.waiting_count || 0) + ' aguardando';
        }
    }

    function poll() {
        if (!config.dataUrl || polling) {
            return;
        }
        polling = true;
        fetch(config.dataUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function (data) {
                applyPayload(data, true);
            })
            .catch(function () {
                if (lastRevision === null && contentEl) {
                    contentEl.innerHTML =
                        '<p class="error">Não foi possível atualizar o painel. Recarregue a página ou use a URL em Admin → Token do painel.</p>';
                }
            })
            .finally(function () {
                polling = false;
            });
    }

    function startPolling() {
        var ms = config.pollMs || 4000;
        pollTimer = window.setInterval(poll, ms);
    }

    if (config.initial) {
        applyPayload(config.initial, false);
    }

    startPolling();
    window.setTimeout(poll, 1500);
})();
