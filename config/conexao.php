<?php
/**
 * Conexao com o banco de dados via PDO.
 *
 * Para sobrescrever credenciais sem mexer neste arquivo (ex.: outra
 * maquina), basta criar /config/conexao_local.php definindo as mesmas
 * constantes ANTES do require deste arquivo. O .gitignore ja ignora
 * esse local override.
 */

// Permite override local opcional (nao versionado)
if (file_exists(__DIR__ . '/conexao_local.php')) {
    require_once __DIR__ . '/conexao_local.php';
}

// Credenciais padrao do XAMPP - so define se ainda nao foram setadas
if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_NOME'))    define('DB_NOME',    'diario_pets');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_SENHA'))   define('DB_SENHA',   '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma instancia PDO ja configurada.
 * Usa singleton simples para nao abrir multiplas conexoes por request.
 */
function obterConexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';dbname=' . DB_NOME
         . ';charset=' . DB_CHARSET;

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_SENHA, $opcoes);
    } catch (PDOException $e) {
        // Em ambiente academico/local mostramos mensagem amigavel.
        // O detalhe do erro vai pro log do servidor, nao pro usuario.
        error_log('Falha de conexao com o banco: ' . $e->getMessage());
        http_response_code(500);
        die('Nao foi possivel conectar ao banco de dados. '
            . 'Verifique se o MySQL esta rodando e se o banco "diario_pets" foi criado.');
    }

    return $pdo;
}
