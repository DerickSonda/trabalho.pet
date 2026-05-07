<?php
/**
 * Encerra a sessao do usuario.
 */

require_once __DIR__ . '/../includes/auth.php';

iniciarSessao();
deslogarUsuario();

// deslogarUsuario destruiu a sessao - precisamos reiniciar
// para conseguir gravar a mensagem flash de saida com sucesso.
iniciarSessao();
mensagemFlash('sucesso', 'Voce saiu com sucesso.');

redirecionar('/trabalho.pet/public/login.php');
