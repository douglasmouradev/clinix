(function () {
    var config = window.CLINIX_PANEL || {};
    var contentEl = document.getElementById('panel-content');
    var waitingEl = document.getElementById('panel-waiting-count');
    var recentEl = document.getElementById('panel-recent');
    var lastRevision = config.initial ? (config.initial.revision || 'idle') : null;
    var pollTimer = null;
    var polling = false;
    var hideNames = config.hideNames === true;
    var cachedPtVoice = null;
    var audioCtx = null;

    if (!contentEl) {
        return;
    }

    function getAudioContext() {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) {
            return null;
        }
        if (!audioCtx) {
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().catch(function () {
                /* TV pode bloquear até política do navegador permitir */
            });
        }
        return audioCtx;
    }

    function warmUpSpeech() {
        if (!window.speechSynthesis) {
            return;
        }
        try {
            resolvePtVoice();
            window.speechSynthesis.cancel();
            var warm = new SpeechSynthesisUtterance(' ');
            warm.volume = 0.01;
            warm.rate = 10;
            warm.lang = 'pt-BR';
            var voice = resolvePtVoice();
            if (voice) {
                warm.voice = voice;
            }
            window.speechSynthesis.speak(warm);
        } catch (e) {
            /* opcional */
        }
    }

    function warmUpAudio() {
        getAudioContext();
        warmUpSpeech();
    }

    warmUpAudio();
    window.addEventListener('load', warmUpAudio);
    window.setInterval(warmUpAudio, 30000);

    function resolvePtVoice() {
        if (cachedPtVoice || !window.speechSynthesis) {
            return cachedPtVoice;
        }
        var voices = window.speechSynthesis.getVoices() || [];
        cachedPtVoice = voices.find(function (voice) {
            return /^pt(-|_)/i.test(voice.lang);
        }) || null;
        return cachedPtVoice;
    }

    if (window.speechSynthesis && window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = resolvePtVoice;
    }
    resolvePtVoice();

    function digitWord(ch) {
        var words = ['zero', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
        var n = parseInt(ch, 10);
        return Number.isNaN(n) ? ch : words[n];
    }

    function ticketSpeech(ticket) {
        var raw = String(ticket || '').replace(/^#/, '').trim();
        if (!raw) {
            return 'não informada';
        }
        return raw.split('').map(function (ch) {
            return /\d/.test(ch) ? digitWord(ch) : ch;
        }).join(', ');
    }

    function speakCall(called) {
        if (!called || called.live !== true || !window.speechSynthesis) {
            return;
        }

        warmUpSpeech();

        var parts = ['Atenção!', 'Senha', ticketSpeech(called.ticket_number)];
        var name = displayName(called.full_name);
        if (!hideNames && name && name !== 'Paciente') {
            parts.push(name);
        }
        parts.push('Dirija-se a', String(called.room || 'o local de atendimento'));

        var utterance = new SpeechSynthesisUtterance(parts.join('. '));
        utterance.lang = 'pt-BR';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;

        var voice = resolvePtVoice();
        if (voice) {
            utterance.voice = voice;
        }

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function displayName(name) {
        if (hideNames && name) {
            return 'Paciente';
        }
        return name;
    }

    function beep() {
        try {
            var ctx = getAudioContext();
            if (!ctx) {
                return;
            }
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.45);
            osc.start();
            osc.stop(ctx.currentTime + 0.45);
            setTimeout(function () {
                var osc2 = ctx.createOscillator();
                var gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.frequency.value = 1100;
                gain2.gain.value = 0.12;
                osc2.start();
                osc2.stop(ctx.currentTime + 0.2);
            }, 220);
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
        var nameHtml = hideNames
            ? ''
            : '<p class="panel-call-name">' + escapeHtml(displayName(called.full_name)) + '</p>';

        contentEl.innerHTML =
            '<div class="' + cardClass + '">' +
            '<p class="panel-call-label">' + label + '</p>' +
            '<h1 class="panel-call-number">#' + escapeHtml(called.ticket_number) + '</h1>' +
            nameHtml +
            '<p class="panel-call-room">Dirija-se a: <strong>' + escapeHtml(called.room || 'A definir') + '</strong></p></div>';

        if (isNew) {
            beep();
            if (isLive) {
                speakCall(called);
            }
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
                '<h3>Últimas chamadas</h3>' +
                '<p class="panel-recent-empty">Nenhuma chamada registrada hoje.</p>';
            return;
        }

        var html = '<h3>Últimas chamadas</h3><ul class="panel-recent-list">';
        recent.forEach(function (item) {
            var time = item.time_label ? ' · ' + escapeHtml(item.time_label) : '';
            var nameHtml = hideNames
                ? ''
                : '<span class="panel-recent-name">' + escapeHtml(displayName(item.full_name)) + '</span>';
            html +=
                '<li class="panel-recent-item">' +
                '<span class="panel-recent-number">#' + escapeHtml(item.ticket_number) + '</span>' +
                nameHtml +
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
                        '<p class="error">Não foi possível atualizar o painel. Recarregue a página.</p>';
                }
            })
            .finally(function () {
                polling = false;
            });
    }

    function startPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
        }
        var ms = config.pollMs || 4000;
        pollTimer = window.setInterval(poll, ms);
    }

    function startSse() {
        if (!config.streamUrl || typeof EventSource === 'undefined') {
            startPolling();
            return;
        }

        try {
            var es = new EventSource(config.streamUrl);
            es.addEventListener('update', function (event) {
                try {
                    applyPayload(JSON.parse(event.data), true);
                } catch (e) {
                    /* ignore */
                }
            });
            es.onerror = function () {
                es.close();
                startPolling();
            };
        } catch (e) {
            startPolling();
        }
    }

    if (config.initial) {
        applyPayload(config.initial, false);
    }

    if (config.useSse) {
        startSse();
    } else {
        startPolling();
    }

    window.setTimeout(poll, 1500);
})();
