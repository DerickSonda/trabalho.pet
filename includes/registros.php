<?php
/**
 * Helpers de dominio para registros (alimentacao, banho, vet, etc).
 */

require_once __DIR__ . '/../config/conexao.php';

/**
 * Tipos validos de registro - chaves casam com o ENUM da tabela `registros`.
 * O valor e o rotulo amigavel para exibir.
 */
function tiposDisponiveis(): array
{
    return [
        'alimentacao' => 'Alimentacao',
        'limpeza'     => 'Limpeza',
        'veterinario' => 'Veterinario',
        'medicacao'   => 'Medicacao',
        'banho'       => 'Banho',
        'outro'       => 'Outro',
    ];
}

/**
 * Sufixo da classe CSS do badge para um tipo de registro.
 * Ex.: 'banho' -> 'badge-banho' (classe definida no CSS).
 */
function classeBadgeTipo(?string $tipo): string
{
    $tipos = array_keys(tiposDisponiveis());
    if (!in_array($tipo, $tipos, true)) {
        $tipo = 'outro';
    }
    return 'badge-' . $tipo;
}

/**
 * Busca um registro pelo id, garantindo que ele pertence a um pet do
 * usuario informado. Retorna null se nao existir ou for de outro usuario.
 */
function buscarRegistroDoUsuario(int $registroId, int $usuarioId): ?array
{
    $pdo = obterConexao();
    $stmt = $pdo->prepare(
        'SELECT r.id, r.pet_id, r.tipo, r.data_hora, r.descricao, r.custo,
                p.nome AS pet_nome
         FROM registros r
         INNER JOIN pets p ON p.id = r.pet_id
         WHERE r.id = :id AND p.usuario_id = :uid
         LIMIT 1'
    );
    $stmt->execute([
        ':id'  => $registroId,
        ':uid' => $usuarioId,
    ]);
    $registro = $stmt->fetch();
    return $registro ?: null;
}
