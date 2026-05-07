<?php
/**
 * Funcoes utilitarias compartilhadas pelo sistema.
 */

/**
 * Escapa texto para saida HTML, prevenindo XSS.
 * Usa ENT_QUOTES para escapar tambem aspas simples e duplas.
 */
function escapar(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona o navegador para o caminho informado e encerra o script.
 */
function redirecionar(string $caminho): void
{
    header('Location: ' . $caminho);
    exit;
}

/**
 * Mensagens "flash" de uso unico.
 *
 * - mensagemFlash('erro', 'Algo deu errado'): grava na sessao
 * - mensagemFlash('erro'): retorna o valor e o remove da sessao
 *
 * Util para mostrar mensagens apos um redirect (login, cadastro, etc).
 */
function mensagemFlash(string $chave, ?string $valor = null): ?string
{
    if ($valor !== null) {
        $_SESSION['flash'][$chave] = $valor;
        return null;
    }

    if (!isset($_SESSION['flash'][$chave])) {
        return null;
    }

    $mensagem = $_SESSION['flash'][$chave];
    unset($_SESSION['flash'][$chave]);
    return $mensagem;
}
