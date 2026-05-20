(function () {
    var config = window.CLINIX_QUEUE || {};
    var flashEl = document.getElementById('queue-flash');
    var tableBody = document.getElementById('queue-table-body');
    var countPill = document.getElementById('queue-count-pill');
    var callSelect = document.getElementById('queue-call-select');
    var callRoom = document.getElementById('queue-call-room');

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

    function statusLabel(status) {
        return status;
    }

    function renderRows(queue) {
        if (!tableBody) {
            return;
        }

        if (!queue.length) {
            tableBody.innerHTML = '<tr><td colspan="5">Nenhuma senha na fila.</td></tr>';
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
            var room = ticket.room || config.defaultRoom || 'Triagem';
            html += '<tr data-ticket-id="' + ticket.id + '">' +
                '<td>#' + escapeHtml(ticket.ticket_number) + '</td>' +
                '<td>' + escapeHtml(ticket.full_name) + '</td>' +
                '<td>' + escapeHtml(statusLabel(ticket.status)) + '</td>' +
                '<td>' + escapeHtml(ticket.room || '-') + '</td>' +
                '<td class="queue-actions">';

            if (config.canPrint) {
                html += '<button type="button" class="btn secondary small queue-print-btn" style="width:auto;" ' +
                    'data-ticket-id="' + ticket.id + '">Imprimir</button>';
            }

            if (config.canCall && ticket.status === 'waiting') {
                html += '<button type="button" class="btn small queue-call-btn" style="width:auto;" ' +
                    'data-ticket-id="' + ticket.id + '" data-room="' + escapeHtml(room) + '">Chamar senha</button>';
                selectHtml += '<option value="' + ticket.id + '" data-room="' + escapeHtml(room) + '">#' +
                    escapeHtml(ticket.ticket_number) + ' - ' + escapeHtml(ticket.full_name) + '</option>';
            }

            if (config.canDone && ticket.status === 'called') {
                html += '<button type="button" class="btn small queue-done-btn" style="width:auto;" ' +
                    'data-ticket-id="' + ticket.id + '">Finalizar</button>';
            }

            html += '</td></tr>';
        });

        tableBody.innerHTML = html;
        if (countPill) {
            countPill.textContent = queue.length + ' na fila';
        }
        if (callSelect) {
            callSelect.innerHTML = selectHtml;
        }
        bindActionButtons();
    }

    function openTicketPrint(ticketId) {
        if (!ticketId) {
            return;
        }
        var url = config.appUrl + '/?route=queue.ticket.print&id=' + encodeURIComponent(String(ticketId));
        window.open(url, 'clinix_ticket_print', 'width=420,height=640');
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

    function callTicket(ticketId, room, button) {
        var row = button ? button.closest('tr') : null;
        if (row) {
            row.classList.add('queue-row-calling');
        }
        if (button) {
            button.disabled = true;
        }

        return postQueue('queue.call', {
            ticket_id: String(ticketId),
            room: room || config.defaultRoom || 'Triagem',
        })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Não foi possível chamar a senha.');
                }
                showFlash('success', result.data.message || 'Paciente chamado.');
                renderRows(result.data.queue || []);
                return result.data;
            })
            .catch(function (error) {
                showFlash('error', error.message || 'Erro ao chamar senha.');
            })
            .finally(function () {
                if (row) {
                    row.classList.remove('queue-row-calling');
                }
                if (button) {
                    button.disabled = false;
                }
            });
    }

    function doneTicket(ticketId, button) {
        if (button) {
            button.disabled = true;
        }

        return postQueue('queue.done', { ticket_id: String(ticketId) })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Não foi possível finalizar.');
                }
                showFlash('success', result.data.message || 'Atendimento finalizado.');
                renderRows(result.data.queue || []);
            })
            .catch(function (error) {
                showFlash('error', error.message || 'Erro ao finalizar.');
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                }
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
                callTicket(button.getAttribute('data-ticket-id'), button.getAttribute('data-room'), button);
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
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            postQueue(route, payload)
                .then(function (result) {
                    if (!result.ok || !result.data.ok) {
                        throw new Error(result.data.message || 'Operação não concluída.');
                    }
                    showFlash('success', result.data.message || 'Concluído.');
                    renderRows(result.data.queue || []);
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
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });
    });

    if (callSelect && callRoom) {
        callSelect.addEventListener('change', function () {
            var option = callSelect.options[callSelect.selectedIndex];
            var room = option ? option.getAttribute('data-room') : '';
            if (room) {
                callRoom.value = room;
            }
        });
    }

    bindActionButtons();
})();
