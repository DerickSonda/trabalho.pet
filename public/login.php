<?php
/**
 * Tela e processamento de login.
 */

require_once __DIR__ . '/../includes/auth.php';
iniciarSessao();

// Se ja esta logado, manda direto pro dashboard
if (usuarioLogado()) {
    redirecionar('/trabalho.pet/public/dashboard.php');
}

$erroLogin   = '';
$emailAntigo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']      ?? '';

    $emailAntigo = $email;

    // Mensagem unica e generica em qualquer falha de validacao/credencial:
    // assim nao revelamos se o email existe ou nao (anti-enumeracao).
    $mensagemGenerica = 'Email ou senha incorretos.';

    if ($email === '' || $senha === '') {
        $erroLogin = $mensagemGenerica;
    } else {
        $pdo  = obterConexao();
        $stmt = $pdo->prepare(
            'SELECT id, nome, email, senha_hash FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            logarUsuario($usuario);
            redirecionar('/trabalho.pet/public/dashboard.php');
        }

        $erroLogin = $mensagemGenerica;
    }
}

$titulo  = 'Entrar';
$scripts = ['/trabalho.pet/public/js/login.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container-auth" style="margin: 0 auto;">
    <div class="cartao">
        <h1>Entrar</h1>

        <?php if ($erroLogin !== ''): ?>
            <div class="alerta alerta-erro"><?= escapar($erroLogin) ?></div>
        <?php endif; ?>

        <form id="form-login" class="formulario" method="post" action="" novalidate>
            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= escapar($emailAntigo) ?>"
                       autocomplete="email" required>
                <span class="campo-erro" data-erro="email"></span>
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha"
                       autocomplete="current-password" required>
                <span class="campo-erro" data-erro="senha"></span>
            </div>

            <button type="submit" class="botao botao-primario">Entrar</button>

            <p class="texto-centro">
                Ainda nao tem conta?
                <a href="/trabalho.pet/public/cadastro.php">Cadastre-se</a>
            </p>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
