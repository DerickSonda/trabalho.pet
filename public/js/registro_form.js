/**
 * Validacao client-side do formulario de registro.
 * Server-side em registro_form.php valida tudo de novo.
 */
(function () {
    'use strict';

    const formulario = document.getElementById('form-registro');
    if (!formulario) {
        return;
    }

    function mostrarErro(nomeCampo, mensagem) {
        const span = formulario.querySelector('[data-erro="' + nomeCampo + '"]');
        if (span) {
            span.textContent = mensagem;
        }
    }

    function limparErros() {
        const spans = formulario.querySelectorAll('.campo-erro');
        spans.forEach(function (s) { s.textContent = ''; });
    }

    formulario.addEventListener('submit', function (evento) {
        limparErros();

        const petId    = formulario.pet_id.value;
        const tipo     = formulario.tipo.value;
        const dataHora = formulario.data_hora.value;
        const custo    = formulario.custo.value.trim();

        let valido = true;

        if (petId === '') {
            mostrarErro('pet_id', 'Selecione um pet.');
            valido = false;
        }
        if (tipo === '') {
            mostrarErro('tipo', 'Escolha um tipo.');
            valido = false;
        }
        if (dataHora === '') {
            mostrarErro('data_hora', 'Informe a data e hora.');
            valido = false;
        }
        if (custo !== '') {
            const valorCusto = parseFloat(custo.replace(',', '.'));
            if (isNaN(valorCusto) || valorCusto < 0) {
                mostrarErro('custo', 'Custo deve ser um numero positivo.');
                valido = false;
            }
        }

        if (!valido) {
            evento.preventDefault();
        }
    });
})();
