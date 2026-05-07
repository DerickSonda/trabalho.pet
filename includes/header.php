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
    <link rel="stylesheet" href="/trabalho.pet/public/css/estilo.css">
</head>
<body>
    <header class="topo">
        <div class="topo-conteudo">
            <a href="/trabalho.pet/public/" class="topo-marca">Diario de Pets</a>
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
