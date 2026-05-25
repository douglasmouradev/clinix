(function () {
    var cpfInput = document.getElementById('kiosk-cpf');
    if (!cpfInput) {
        return;
    }

    cpfInput.addEventListener('input', function () {
        var digits = cpfInput.value.replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 3) {
            cpfInput.value = digits;
        } else if (digits.length <= 6) {
            cpfInput.value = digits.slice(0, 3) + '.' + digits.slice(3);
        } else if (digits.length <= 9) {
            cpfInput.value = digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
        } else {
            cpfInput.value = digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
        }
    });

    cpfInput.focus();
})();
