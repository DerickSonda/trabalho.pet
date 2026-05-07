<?php
/**
 * Front controller minimo: decide para onde mandar o visitante.
 */

require_once __DIR__ . '/../includes/auth.php';
iniciarSessao();

if (usuarioLogado()) {
    redirecionar('/trabalho.pet/public/dashboard.php');
}

redirecionar('/trabalho.pet/public/login.php');
