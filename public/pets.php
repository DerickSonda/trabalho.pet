<?php
/**
 * Lista todos os pets do usuario logado.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pets.php';

iniciarSessao();
exigirLogin();

$usuario   = usuarioAtual();
$usuarioId = (int) $usuario['id'];
$pets      = listarPetsDoUsuario($usuarioId);

$titulo  = 'Meus pets';
$scripts = ['/trabalho.pet/public/js/pets.js'];
require __DIR__ . '/../includes/header.php';
?>

<section class="cabecalho-pagina">
    <h1>Meus pets</h1>
    <a href="/trabalho.pet/public/pet_form.php" class="botao botao-primario">
        + Cadastrar novo pet
    </a>
</section>

<?php if (empty($pets)): ?>
    <div class="cartao estado-vazio">
        <div class="estado-vazio-icone">[ pet ]</div>
        <h2>Voce ainda nao tem pets cadastrados</h2>
        <p>Comece adicionando seu primeiro pet para registrar o dia a dia dele.</p>
        <a href="/trabalho.pet/public/pet_form.php" class="botao botao-primario">
            Cadastrar meu primeiro pet
        </a>
    </div>
<?php else: ?>
    <div class="grade-pets">
        <?php foreach ($pets as $pet): ?>
            <?php
                $idade = calcularIdade($pet['data_nascimento']);
                $temFoto = !empty($pet['foto']);
                $caminhoFoto = $temFoto
                    ? '/trabalho.pet/public/uploads/' . rawurlencode($pet['foto'])
                    : '';
            ?>
            <article class="cartao cartao-pet">
                <?php if ($temFoto): ?>
                    <img class="pet-foto"
                         src="<?= escapar($caminhoFoto) ?>"
                         alt="Foto de <?= escapar($pet['nome']) ?>">
                <?php else: ?>
                    <div class="pet-foto pet-foto-placeholder" aria-hidden="true">
                        <span><?= escapar(mb_substr($pet['nome'], 0, 1)) ?></span>
                    </div>
                <?php endif; ?>

                <div class="pet-info">
                    <h2 class="pet-nome"><?= escapar($pet['nome']) ?></h2>
                    <p class="pet-meta">
                        <?= escapar(rotuloEspecie($pet['especie'])) ?>
                        <?php if (!empty($pet['raca'])): ?>
                            - <?= escapar($pet['raca']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($idade !== ''): ?>
                        <p class="pet-meta">Idade: <?= escapar($idade) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($pet['peso'])): ?>
                        <p class="pet-meta">Peso: <?= escapar(number_format((float) $pet['peso'], 2, ',', '.')) ?> kg</p>
                    <?php endif; ?>
                </div>

                <div class="pet-acoes">
                    <a class="botao botao-secundario"
                       href="/trabalho.pet/public/registros.php?pet_id=<?= (int) $pet['id'] ?>">
                        Registros
                    </a>
                    <a class="botao botao-secundario"
                       href="/trabalho.pet/public/pet_form.php?id=<?= (int) $pet['id'] ?>">
                        Editar
                    </a>
                    <form class="form-excluir-pet"
                          method="post"
                          action="/trabalho.pet/public/pet_excluir.php">
                        <input type="hidden" name="id" value="<?= (int) $pet['id'] ?>">
                        <button type="button"
                                class="botao botao-perigo"
                                data-acao-excluir
                                data-nome-pet="<?= escapar($pet['nome']) ?>">
                            Excluir
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
