<?php
/**
 * Autenticacao: controle de sessao e helpers de login.
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';

/**
 * Inicia a sessao apenas se ainda nao foi iniciada (idempotente).
 */
function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica se ha um usuario logado na sessao atual.
 */
function usuarioLogado(): bool
{
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}

/**
 * Bloqueia paginas que exigem login.
 * Se o usuario nao esta logado, salva uma mensagem de erro e
 * redireciona para a tela de login.
 */
function exigirLogin(): void
{
    if (!usuarioLogado()) {
        mensagemFlash('erro', 'Voce precisa estar logado para acessar esta pagina.');
        redirecionar('/trabalho.pet/public/login.php');
    }
}

/**
 * Retorna os dados do usuario logado (id, nome, email) ou null.
 * Consulta o banco para garantir que o usuario ainda existe.
 */
function usuarioAtual(): ?array
{
    if (!usuarioLogado()) {
        return null;
    }

    $pdo = obterConexao();
    $stmt = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    return $usuario ?: null;
}

/**
 * Marca o usuario como logado na sessao.
 * Regenera o id da sessao para prevenir session fixation.
 *
 * @param array $usuario array com pelo menos a chave 'id'
 */
function logarUsuario(array $usuario): void
{
    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
}

/**
 * Encerra a sessao do usuario.
 */
function deslogarUsuario(): void
{
    iniciarSessao();
    $_SESSION = [];
    session_unset();
    session_destroy();
}
