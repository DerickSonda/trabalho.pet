/**
 * Validacao client-side do formulario de pet.
 * Server-side em pet_form.php valida tudo de novo.
 */
(function () {
    'use strict';

    const formulario = document.getElementById('form-pet');
    if (!formulario) {
        return;
    }

    const TAMANHO_MAX_FOTO = 2 * 1024 * 1024; // 2 MB
    const EXTENSOES_FOTO = ['jpg', 'jpeg', 'png', 'webp'];

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

        const nome    = formulario.nome.value.trim();
        const especie = formulario.especie.value;
        const peso    = formulario.peso.value.trim();
        const foto    = formulario.foto.files[0];

        let valido = true;

        if (nome.length < 2) {
            mostrarErro('nome', 'O nome deve ter pelo menos 2 caracteres.');
            valido = false;
        }
        if (especie === '') {
            mostrarErro('especie', 'Escolha uma especie.');
            valido = false;
        }

        if (peso !== '') {
            // Aceita virgula, troca por ponto antes de testar
            const valorPeso = parseFloat(peso.replace(',', '.'));
            if (isNaN(valorPeso) || valorPeso < 0.1 || valorPeso > 200) {
                mostrarErro('peso', 'Peso deve estar entre 0,1 e 200 kg.');
                valido = false;
            }
        }

        if (foto) {
            if (foto.size > TAMANHO_MAX_FOTO) {
                mostrarErro('foto', 'A foto deve ter no maximo 2 MB.');
                valido = false;
            } else {
                const partes = foto.name.split('.');
                const ext = partes.length > 1
                    ? partes[partes.length - 1].toLowerCase()
                    : '';
                if (EXTENSOES_FOTO.indexOf(ext) === -1) {
                    mostrarErro('foto', 'Use uma imagem JPG, PNG ou WEBP.');
                    valido = false;
                }
            }
        }

        if (!valido) {
            evento.preventDefault();
        }
    });
})();
