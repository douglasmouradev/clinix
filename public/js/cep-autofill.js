(function () {
    var cepInput = document.getElementById('cep-input');
    if (!cepInput) {
        return;
    }

    var root = document.querySelector('.address-block');
    var appUrl = (root && root.getAttribute('data-app-url')) || '';
    var street = document.getElementById('address-street');
    var neighborhood = document.getElementById('address-neighborhood');
    var city = document.getElementById('address-city');
    var state = document.getElementById('address-state');
    var status = document.getElementById('cep-status');
    var loading = false;
    var lastLookup = '';

    function onlyDigits(value) {
        return String(value || '').replace(/\D/g, '').slice(0, 8);
    }

    function formatCep(digits) {
        if (digits.length <= 5) {
            return digits;
        }
        return digits.slice(0, 5) + '-' + digits.slice(5);
    }

    function setStatus(message, type) {
        if (!status) {
            return;
        }
        status.textContent = message || '';
        status.className = 'cep-status' + (type ? ' cep-status--' + type : '');
    }

    function fillAddress(data) {
        if (street && data.logradouro) {
            street.value = data.logradouro;
        }
        if (neighborhood && data.bairro) {
            neighborhood.value = data.bairro;
        }
        if (city && data.localidade) {
            city.value = data.localidade;
        }
        if (state && data.uf) {
            state.value = data.uf;
        }
        if (data.complemento) {
            var complement = document.getElementById('address-complement');
            if (complement && !complement.value) {
                complement.value = data.complemento;
            }
        }
    }

    function lookupCep(digits) {
        if (digits.length !== 8 || loading || digits === lastLookup) {
            return;
        }
        loading = true;
        setStatus('Buscando endereço...', 'loading');

        fetch(appUrl + '/?route=cep.lookup&cep=' + encodeURIComponent(digits), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.error || 'lookup_failed');
                    }
                    return payload.data;
                });
            })
            .then(function (data) {
                lastLookup = digits;
                fillAddress(data);
                setStatus('Endereço preenchido automaticamente.', 'success');
                var number = document.getElementById('address-number');
                if (number) {
                    number.focus();
                }
            })
            .catch(function (error) {
                var message = error && error.message === 'CEP não encontrado.'
                    ? 'CEP não encontrado.'
                    : 'Não foi possível consultar o CEP. Preencha manualmente.';
                setStatus(message, 'error');
            })
            .finally(function () {
                loading = false;
            });
    }

    cepInput.addEventListener('input', function () {
        var digits = onlyDigits(cepInput.value);
        cepInput.value = formatCep(digits);
        if (digits !== lastLookup) {
            setStatus('', '');
        }
        if (digits.length === 8) {
            lookupCep(digits);
        }
    });

    cepInput.addEventListener('blur', function () {
        var digits = onlyDigits(cepInput.value);
        if (digits.length === 8) {
            lookupCep(digits);
        }
    });
})();
