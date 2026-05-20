(function () {
    var input = document.getElementById('cpf-input');
    if (!input) {
        return;
    }

    input.addEventListener('input', function () {
        var digits = input.value.replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 3) {
            input.value = digits;
        } else if (digits.length <= 6) {
            input.value = digits.slice(0, 3) + '.' + digits.slice(3);
        } else if (digits.length <= 9) {
            input.value = digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
        } else {
            input.value = digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
        }
    });
})();
