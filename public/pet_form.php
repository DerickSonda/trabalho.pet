<?php
/**
 * Formulario unico de cadastro e edicao de pet.
 *
 * - Sem ?id=X  -> modo cadastro
 * - Com ?id=X  -> modo edicao (somente se o pet pertencer ao usuario)
 *
 * O usuario_id e SEMPRE pego da sessao - nunca de campo hidden.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pets.php';

iniciarSessao();
exigirLogin();

$usuario   = usuarioAtual();
$usuarioId = (int) $usuario['id'];

// Modo: cadastro (id null) ou edicao
$petId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$petAtual = null;
if ($petId > 0) {
    $petAtual = buscarPetDoUsuario($petId, $usuarioId);
    if ($petAtual === null) {
        // Nao existe ou nao pertence ao usuario - finge 404 para nao vazar
        mensagemFlash('erro', 'Pet nao encontrado.');
        redirecionar('/trabalho.pet/public/pets.php');
    }
}
$modoEdicao = $petAtual !== null;

// Valores padrao do form (preserva digitado em caso de erro / preenche em edicao)
$valores = [
    'nome'            => $petAtual['nome']            ?? '',
    'especie'         => $petAtual['especie']         ?? '',
    'raca'            => $petAtual['raca']            ?? '',
    'data_nascimento' => $petAtual['data_nascimento'] ?? '',
    'peso'            => $petAtual['peso']            ?? '',
    'observacoes'     => $petAtual['observacoes']     ?? '',
];
$erros = [];

// Restricoes de upload
const TAMANHO_MAX_FOTO = 2 * 1024 * 1024; // 2 MB
const EXTENSOES_FOTO = ['jpg', 'jpeg', 'png', 'webp'];
const MIMES_FOTO = ['image/jpeg', 'image/png', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sobrescreve valores com o que veio do POST (preserva em re-render se erro)
    $valores['nome']            = trim($_POST['nome']            ?? '');
    $valores['especie']         = trim($_POST['especie']         ?? '');
    $valores['raca']            = trim($_POST['raca']            ?? '');
    $valores['data_nascimento'] = trim($_POST['data_nascimento'] ?? '');
    $valores['peso']            = trim($_POST['peso']            ?? '');
    $valores['observacoes']     = trim($_POST['observacoes']     ?? '');

    // Nome
    if (mb_strlen($valores['nome']) < 2) {
        $erros['nome'] = 'O nome deve ter pelo menos 2 caracteres.';
    }

    // Especie - precisa estar nas opcoes validas
    if (!array_key_exists($valores['especie'], especiesDisponiveis())) {
        $erros['especie'] = 'Escolha uma especie valida.';
    }

    // Data de nascimento (opcional, se preenchida precisa ser valida e nao futura)
    if ($valores['data_nascimento'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $valores['data_nascimento']);
        if (!$d || $d->format('Y-m-d') !== $valores['data_nascimento']) {
            $erros['data_nascimento'] = 'Data de nascimento invalida.';
        } elseif ($d > new DateTime('today')) {
            $erros['data_nascimento'] = 'Data de nascimento nao pode ser no futuro.';
        }
    }

    // Peso (opcional, se preenchido valida intervalo)
    $pesoNumerico = null;
    if ($valores['peso'] !== '') {
        // Aceita virgula como separador decimal
        $pesoNormalizado = str_replace(',', '.', $valores['peso']);
        if (!is_numeric($pesoNormalizado)) {
            $erros['peso'] = 'Peso deve ser um numero.';
        } else {
            $pesoNumerico = (float) $pesoNormalizado;
            if ($pesoNumerico < 0.1 || $pesoNumerico > 200) {
                $erros['peso'] = 'Peso deve estar entre 0,1 e 200 kg.';
            }
        }
    }

    // Foto (opcional)
    $nomeFotoNova = null;
    $temUploadFoto = isset($_FILES['foto'])
        && is_array($_FILES['foto'])
        && (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($temUploadFoto) {
        $arq = $_FILES['foto'];
        $erroUpload = (int) $arq['error'];

        if ($erroUpload !== UPLOAD_ERR_OK) {
            $erros['foto'] = 'Falha no envio da foto (codigo ' . $erroUpload . ').';
        } elseif ($arq['size'] > TAMANHO_MAX_FOTO) {
            $erros['foto'] = 'A foto deve ter no maximo 2 MB.';
        } else {
            // Extensao
            $ext = strtolower(pathinfo($arq['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, EXTENSOES_FOTO, true)) {
                $erros['foto'] = 'Use uma imagem JPG, PNG ou WEBP.';
            } else {
                // MIME real (lendo o arquivo, nao o que o browser disse)
                $mimeReal = function_exists('mime_content_type')
                    ? mime_content_type($arq['tmp_name'])
                    : null;
                if ($mimeReal === null || !in_array($mimeReal, MIMES_FOTO, true)) {
                    $erros['foto'] = 'O arquivo nao parece ser uma imagem valida.';
                } else {
                    // Nome unico - evita colisao e nao vaza nome original
                    $nomeFotoNova = uniqid('pet_', true) . '.' . $ext;
                }
            }
        }
    }

    // Sem erros: persiste
    if (empty($erros)) {
        $pdo = obterConexao();

        // Move o arquivo (se houver) so DEPOIS da validacao - antes do INSERT/UPDATE
        $pastaUploads = __DIR__ . '/uploads';
        if ($nomeFotoNova !== null) {
            $destino = $pastaUploads . DIRECTORY_SEPARATOR . $nomeFotoNova;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $erros['foto'] = 'Nao foi possivel salvar a foto. Tente novamente.';
            }
        }
    }

    // Reverifica erros (move_uploaded_file pode ter falhado acima)
    if (empty($erros)) {
        $pdo = obterConexao();

        if ($modoEdicao) {
            // Em edicao, decide qual foto vai ficar gravada
            $fotoFinal = $nomeFotoNova ?? $petAtual['foto']; // mantem a antiga se nao houve upload

            $stmt = $pdo->prepare(
                'UPDATE pets
                    SET nome            = :nome,
                        especie         = :especie,
                        raca            = :raca,
                        data_nascimento = :nascimento,
                        peso            = :peso,
                        foto            = :foto,
                        observacoes     = :obs
                  WHERE id = :id AND usuario_id = :uid'
            );
            $stmt->execute([
                ':nome'       => $valores['nome'],
                ':especie'    => $valores['especie'],
                ':raca'       => $valores['raca'] !== '' ? $valores['raca'] : null,
                ':nascimento' => $valores['data_nascimento'] !== '' ? $valores['data_nascimento'] : null,
                ':peso'       => $pesoNumerico,
                ':foto'       => $fotoFinal,
                ':obs'        => $valores['observacoes'] !== '' ? $valores['observacoes'] : null,
                ':id'         => (int) $petAtual['id'],
                ':uid'        => $usuarioId,
            ]);

            // Se enviou foto nova com sucesso, apaga a antiga
            if ($nomeFotoNova !== null && !empty($petAtual['foto'])) {
                $arquivoAntigo = $pastaUploads . DIRECTORY_SEPARATOR . $petAtual['foto'];
                if (is_file($arquivoAntigo)) {
                    @unlink($arquivoAntigo);
                }
            }

            mensagemFlash('sucesso', 'Pet atualizado com sucesso.');
        } else {
            // Cadastro - usuario_id SEMPRE da sessao
            $stmt = $pdo->prepare(
                'INSERT INTO pets
                    (usuario_id, nome, especie, raca, data_nascimento, peso, foto, observacoes)
                 VALUES
                    (:uid, :nome, :especie, :raca, :nascimento, :peso, :foto, :obs)'
            );
            $stmt->execute([
                ':uid'        => $usuarioId,
                ':nome'       => $valores['nome'],
                ':especie'    => $valores['especie'],
                ':raca'       => $valores['raca'] !== '' ? $valores['raca'] : null,
                ':nascimento' => $valores['data_nascimento'] !== '' ? $valores['data_nascimento'] : null,
                ':peso'       => $pesoNumerico,
                ':foto'       => $nomeFotoNova,
                ':obs'        => $valores['observacoes'] !== '' ? $valores['observacoes'] : null,
            ]);

            mensagemFlash('sucesso', 'Pet cadastrado com sucesso.');
        }

        redirecionar('/trabalho.pet/public/pets.php');
    }
}

$titulo  = $modoEdicao ? 'Editar pet' : 'Cadastrar pet';
$scripts = ['/trabalho.pet/public/js/pet_form.js'];
require __DIR__ . '/../includes/header.php';

$especies = especiesDisponiveis();
?>

<div class="cartao">
    <h1><?= $modoEdicao ? 'Editar pet' : 'Cadastrar pet' ?></h1>

    <form id="form-pet"
          class="formulario"
          method="post"
          action=""
          enctype="multipart/form-data"
          novalidate>

        <div class="campo">
            <label for="nome">Nome <span class="obrig">*</span></label>
            <input type="text" id="nome" name="nome" required
                   value="<?= escapar($valores['nome']) ?>">
            <span class="campo-erro" data-erro="nome">
                <?= isset($erros['nome']) ? escapar($erros['nome']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="especie">Especie <span class="obrig">*</span></label>
            <select id="especie" name="especie" required>
                <option value="">Selecione...</option>
                <?php foreach ($especies as $valor => $rotulo): ?>
                    <option value="<?= escapar($valor) ?>"
                        <?= $valores['especie'] === $valor ? 'selected' : '' ?>>
                        <?= escapar($rotulo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo-erro" data-erro="especie">
                <?= isset($erros['especie']) ? escapar($erros['especie']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="raca">Raca</label>
            <input type="text" id="raca" name="raca"
                   value="<?= escapar($valores['raca']) ?>">
        </div>

        <div class="campo">
            <label for="data_nascimento">Data de nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento"
                   value="<?= escapar($valores['data_nascimento']) ?>"
                   max="<?= escapar(date('Y-m-d')) ?>">
            <span class="campo-erro" data-erro="data_nascimento">
                <?= isset($erros['data_nascimento']) ? escapar($erros['data_nascimento']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="peso">Peso (kg)</label>
            <input type="number" id="peso" name="peso"
                   step="0.01" min="0.1" max="200"
                   value="<?= escapar((string) $valores['peso']) ?>">
            <span class="campo-erro" data-erro="peso">
                <?= isset($erros['peso']) ? escapar($erros['peso']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="foto">Foto (JPG, PNG ou WEBP, ate 2 MB)</label>
            <?php if ($modoEdicao && !empty($petAtual['foto'])): ?>
                <p class="texto-meta">
                    Foto atual:
                    <a href="/trabalho.pet/public/uploads/<?= escapar(rawurlencode($petAtual['foto'])) ?>"
                       target="_blank" rel="noopener">
                        ver
                    </a>
                    (envie outra para substituir)
                </p>
            <?php endif; ?>
            <input type="file" id="foto" name="foto"
                   accept="image/jpeg,image/png,image/webp">
            <span class="campo-erro" data-erro="foto">
                <?= isset($erros['foto']) ? escapar($erros['foto']) : '' ?>
            </span>
        </div>

        <div class="campo">
            <label for="observacoes">Observacoes</label>
            <textarea id="observacoes" name="observacoes" rows="3"><?= escapar($valores['observacoes']) ?></textarea>
        </div>

        <div class="linha-acoes">
            <a href="/trabalho.pet/public/pets.php" class="botao botao-secundario">Cancelar</a>
            <button type="submit" class="botao botao-primario">
                <?= $modoEdicao ? 'Salvar alteracoes' : 'Cadastrar' ?>
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
