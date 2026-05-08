<?php
/**
 * Formulario unico de cadastro/edicao de registro.
 *
 * - Sem ?id=X        -> modo cadastro
 * - Com ?id=X        -> modo edicao (somente se for de pet do usuario)
 * - Sem id e ?pet_id -> pre-seleciona o pet no select
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pets.php';
require_once __DIR__ . '/../includes/registros.php';

iniciarSessao();
exigirLogin();

$usuario   = usuarioAtual();
$usuarioId = (int) $usuario['id'];
$pets      = listarPetsDoUsuario($usuarioId);
$tipos     = tiposDisponiveis();

// Sem pets nao da pra criar registro - manda pra cadastrar pet primeiro
if (empty($pets)) {
    mensagemFlash('erro', 'Cadastre um pet antes de criar registros.');
    redirecionar('/trabalho.pet/public/pets.php');
}

$registroId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$registroAtual = null;
if ($registroId > 0) {
    $registroAtual = buscarRegistroDoUsuario($registroId, $usuarioId);
    if ($registroAtual === null) {
        mensagemFlash('erro', 'Registro nao encontrado.');
        redirecionar('/trabalho.pet/public/registros.php');
    }
}
$modoEdicao = $registroAtual !== null;

// Pre-selecao de pet via ?pet_id (so faz sentido em cadastro)
$petPreSelecionado = (!$modoEdicao && isset($_GET['pet_id']))
    ? (int) $_GET['pet_id']
    : 0;

// data_hora "agora" no formato datetime-local
$agora = (new DateTime('now'))->format('Y-m-d\TH:i');

// Valores iniciais (preserva digitado em re-render apos erro)
$valores = [
    'pet_id'    => (string) ($registroAtual['pet_id'] ?? $petPreSelecionado ?: ''),
    'tipo'      => $registroAtual['tipo']      ?? '',
    'data_hora' => !empty($registroAtual['data_hora'])
        ? date('Y-m-d\TH:i', strtotime($registroAtual['data_hora']))
        : $agora,
    'descricao' => $registroAtual['descricao'] ?? '',
    'custo'     => $registroAtual['custo']     ?? '',
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['pet_id']    = trim($_POST['pet_id']    ?? '');
    $valores['tipo']      = trim($_POST['tipo']      ?? '');
    $valores['data_hora'] = trim($_POST['data_hora'] ?? '');
    $valores['descricao'] = trim($_POST['descricao'] ?? '');
    $valores['custo']     = trim($_POST['custo']     ?? '');

    // Pet - obrigatorio + tem que ser do usuario
    $petIdInt = (int) $valores['pet_id'];
    $petAlvo  = $petIdInt > 0 ? buscarPetDoUsuario($petIdInt, $usuarioId) : null;
    if ($petAlvo === null) {
        $erros['pet_id'] = 'Selecione um pet valido.';
    }

    // Tipo - obrigatorio + dentro do enum
    if (!array_key_exists($valores['tipo'], $tipos)) {
        $erros['tipo'] = 'Escolha um tipo valido.';
    }

    // Data/hora - obrigatorio
    $dataHoraSql = null;
    if ($valores['data_hora'] === '') {
        $erros['data_hora'] = 'Informe a data e hora.';
    } else {
        // datetime-local manda "Y-m-d\TH:i"; tentamos interpretar
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $valores['data_hora']);
        if (!$dt) {
            // Alguns navegadores podem incluir segundos
            $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $valores['data_hora']);
        }
        if (!$dt) {
            $erros['data_hora'] = 'Data/hora invalida.';
        } else {
            $dataHoraSql = $dt->format('Y-m-d H:i:s');
        }
    }

    // Custo - opcional
    $custoNumerico = null;
    if ($valores['custo'] !== '') {
        $custoNormalizado = str_replace(',', '.', $valores['custo']);
        if (!is_numeric($custoNormalizado)) {
            $erros['custo'] = 'Custo deve ser um numero.';
        } else {
            $custoNumerico = (float) $custoNormalizado;
            if ($custoNumerico < 0) {
                $erros['custo'] = 'Custo nao pode ser negativo.';
            }
        }
    }

    if (empty($erros)) {
        $pdo = obterConexao();

        if ($modoEdicao) {
            // Em edicao, usa o id do registro mas reconfere a propriedade
            // via JOIN no UPDATE: so atualiza se o pet alvo for do usuario.
            $stmt = $pdo->prepare(
                'UPDATE registros r
                   INNER JOIN pets p ON p.id = r.pet_id
                    SET r.pet_id    = :pet_id,
                        r.tipo      = :tipo,
                        r.data_hora = :data_hora,
                        r.descricao = :descricao,
                        r.custo     = :custo
                  WHERE r.id = :id AND p.usuario_id = :uid'
            );
            $stmt->execute([
                ':pet_id'    => $petIdInt,
                ':tipo'      => $valores['tipo'],
                ':data_hora' => $dataHoraSql,
                ':descricao' => $valores['descricao'] !== '' ? $valores['descricao'] : null,
                ':custo'     => $custoNumerico,
                ':id'        => (int) $registroAtual['id'],
                ':uid'       => $usuarioId,
            ]);
            mensagemFlash('sucesso', 'Registro atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO registros (pet_id, tipo, data_hora, descricao, custo)
                 VALUES (:pet_id, :tipo, :data_hora, :descricao, :custo)'
            );
            $stmt->execute([
                ':pet_id'    => $petIdInt,
                ':tipo'      => $valores['tipo'],
                ':data_hora' => $dataHoraSql,
                ':descricao' => $valores['descricao'] !== '' ? $valores['descricao'] : null,
                ':custo'     => $custoNumerico,
            ]);
            mensagemFlash('sucesso', 'Registro criado com sucesso.');
        }

        redirecionar('/trabalho.pet/public/registros.php?pet_id=' . $petIdInt);
    }
}

$titulo  = $modoEdicao ? 'Editar registro' : 'Novo registro';
$scripts = ['/trabalho.pet/public/js/registro_form.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="cartao">
    <h1><?= $modoEdicao ? 'Editar registro' : 'Novo registro' ?></h1>

    <form id="form-registro" class="formulario" method="post" action="" novalidate>
        <div class="campo">
            <label for="pet_id">Pet <span class="obrig">*</span></label>
            <select id="pet_id" name="pet_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($pets as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= (string) $p['id'] === $valores['pet_id'] ? 'selected' : '' ?>>
                        <?= escapar($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo-erro" data-erro="pet_id">
                <?= isset($erros['pet_id']) ? escapar($erros['pet_id']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="tipo">Tipo <span class="obrig">*</span></label>
            <select id="tipo" name="tipo" required>
                <option value="">Selecione...</option>
                <?php foreach ($tipos as $valor => $rotulo): ?>
                    <option value="<?= escapar($valor) ?>"
                        <?= $valores['tipo'] === $valor ? 'selected' : '' ?>>
                        <?= escapar($rotulo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo-erro" data-erro="tipo">
                <?= isset($erros['tipo']) ? escapar($erros['tipo']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="data_hora">Data/Hora <span class="obrig">*</span></label>
            <input type="datetime-local" id="data_hora" name="data_hora"
                   value="<?= escapar($valores['data_hora']) ?>" required>
            <span class="campo-erro" data-erro="data_hora">
                <?= isset($erros['data_hora']) ? escapar($erros['data_hora']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="custo">Custo (R$)</label>
            <input type="number" id="custo" name="custo"
                   step="0.01" min="0"
                   value="<?= escapar((string) $valores['custo']) ?>">
            <span class="campo-erro" data-erro="custo">
                <?= isset($erros['custo']) ? escapar($erros['custo']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="descricao">Descricao</label>
            <textarea id="descricao" name="descricao" rows="3"><?= escapar($valores['descricao']) ?></textarea>
        </div>

        <div class="linha-acoes">
            <a href="/trabalho.pet/public/registros.php" class="botao botao-secundario">Cancelar</a>
            <button type="submit" class="botao botao-primario">
                <?= $modoEdicao ? 'Salvar alteracoes' : 'Criar registro' ?>
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
