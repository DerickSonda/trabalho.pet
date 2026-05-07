<?php
/**
 * Tela e processamento de cadastro de novos usuarios.
 */

require_once __DIR__ . '/../includes/auth.php';
iniciarSessao();

// Se ja esta logado, manda direto pro dashboard
if (usuarioLogado()) {
    redirecionar('/trabalho.pet/public/dashboard.php');
}

$erros = [];
$nomeAntigo  = '';
$emailAntigo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sempre trim para nao aceitar campos so com espacos
    $nome              = trim($_POST['nome']              ?? '');
    $email             = trim($_POST['email']             ?? '');
    $senha             = $_POST['senha']                  ?? '';
    $confirmacaoSenha  = $_POST['confirmacao_senha']      ?? '';

    // Preserva nome e email para re-renderizar o form (NUNCA a senha)
    $nomeAntigo  = $nome;
    $emailAntigo = $email;

    // Validacoes basicas
    if (mb_strlen($nome) < 2) {
        $erros['nome'] = 'O nome deve ter pelo menos 2 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um email valido.';
    }
    if (strlen($senha) < 8) {
        $erros['senha'] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($senha !== $confirmacaoSenha) {
        $erros['confirmacao_senha'] = 'A confirmacao nao confere com a senha.';
    }

    // Verifica se o email ja esta cadastrado (so se ainda nao houve erro de email)
    if (!isset($erros['email'])) {
        $pdo  = obterConexao();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $erros['email'] = 'Este email ja esta cadastrado.';
        }
    }

    // Sem erros: persiste o usuario e segue para login
    if (empty($erros)) {
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        $pdo  = obterConexao();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha)'
        );
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $hashSenha,
        ]);

        mensagemFlash('sucesso', 'Cadastro realizado! Faca login para continuar.');
        redirecionar('/trabalho.pet/public/login.php');
    }
}

$titulo  = 'Cadastro';
$scripts = ['/trabalho.pet/public/js/cadastro.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container-auth" style="margin: 0 auto;">
    <div class="cartao">
        <h1>Criar conta</h1>

        <form id="form-cadastro" class="formulario" method="post" action="" novalidate>
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome"
                       value="<?= escapar($nomeAntigo) ?>"
                       autocomplete="name" required>
                <span class="campo-erro" data-erro="nome">
                    <?= isset($erros['nome']) ? escapar($erros['nome']) : '' ?>
                </span>
            </div>

            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= escapar($emailAntigo) ?>"
                       autocomplete="email" required>
                <span class="campo-erro" data-erro="email">
                    <?= isset($erros['email']) ? escapar($erros['email']) : '' ?>
                </span>
            </div>

            <div class="campo">
                <label for="senha">Senha (minimo 8 caracteres)</label>
                <input type="password" id="senha" name="senha"
                       autocomplete="new-password" required>
                <span class="campo-erro" data-erro="senha">
                    <?= isset($erros['senha']) ? escapar($erros['senha']) : '' ?>
                </span>
            </div>

            <div class="campo">
                <label for="confirmacao_senha">Confirmar senha</label>
                <input type="password" id="confirmacao_senha" name="confirmacao_senha"
                       autocomplete="new-password" required>
                <span class="campo-erro" data-erro="confirmacao_senha">
                    <?= isset($erros['confirmacao_senha']) ? escapar($erros['confirmacao_senha']) : '' ?>
                </span>
            </div>

            <button type="submit" class="botao botao-primario">Cadastrar</button>

            <p class="texto-centro">
                Ja tem conta? <a href="/trabalho.pet/public/login.php">Entrar</a>
            </p>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
