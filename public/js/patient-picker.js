(function () {
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initPicker(root) {
        var hidden = root.querySelector('[data-patient-id]');
        var input = root.querySelector('[data-patient-search]');
        var list = root.querySelector('[data-patient-results]');
        var appUrl = root.getAttribute('data-app-url') || '';
        var selectedLabel = root.getAttribute('data-selected-label') || '';
        var timer = null;

        if (!hidden || !input || !list || !appUrl) {
            return;
        }

        if (selectedLabel) {
            input.value = selectedLabel;
        }

        function hideList() {
            list.hidden = true;
            list.innerHTML = '';
        }

        function selectPatient(id, label) {
            hidden.value = String(id);
            input.value = label;
            hideList();
        }

        function renderResults(items) {
            if (!items.length) {
                list.innerHTML = '<div class="patient-picker-empty">Nenhum paciente encontrado</div>';
                list.hidden = false;
                return;
            }

            list.innerHTML = items.map(function (item) {
                var phone = item.phone ? ' · ' + escapeHtml(item.phone) : '';
                return '<button type="button" class="patient-picker-item" data-id="' + item.id + '" data-label="' + escapeHtml(item.full_name) + '">' +
                    '<strong>' + escapeHtml(item.full_name) + '</strong>' +
                    '<span>' + escapeHtml(item.cpf || '') + phone + '</span>' +
                    '</button>';
            }).join('');
            list.hidden = false;

            list.querySelectorAll('.patient-picker-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectPatient(btn.getAttribute('data-id'), btn.getAttribute('data-label'));
                });
            });
        }

        function search(query) {
            if (query.length < 2) {
                hideList();
                return;
            }

            fetch(appUrl + '/?route=patient.search&q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    renderResults((data && data.data) || []);
                })
                .catch(function () {
                    hideList();
                });
        }

        input.addEventListener('input', function () {
            hidden.value = '';
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                search(input.value.trim());
            }, 250);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2) {
                search(input.value.trim());
            }
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                hideList();
            }
        });
    }

    document.querySelectorAll('[data-patient-picker]').forEach(initPicker);
})();
