<?php
/**
 * Exclui um pet do usuario logado.
 *
 * Aceita SOMENTE POST: GET poderia ser disparado por <img src> ou link
 * acidental e a exclusao e destrutiva. O CASCADE da FK ja remove os
 * registros vinculados.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pets.php';

iniciarSessao();
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('/trabalho.pet/public/pets.php');
}

$usuario   = usuarioAtual();
$usuarioId = (int) $usuario['id'];
$petId     = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($petId <= 0) {
    mensagemFlash('erro', 'Pet invalido.');
    redirecionar('/trabalho.pet/public/pets.php');
}

// buscarPetDoUsuario garante que o pet pertence ao usuario logado.
// Sem isso, alguem poderia POSTar id de outro usuario.
$pet = buscarPetDoUsuario($petId, $usuarioId);
if ($pet === null) {
    mensagemFlash('erro', 'Pet nao encontrado.');
    redirecionar('/trabalho.pet/public/pets.php');
}

$pdo = obterConexao();
$stmt = $pdo->prepare('DELETE FROM pets WHERE id = :id AND usuario_id = :uid');
$stmt->execute([
    ':id'  => $petId,
    ':uid' => $usuarioId,
]);

// Apos remover do banco, apaga a foto do disco se existir
if (!empty($pet['foto'])) {
    $arquivo = __DIR__ . '/uploads/' . $pet['foto'];
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

mensagemFlash('sucesso', 'Pet "' . $pet['nome'] . '" excluido.');
redirecionar('/trabalho.pet/public/pets.php');
