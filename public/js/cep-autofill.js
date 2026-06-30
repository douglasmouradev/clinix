(function () {
    var cepInput = document.getElementById('cep-input');
    if (!cepInput) {
        return;
    }

    var street = document.getElementById('address-street');
    var neighborhood = document.getElementById('address-neighborhood');
    var city = document.getElementById('address-city');
    var state = document.getElementById('address-state');
    var status = document.getElementById('cep-status');
    var loading = false;

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
        if (street && data.complemento && street.value.indexOf(data.complemento) === -1) {
            var complement = document.getElementById('address-complement');
            if (complement && !complement.value) {
                complement.value = data.complemento;
            }
        }
    }

    function lookupCep(digits) {
        if (digits.length !== 8 || loading) {
            return;
        }
        loading = true;
        setStatus('Buscando endereço...', 'loading');

        fetch('https://viacep.com.br/ws/' + digits + '/json/', {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || data.erro) {
                    setStatus('CEP não encontrado.', 'error');
                    return;
                }
                fillAddress(data);
                setStatus('Endereço preenchido automaticamente.', 'success');
                var number = document.getElementById('address-number');
                if (number) {
                    number.focus();
                }
            })
            .catch(function () {
                setStatus('Não foi possível consultar o CEP. Preencha manualmente.', 'error');
            })
            .finally(function () {
                loading = false;
            });
    }

    cepInput.addEventListener('input', function () {
        var digits = onlyDigits(cepInput.value);
        cepInput.value = formatCep(digits);
        setStatus('', '');
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
