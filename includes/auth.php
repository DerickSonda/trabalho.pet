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
 *
 * Alem de checar a sessao, garante que o usuario ainda existe no banco.
 * Se a sessao tiver um usuario_id orfao (ex.: usuario foi excluido,
 * banco recriado), destroi a sessao e manda pro login.
 */
function exigirLogin(): void
{
    if (!usuarioLogado()) {
        mensagemFlash('erro', 'Voce precisa estar logado para acessar esta pagina.');
        redirecionar('/trabalho.pet/public/login.php');
    }

    if (usuarioAtual() === null) {
        deslogarUsuario();
        iniciarSessao();
        mensagemFlash('erro', 'Sua sessao expirou. Faca login novamente.');
        redirecionar('/trabalho.pet/public/login.php');
    }
}

/**
 * Retorna os dados do usuario logado (id, nome, email) ou null.
 * Consulta o banco para garantir que o usuario ainda existe.
 *
 * Resultado e cacheado por request - chamadas seguintes nao reconsultam.
 */
function usuarioAtual(): ?array
{
    static $cache = null;
    static $consultado = false;

    if ($consultado) {
        return $cache;
    }

    if (!usuarioLogado()) {
        $consultado = true;
        return null;
    }

    $pdo = obterConexao();
    $stmt = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    $cache = $usuario ?: null;
    $consultado = true;
    return $cache;
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
