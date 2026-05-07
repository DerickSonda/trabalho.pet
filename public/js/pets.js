/**
 * Comportamento da listagem de pets:
 * confirmacao antes de excluir.
 */
(function () {
    'use strict';

    /**
     * Mostra confirm() e, se aceito, submete o form de exclusao
     * que envolve o botao clicado.
     */
    function confirmarExclusao(botao) {
        const nome = botao.getAttribute('data-nome-pet') || 'este pet';
        const ok = window.confirm(
            'Tem certeza que deseja excluir "' + nome + '"?\n' +
            'Todos os registros vinculados a ele serao removidos.'
        );
        if (ok) {
            const form = botao.closest('form');
            if (form) {
                form.submit();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const botoes = document.querySelectorAll('[data-acao-excluir]');
        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                confirmarExclusao(botao);
            });
        });
    });
})();
