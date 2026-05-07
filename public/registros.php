<?php
/**
 * Lista de registros do usuario com filtros por pet, tipo e periodo.
 *
 * Toda query passa por JOIN em pets WHERE pets.usuario_id = :uid -
 * isso garante que o usuario so ve seus proprios dados.
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

// --- Lendo e validando os filtros vindos da query string ---
$filtroPetId = isset($_GET['pet_id']) && $_GET['pet_id'] !== ''
    ? (int) $_GET['pet_id']
    : 0;

$filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
if ($filtroTipo !== '' && !array_key_exists($filtroTipo, $tipos)) {
    $filtroTipo = ''; // tipo invalido = ignora o filtro
}

$filtroDataInicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : '';
$filtroDataFim    = isset($_GET['data_fim'])    ? trim($_GET['data_fim'])    : '';

// Valida formato YYYY-MM-DD; se invalido, ignora silenciosamente
foreach (['filtroDataInicio', 'filtroDataFim'] as $var) {
    if ($$var !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $$var);
        if (!$d || $d->format('Y-m-d') !== $$var) {
            $$var = '';
        }
    }
}

// --- Monta a query dinamicamente ---
$where  = ['p.usuario_id = :uid'];
$params = [':uid' => $usuarioId];

if ($filtroPetId > 0) {
    $where[] = 'r.pet_id = :pet_id';
    $params[':pet_id'] = $filtroPetId;
}
if ($filtroTipo !== '') {
    $where[] = 'r.tipo = :tipo';
    $params[':tipo'] = $filtroTipo;
}
if ($filtroDataInicio !== '') {
    $where[] = 'r.data_hora >= :ini';
    $params[':ini'] = $filtroDataInicio . ' 00:00:00';
}
if ($filtroDataFim !== '') {
    $where[] = 'r.data_hora <= :fim';
    $params[':fim'] = $filtroDataFim . ' 23:59:59';
}

$sql = 'SELECT r.id, r.tipo, r.data_hora, r.descricao, r.custo,
               p.id AS pet_id, p.nome AS pet_nome
        FROM registros r
        INNER JOIN pets p ON p.id = r.pet_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY r.data_hora DESC, r.id DESC';

$pdo = obterConexao();
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

// pet_id pra pre-selecionar no botao "Novo registro"
$petIdParaNovo = $filtroPetId > 0 ? '?pet_id=' . $filtroPetId : '';

$titulo  = 'Registros';
$scripts = ['/trabalho.pet/public/js/registros.js'];
require __DIR__ . '/../includes/header.php';
?>

<section class="cabecalho-pagina">
    <h1>Registros</h1>
    <a href="/trabalho.pet/public/registro_form.php<?= escapar($petIdParaNovo) ?>"
       class="botao botao-primario">
        + Novo registro
    </a>
</section>

<form id="filtros-registros" class="cartao filtros" method="get" action="">
    <div class="campo">
        <label for="filtro-pet">Pet</label>
        <select id="filtro-pet" name="pet_id" data-auto-submit>
            <option value="">Todos</option>
            <?php foreach ($pets as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                    <?= $filtroPetId === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= escapar($p['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="filtro-tipo">Tipo</label>
        <select id="filtro-tipo" name="tipo" data-auto-submit>
            <option value="">Todos</option>
            <?php foreach ($tipos as $valor => $rotulo): ?>
                <option value="<?= escapar($valor) ?>"
                    <?= $filtroTipo === $valor ? 'selected' : '' ?>>
                    <?= escapar($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="filtro-inicio">De</label>
        <input type="date" id="filtro-inicio" name="data_inicio"
               value="<?= escapar($filtroDataInicio) ?>">
    </div>

    <div class="campo">
        <label for="filtro-fim">Ate</label>
        <input type="date" id="filtro-fim" name="data_fim"
               value="<?= escapar($filtroDataFim) ?>">
    </div>

    <div class="filtros-acoes">
        <button type="submit" class="botao botao-primario">Filtrar</button>
        <a href="/trabalho.pet/public/registros.php" class="botao botao-secundario">Limpar</a>
    </div>
</form>

<?php if (empty($pets)): ?>
    <div class="cartao estado-vazio">
        <h2>Cadastre um pet primeiro</h2>
        <p>Voce precisa ter pelo menos um pet para criar registros.</p>
        <a href="/trabalho.pet/public/pet_form.php" class="botao botao-primario">
            Cadastrar pet
        </a>
    </div>
<?php elseif (empty($registros)): ?>
    <div class="cartao estado-vazio">
        <h2>Nenhum registro encontrado</h2>
        <p>Ajuste os filtros ou crie um novo registro.</p>
    </div>
<?php else: ?>
    <div class="cartao tabela-wrap">
        <table class="tabela-registros">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Pet</th>
                    <th>Tipo</th>
                    <th>Descricao</th>
                    <th>Custo</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                    <?php
                        $descricaoTruncada = '';
                        if (!empty($reg['descricao'])) {
                            $descricaoTruncada = mb_strlen($reg['descricao']) > 60
                                ? mb_substr($reg['descricao'], 0, 60) . '...'
                                : $reg['descricao'];
                        }
                    ?>
                    <tr>
                        <td data-rotulo="Data/Hora">
                            <?= escapar(date('d/m/Y H:i', strtotime($reg['data_hora']))) ?>
                        </td>
                        <td data-rotulo="Pet"><?= escapar($reg['pet_nome']) ?></td>
                        <td data-rotulo="Tipo">
                            <span class="badge <?= escapar(classeBadgeTipo($reg['tipo'])) ?>">
                                <?= escapar(rotuloTipoRegistro($reg['tipo'])) ?>
                            </span>
                        </td>
                        <td data-rotulo="Descricao" class="celula-descricao">
                            <?= escapar($descricaoTruncada) ?>
                        </td>
                        <td data-rotulo="Custo">
                            <?php if ($reg['custo'] !== null && $reg['custo'] !== ''): ?>
                                R$ <?= escapar(number_format((float) $reg['custo'], 2, ',', '.')) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td data-rotulo="Acoes" class="celula-acoes">
                            <a class="link-acao"
                               href="/trabalho.pet/public/registro_form.php?id=<?= (int) $reg['id'] ?>">
                                Editar
                            </a>
                            <form method="post"
                                  action="/trabalho.pet/public/registro_excluir.php"
                                  class="form-excluir-registro">
                                <input type="hidden" name="id" value="<?= (int) $reg['id'] ?>">
                                <button type="button"
                                        class="link-acao link-acao-perigo"
                                        data-acao-excluir-registro
                                        data-rotulo="<?= escapar(rotuloTipoRegistro($reg['tipo'])) ?> de <?= escapar($reg['pet_nome']) ?>">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
