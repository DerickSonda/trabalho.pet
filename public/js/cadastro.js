/**
 * Validacao do formulario de cadastro no lado do cliente.
 *
 * Apenas para melhorar a experiencia do usuario - a validacao
 * de seguranca acontece sempre no servidor (cadastro.php).
 */
(function () {
    'use strict';

    const formulario = document.getElementById('form-cadastro');
    if (!formulario) {
        return;
    }

    // Regex simples para validar formato de email
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /**
     * Mostra a mensagem de erro embaixo do campo.
     */
    function mostrarErro(nomeCampo, mensagem) {
        const span = formulario.querySelector('[data-erro="' + nomeCampo + '"]');
        if (span) {
            span.textContent = mensagem;
        }
    }

    /**
     * Limpa todas as mensagens de erro do formulario.
     */
    function limparErros() {
        const spans = formulario.querySelectorAll('.campo-erro');
        spans.forEach(function (s) { s.textContent = ''; });
    }

    formulario.addEventListener('submit', function (evento) {
        limparErros();

        const nome             = formulario.nome.value.trim();
        const email            = formulario.email.value.trim();
        const senha            = formulario.senha.value;
        const confirmacaoSenha = formulario.confirmacao_senha.value;

        let valido = true;

        if (nome.length < 2) {
            mostrarErro('nome', 'O nome deve ter pelo menos 2 caracteres.');
            valido = false;
        }
        if (!regexEmail.test(email)) {
            mostrarErro('email', 'Informe um email valido.');
            valido = false;
        }
        if (senha.length < 8) {
            mostrarErro('senha', 'A senha deve ter pelo menos 8 caracteres.');
            valido = false;
        }
        if (senha !== confirmacaoSenha) {
            mostrarErro('confirmacao_senha', 'A confirmacao nao confere com a senha.');
            valido = false;
        }

        if (!valido) {
            evento.preventDefault();
        }
    });
})();
