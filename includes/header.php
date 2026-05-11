<?php
/**
 * Cabecalho HTML compartilhado.
 *
 * Variaveis esperadas (opcionais):
 *   $titulo  string  Titulo da aba do navegador. Default: "Diario de Pets".
 */

require_once __DIR__ . '/auth.php';
iniciarSessao();

$tituloPagina = isset($titulo) && $titulo !== ''
    ? $titulo . ' - Diario de Pets'
    : 'Diario de Pets';

$logado = usuarioLogado();
$usuario = $logado ? usuarioAtual() : null;

// Mensagens flash a serem exibidas no topo da pagina
$flashErro    = mensagemFlash('erro');
$flashSucesso = mensagemFlash('sucesso');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapar($tituloPagina) ?></title>
    <?php
        // Cache busting do CSS - mesma logica dos scripts no footer
        $cssCaminho  = '/trabalho.pet/public/css/estilo.css';
        $cssArquivo  = $_SERVER['DOCUMENT_ROOT'] . $cssCaminho;
        $cssVersao   = is_file($cssArquivo) ? filemtime($cssArquivo) : time();
    ?>
    <link rel="stylesheet" href="<?= escapar($cssCaminho) ?>?v=<?= $cssVersao ?>">
</head>
<body>
    <header class="topo">
        <div class="topo-conteudo">
            <a href="/trabalho.pet/public/" class="topo-marca">
                <svg class="topo-marca-icone" viewBox="0 0 64 64"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="grad-marca" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%"   stop-color="#6366f1"/>
                            <stop offset="100%" stop-color="#ec4899"/>
                        </linearGradient>
                    </defs>
                    <ellipse cx="14" cy="22" rx="6" ry="8" fill="url(#grad-marca)"/>
                    <ellipse cx="26" cy="13" rx="6" ry="9" fill="url(#grad-marca)"/>
                    <ellipse cx="38" cy="13" rx="6" ry="9" fill="url(#grad-marca)"/>
                    <ellipse cx="50" cy="22" rx="6" ry="8" fill="url(#grad-marca)"/>
                    <path d="M32 28 C20 28 14 38 14 46 C14 54 22 58 32 58
                             C42 58 50 54 50 46 C50 38 44 28 32 28 Z"
                          fill="url(#grad-marca)"/>
                </svg>
                <span>Diario de Pets</span>
            </a>
            <nav class="topo-menu">
                <?php if ($logado && $usuario): ?>
                    <span class="topo-usuario">Ola, <?= escapar($usuario['nome']) ?></span>
                    <a href="/trabalho.pet/public/logout.php">Sair</a>
                <?php else: ?>
                    <a href="/trabalho.pet/public/login.php">Entrar</a>
                    <a href="/trabalho.pet/public/cadastro.php">Cadastrar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($flashErro): ?>
            <div class="alerta alerta-erro"><?= escapar($flashErro) ?></div>
        <?php endif; ?>
        <?php if ($flashSucesso): ?>
            <div class="alerta alerta-sucesso"><?= escapar($flashSucesso) ?></div>
        <?php endif; ?>
