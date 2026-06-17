(function () {
    var body = document.body;
    if (!body || !body.classList.contains('kiosk-body')) {
        return;
    }

    var cpfInput = document.getElementById('kiosk-cpf');
    var keypadEl = document.getElementById('kiosk-keypad');
    var idleSeconds = parseInt(body.getAttribute('data-kiosk-idle-seconds') || '45', 10);
    var idleTimer = null;

    function formatCpf(digits) {
        if (digits.length <= 3) {
            return digits;
        }
        if (digits.length <= 6) {
            return digits.slice(0, 3) + '.' + digits.slice(3);
        }
        if (digits.length <= 9) {
            return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
        }
        return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
    }

    function setCpfDigits(digits) {
        if (!cpfInput) {
            return;
        }
        cpfInput.value = formatCpf(digits.slice(0, 11));
    }

    function currentDigits() {
        return cpfInput ? cpfInput.value.replace(/\D/g, '') : '';
    }

    function buildKeypad() {
        if (!keypadEl) {
            return;
        }
        var keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'limpar', '0', 'apagar'];
        keys.forEach(function (key) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'kiosk-key';
            if (key === 'limpar') {
                btn.textContent = 'Limpar';
                btn.classList.add('kiosk-key-wide');
                btn.addEventListener('click', function () {
                    setCpfDigits('');
                });
            } else if (key === 'apagar') {
                btn.textContent = 'Apagar';
                btn.classList.add('kiosk-key-wide');
                btn.addEventListener('click', function () {
                    var d = currentDigits();
                    setCpfDigits(d.slice(0, -1));
                });
            } else {
                btn.textContent = key;
                btn.addEventListener('click', function () {
                    setCpfDigits(currentDigits() + key);
                });
            }
            keypadEl.appendChild(btn);
        });
    }

    function resetIdleTimer() {
        if (!idleSeconds || idleSeconds < 10) {
            return;
        }
        if (idleTimer) {
            window.clearTimeout(idleTimer);
        }
        idleTimer = window.setTimeout(function () {
            var params = new URLSearchParams(window.location.search);
            if (params.get('route') === 'queue.kiosk') {
                return;
            }
            var back = window.location.pathname + '?route=queue.kiosk';
            if (params.get('token')) {
                back += '&token=' + encodeURIComponent(params.get('token'));
            }
            if (params.get('tenant')) {
                back += '&tenant=' + encodeURIComponent(params.get('tenant'));
            }
            window.location.replace(back);
        }, idleSeconds * 1000);
    }

    buildKeypad();
    if (cpfInput) {
        cpfInput.setAttribute('tabindex', '-1');
        cpfInput.addEventListener('focus', function (event) {
            event.target.blur();
        });
    }
    ['click', 'touchstart', 'keydown', 'input'].forEach(function (ev) {
        document.addEventListener(ev, resetIdleTimer, { passive: true });
    });
    resetIdleTimer();
})();
