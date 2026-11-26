'use strict';
// Montos rápidos; la validación definitiva siempre se realiza en PHP.
document.querySelectorAll('.monto-rapido').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = document.getElementById('monto');
        if (campo) campo.value = boton.dataset.monto;
    });
});
