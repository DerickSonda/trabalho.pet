/**
 * Comportamento da listagem de registros:
 *  - confirmacao antes de excluir
 *  - auto-submit do form de filtros ao mudar select com data-auto-submit
 */
(function () {
    'use strict';

    function confirmarExclusao(botao) {
        const rotulo = botao.getAttribute('data-rotulo') || 'este registro';
        const ok = window.confirm('Tem certeza que deseja excluir ' + rotulo + '?');
        if (ok) {
            const form = botao.closest('form');
            if (form) {
                form.submit();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Botoes de excluir
        const botoes = document.querySelectorAll('[data-acao-excluir-registro]');
        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                confirmarExclusao(botao);
            });
        });

        // Auto-submit dos filtros (apenas selects - nao submete a cada
        // tecla digitada nos campos de data)
        const formFiltros = document.getElementById('filtros-registros');
        if (formFiltros) {
            const selects = formFiltros.querySelectorAll('[data-auto-submit]');
            selects.forEach(function (campo) {
                campo.addEventListener('change', function () {
                    formFiltros.submit();
                });
            });
        }
    });
})();
