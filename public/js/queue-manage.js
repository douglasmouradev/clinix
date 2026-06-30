(function () {
    var config = window.CLINIX_QUEUE || {};
    var flashEl = document.getElementById('queue-flash');
    var tableBody = document.getElementById('queue-table-body');
    var countPill = document.getElementById('queue-count-pill');
    var syncEl = document.getElementById('queue-sync-label');
    var callSelect = document.getElementById('queue-call-select');
    var callRoom = document.getElementById('queue-call-room');
    var pollTimer = null;
    var polling = false;
    var lastSyncAt = null;

    var statusLabels = {
        waiting: 'Aguardando',
        called: 'Chamado',
        done: 'Finalizado',
    };
    var kioskKinds = ['Prioritário', 'Agendado', 'Sem agendamento'];

    function suggestedCallRoom(ticketRoom) {
        var room = String(ticketRoom || '').trim();
        if (!room || kioskKinds.indexOf(room) !== -1) {
            return config.defaultRoom || 'Triagem';
        }
        return room;
    }

    function resolveCallRoom(fallbackRoom) {
        if (callRoom && callRoom.value.trim() !== '') {
            return callRoom.value.trim();
        }
        return suggestedCallRoom(fallbackRoom);
    }

    function showFlash(type, message) {
        if (!flashEl) {
            return;
        }
        flashEl.hidden = false;
        flashEl.className = 'queue-flash ' + type;
        flashEl.textContent = message;
        window.setTimeout(function () {
            flashEl.hidden = true;
        }, 4000);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function statusLabel(ticket) {
        return ticket.status_label || statusLabels[ticket.status] || ticket.status;
    }

    function setBusy(button, busy) {
        if (!button) {
            return;
        }
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function updateSyncLabel() {
        if (!syncEl || !lastSyncAt) {
            return;
        }
        var secs = Math.max(0, Math.floor((Date.now() - lastSyncAt) / 1000));
        syncEl.textContent = 'Atualizado há ' + secs + ' s';
    }

    function statusBadgeClass(status) {
        if (status === 'waiting') {
            return 'status-badge status-waiting';
        }
        if (status === 'called') {
            return 'status-badge status-called';
        }
        if (status === 'done') {
            return 'status-badge status-done';
        }
        return 'status-badge';
    }

    function renderRows(queue) {
        if (!tableBody) {
            return;
        }

        if (!queue.length) {
            tableBody.innerHTML =
                '<tr><td colspan="5" class="empty-state-cell">' +
                '<p class="empty-state-title">Nenhuma senha na fila</p>' +
                '<p class="empty-state-hint">Gere uma senha na recepção ou aguarde o totem.</p></td></tr>';
            if (countPill) {
                countPill.textContent = '0 na fila';
            }
            if (callSelect) {
                callSelect.innerHTML = '<option value="">Selecione</option>';
            }
            return;
        }

        var html = '';
        var selectHtml = '<option value="">Selecione</option>';

        queue.forEach(function (ticket) {
            var room = suggestedCallRoom(ticket.room);
            html += '<tr data-ticket-id="' + ticket.id + '">' +
                '<td data-label="Senha">#' + escapeHtml(ticket.ticket_number) + '</td>' +
                '<td data-label="Paciente">' + escapeHtml(ticket.full_name) + '</td>' +
                '<td data-label="Status"><span class="' + statusBadgeClass(ticket.status) + '">' +
                escapeHtml(statusLabel(ticket)) + '</span></td>' +
                '<td data-label="Destino">' + escapeHtml(ticket.room || '-') + '</td>' +
                '<td class="queue-actions">';

            if (config.canPrint) {
                html += '<button type="button" class="btn secondary small queue-print-btn" ' +
                    'data-ticket-id="' + ticket.id + '">Imprimir</button>';
            }

            if (config.canCall && ticket.status === 'waiting') {
                html += '<button type="button" class="btn small queue-call-btn" ' +
                    'data-ticket-id="' + ticket.id + '" data-room="' + escapeHtml(room) + '">Chamar senha</button>';
                selectHtml += '<option value="' + ticket.id + '" data-room="' + escapeHtml(room) + '">#' +
                    escapeHtml(ticket.ticket_number) + ' - ' + escapeHtml(ticket.full_name) + '</option>';
            }

            if (config.canDone && ticket.status === 'called') {
                html += '<button type="button" class="btn small queue-done-btn" ' +
                    'data-ticket-id="' + ticket.id + '">Finalizar</button>';
            }

            html += '</td></tr>';
        });

        tableBody.innerHTML = html;
        if (countPill) {
            countPill.textContent = queue.length + ' na fila';
        }
        if (callSelect) {
            var selectedTicketId = callSelect.value;
            callSelect.innerHTML = selectHtml;
            if (selectedTicketId && callSelect.querySelector('option[value="' + selectedTicketId + '"]')) {
                callSelect.value = selectedTicketId;
            }
        }
        bindActionButtons();
    }

    function openTicketPrint(ticketId) {
        if (!ticketId) {
            return;
        }
        var url = config.appUrl + '/?route=queue.ticket.print&id=' + encodeURIComponent(String(ticketId));
        window.open(url, 'clinix_ticket_print', 'width=300,height=640');
    }

    function postQueue(route, body) {
        var formData = new FormData();
        Object.keys(body).forEach(function (key) {
            formData.append(key, body[key]);
        });
        formData.append('_csrf_token', config.csrfToken);

        return fetch(config.appUrl + '/?route=' + route, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        }).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, data: data };
            });
        });
    }

    function applyQueueData(data) {
        if (!data || !data.ok) {
            return;
        }
        if (data.unchanged) {
            lastSyncAt = Date.now();
            updateSyncLabel();
            return;
        }
        renderRows(data.queue || []);
        lastSyncAt = Date.now();
        updateSyncLabel();
        if (data.revision) {
            config.revision = data.revision;
        }
    }

    function pollQueue() {
        if (!config.dataUrl || polling) {
            return;
        }
        polling = true;
        var url = config.dataUrl;
        if (config.revision) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'revision=' + encodeURIComponent(config.revision);
        }
        fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
        })
            .then(function (r) {
                return r.json();
            })
            .then(applyQueueData)
            .catch(function () {
                /* silencioso */
            })
            .finally(function () {
                polling = false;
            });
    }

    function callTicket(ticketId, room, button) {
        var row = button ? button.closest('tr') : null;
        if (row) {
            row.classList.add('queue-row-calling');
        }
        setBusy(button, true);

        return postQueue('queue.call', {
            ticket_id: String(ticketId),
            room: room || config.defaultRoom || 'Triagem',
        })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Não foi possível chamar a senha.');
                }
                showFlash('success', result.data.message || 'Paciente chamado.');
                applyQueueData(result.data);
                return result.data;
            })
            .catch(function (error) {
                showFlash('error', error.message || 'Erro ao chamar senha.');
            })
            .finally(function () {
                if (row) {
                    row.classList.remove('queue-row-calling');
                }
                setBusy(button, false);
            });
    }

    function doneTicket(ticketId, button) {
        setBusy(button, true);

        return postQueue('queue.done', { ticket_id: String(ticketId) })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Não foi possível finalizar.');
                }
                showFlash('success', result.data.message || 'Atendimento finalizado.');
                applyQueueData(result.data);
            })
            .catch(function (error) {
                showFlash('error', error.message || 'Erro ao finalizar.');
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function callNext(button) {
        setBusy(button, true);
        var nextRoom = document.getElementById('queue-next-room');
        var room = (nextRoom && nextRoom.value) || (callRoom && callRoom.value) || config.defaultRoom;
        return postQueue('queue.call.next', { room: room || config.defaultRoom || 'Triagem' })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Nenhuma senha aguardando.');
                }
                showFlash('success', result.data.message || 'Próxima senha chamada.');
                applyQueueData(result.data);
            })
            .catch(function (error) {
                showFlash('error', error.message || 'Erro ao chamar próxima senha.');
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function bindActionButtons() {
        document.querySelectorAll('.queue-print-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                openTicketPrint(button.getAttribute('data-ticket-id'));
            });
        });

        document.querySelectorAll('.queue-call-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                callTicket(
                    button.getAttribute('data-ticket-id'),
                    resolveCallRoom(button.getAttribute('data-room')),
                    button
                );
            });
        });

        document.querySelectorAll('.queue-done-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                doneTicket(button.getAttribute('data-ticket-id'), button);
            });
        });
    }

    document.querySelectorAll('.queue-ajax-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var action = form.getAttribute('data-queue-action');
            var formData = new FormData(form);
            var payload = {};
            formData.forEach(function (value, key) {
                if (key !== '_csrf_token') {
                    payload[key] = value;
                }
            });

            var route = action === 'generate' ? 'queue.generate' : 'queue.call';
            var submitBtn = form.querySelector('button[type="submit"]');
            setBusy(submitBtn, true);

            postQueue(route, payload)
                .then(function (result) {
                    if (!result.ok || !result.data.ok) {
                        throw new Error(result.data.message || 'Operação não concluída.');
                    }
                    showFlash('success', result.data.message || 'Concluído.');
                    applyQueueData(result.data);
                    if (action === 'generate') {
                        var autoPrint = document.getElementById('queue-auto-print');
                        if (result.data.ticket && (!autoPrint || autoPrint.checked)) {
                            openTicketPrint(result.data.ticket.id);
                        }
                        form.reset();
                        if (autoPrint) {
                            autoPrint.checked = true;
                        }
                    }
                })
                .catch(function (error) {
                    showFlash('error', error.message || 'Erro na operação.');
                })
                .finally(function () {
                    setBusy(submitBtn, false);
                });
        });
    });

    var callNextBtn = document.getElementById('queue-call-next-btn');
    if (callNextBtn) {
        callNextBtn.addEventListener('click', function () {
            callNext(callNextBtn);
        });
    }

    if (callSelect && callRoom) {
        callSelect.addEventListener('change', function () {
            var option = callSelect.options[callSelect.selectedIndex];
            if (!option || !option.value) {
                return;
            }
            var room = option.getAttribute('data-room');
            if (room) {
                callRoom.value = room;
            }
        });
    }

    bindActionButtons();

    if (config.dataUrl) {
        pollTimer = window.setInterval(pollQueue, config.pollMs || 4000);
        window.setInterval(updateSyncLabel, 1000);
        window.setTimeout(pollQueue, 1200);
    }
})();
