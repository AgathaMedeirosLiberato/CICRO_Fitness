document.addEventListener('change', function (e) {
    var lista = e.target;
    if (!lista.matches || !lista.matches('.cor-campo select')) return;
    var texto = lista.parentNode.querySelector('input');
    var outra = lista.value === '__outra';
    texto.hidden = !outra;
    texto.required = outra;
    if (outra) texto.focus();
});
