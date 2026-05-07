<?php
/**
 * Helpers de dominio para pets: rotulos, idade e busca segura.
 */

require_once __DIR__ . '/../config/conexao.php';

/**
 * Mapa especie -> rotulo amigavel para exibicao.
 * As chaves precisam casar com o ENUM da tabela `pets`.
 */
function especiesDisponiveis(): array
{
    return [
        'cao'     => 'Cao',
        'gato'    => 'Gato',
        'coelho'  => 'Coelho',
        'ave'     => 'Ave',
        'outro'   => 'Outro',
    ];
}

/**
 * Rotulo legivel para uma especie. Cai em "Outro" se nao bater.
 */
function rotuloEspecie(?string $especie): string
{
    $mapa = especiesDisponiveis();
    return $mapa[$especie] ?? 'Outro';
}

/**
 * Mapa tipo -> rotulo amigavel para registros (usado no dashboard).
 */
function rotuloTipoRegistro(?string $tipo): string
{
    $mapa = [
        'alimentacao' => 'Alimentacao',
        'limpeza'     => 'Limpeza',
        'veterinario' => 'Veterinario',
        'medicacao'   => 'Medicacao',
        'banho'       => 'Banho',
        'outro'       => 'Outro',
    ];
    return $mapa[$tipo] ?? 'Outro';
}

/**
 * Calcula idade aproximada em "X anos e Y meses" a partir de uma data de
 * nascimento. Retorna string vazia se a data nao for informada/invalida.
 */
function calcularIdade(?string $dataNascimento): string
{
    if ($dataNascimento === null || $dataNascimento === '') {
        return '';
    }

    try {
        $nasc = new DateTime($dataNascimento);
        $hoje = new DateTime('today');
    } catch (Exception $e) {
        return '';
    }

    if ($nasc > $hoje) {
        return '';
    }

    $diff  = $nasc->diff($hoje);
    $anos  = (int) $diff->y;
    $meses = (int) $diff->m;

    if ($anos === 0 && $meses === 0) {
        // Filhote bem novo - mostra dias
        return $diff->d . ($diff->d === 1 ? ' dia' : ' dias');
    }

    $partes = [];
    if ($anos > 0) {
        $partes[] = $anos . ($anos === 1 ? ' ano' : ' anos');
    }
    if ($meses > 0) {
        $partes[] = $meses . ($meses === 1 ? ' mes' : ' meses');
    }
    return implode(' e ', $partes);
}

/**
 * Busca um pet pelo id GARANTINDO que ele pertence ao usuario informado.
 * Retorna null se o pet nao existir OU pertencer a outro usuario - assim
 * uma unica checagem cobre tanto "nao existe" quanto "nao autorizado".
 */
function buscarPetDoUsuario(int $petId, int $usuarioId): ?array
{
    $pdo = obterConexao();
    $stmt = $pdo->prepare(
        'SELECT id, usuario_id, nome, especie, raca, data_nascimento,
                peso, foto, observacoes, criado_em
         FROM pets
         WHERE id = :id AND usuario_id = :usuario_id
         LIMIT 1'
    );
    $stmt->execute([
        ':id'         => $petId,
        ':usuario_id' => $usuarioId,
    ]);
    $pet = $stmt->fetch();
    return $pet ?: null;
}

/**
 * Lista todos os pets de um usuario, mais recentes primeiro.
 */
function listarPetsDoUsuario(int $usuarioId): array
{
    $pdo = obterConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, especie, raca, data_nascimento, peso, foto
         FROM pets
         WHERE usuario_id = :usuario_id
         ORDER BY criado_em DESC, id DESC'
    );
    $stmt->execute([':usuario_id' => $usuarioId]);
    return $stmt->fetchAll();
}
