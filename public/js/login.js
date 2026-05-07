/**
 * Validacao do formulario de login no lado do cliente.
 *
 * Apenas verifica se os campos estao preenchidos. A validacao
 * de credenciais acontece sempre no servidor (login.php).
 */
(function () {
    'use strict';

    const formulario = document.getElementById('form-login');
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

        const email = formulario.email.value.trim();
        const senha = formulario.senha.value;
        let valido = true;

        if (email === '') {
            mostrarErro('email', 'Informe seu email.');
            valido = false;
        }
        if (senha === '') {
            mostrarErro('senha', 'Informe sua senha.');
            valido = false;
        }

        if (!valido) {
            evento.preventDefault();
        }
    });
})();
