<?php
/**
 * Exclui um registro.
 * Aceita SOMENTE POST. Verifica propriedade via JOIN em pets.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/registros.php';

iniciarSessao();
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('/trabalho.pet/public/registros.php');
}

$usuario     = usuarioAtual();
$usuarioId   = (int) $usuario['id'];
$registroId  = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($registroId <= 0) {
    mensagemFlash('erro', 'Registro invalido.');
    redirecionar('/trabalho.pet/public/registros.php');
}

// Confere propriedade antes de qualquer operacao
$registro = buscarRegistroDoUsuario($registroId, $usuarioId);
if ($registro === null) {
    mensagemFlash('erro', 'Registro nao encontrado.');
    redirecionar('/trabalho.pet/public/registros.php');
}

// DELETE com JOIN como defesa em profundidade - mesmo que alguem chame a
// query crua sem passar por buscarRegistroDoUsuario, o WHERE filtra.
$pdo = obterConexao();
$stmt = $pdo->prepare(
    'DELETE r FROM registros r
       INNER JOIN pets p ON p.id = r.pet_id
     WHERE r.id = :id AND p.usuario_id = :uid'
);
$stmt->execute([
    ':id'  => $registroId,
    ':uid' => $usuarioId,
]);

mensagemFlash('sucesso', 'Registro excluido.');

// Volta filtrado pelo pet, se possivel
redirecionar('/trabalho.pet/public/registros.php?pet_id=' . (int) $registro['pet_id']);
