<?php
/**
 * Pagina inicial pos-login.
 * Mostra resumo da conta: total de pets, registros do mes, ultimos registros.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pets.php';
require_once __DIR__ . '/../includes/registros.php';

iniciarSessao();
exigirLogin();

$usuario   = usuarioAtual();
$usuarioId = (int) $usuario['id'];
$pdo       = obterConexao();

// Total de pets do usuario
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM pets WHERE usuario_id = :uid');
$stmt->execute([':uid' => $usuarioId]);
$totalPets = (int) ($stmt->fetch()['total'] ?? 0);

// Total de registros do mes corrente (apenas registros de pets do usuario)
$stmt = $pdo->prepare(
    'SELECT COUNT(r.id) AS total
     FROM registros r
     INNER JOIN pets p ON p.id = r.pet_id
     WHERE p.usuario_id = :uid
       AND YEAR(r.data_hora)  = YEAR(CURRENT_DATE)
       AND MONTH(r.data_hora) = MONTH(CURRENT_DATE)'
);
$stmt->execute([':uid' => $usuarioId]);
$totalRegistrosMes = (int) ($stmt->fetch()['total'] ?? 0);

// Ultimos 5 registros (de qualquer pet do usuario)
$stmt = $pdo->prepare(
    'SELECT r.id, r.tipo, r.data_hora, r.descricao, r.custo,
            p.id AS pet_id, p.nome AS pet_nome
     FROM registros r
     INNER JOIN pets p ON p.id = r.pet_id
     WHERE p.usuario_id = :uid
     ORDER BY r.data_hora DESC, r.id DESC
     LIMIT 5'
);
$stmt->execute([':uid' => $usuarioId]);
$ultimosRegistros = $stmt->fetchAll();

$titulo = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<section class="saudacao">
    <h1>Ola, <?= escapar($usuario['nome']) ?> </h1>
    <p>Bem-vindo ao seu Diario de Pets.</p>
</section>

<section class="resumo">
    <a class="cartao cartao-resumo cartao-resumo-link"
       href="/trabalho.pet/public/pets.php"
       title="Ver todos os pets">
        <span class="resumo-rotulo">Pets cadastrados</span>
        <span class="resumo-numero"><?= $totalPets ?></span>
        <span class="resumo-cta">Ver pets &rarr;</span>
    </a>
    <a class="cartao cartao-resumo cartao-resumo-link"
       href="/trabalho.pet/public/registros.php"
       title="Ver todos os registros">
        <span class="resumo-rotulo">Registros este mes</span>
        <span class="resumo-numero"><?= $totalRegistrosMes ?></span>
        <span class="resumo-cta">Ver registros &rarr;</span>
    </a>
</section>

<section class="cartao">
    <h2>Ultimos registros</h2>
    <?php if (empty($ultimosRegistros)): ?>
        <p class="texto-vazio">
            Nenhum registro ainda. Cadastre um pet e comece a anotar o dia a dia dele.
        </p>
    <?php else: ?>
        <ul class="lista-registros">
            <?php foreach ($ultimosRegistros as $reg): ?>
                <li>
                    <a class="item-registro item-registro-link"
                       href="/trabalho.pet/public/registro_form.php?id=<?= (int) $reg['id'] ?>"
                       title="Editar este registro">
                        <div class="item-registro-cabec">
                            <span class="badge <?= escapar(classeBadgeTipo($reg['tipo'])) ?>">
                                <?= escapar(rotuloTipoRegistro($reg['tipo'])) ?>
                            </span>
                            <span class="item-registro-pet">
                                <?= escapar($reg['pet_nome']) ?>
                            </span>
                        </div>
                        <div class="item-registro-meta">
                            <?= escapar(date('d/m/Y H:i', strtotime($reg['data_hora']))) ?>
                            <?php if ($reg['custo'] !== null && $reg['custo'] !== ''): ?>
                                - R$ <?= escapar(number_format((float) $reg['custo'], 2, ',', '.')) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($reg['descricao'])): ?>
                            <p class="item-registro-desc"><?= escapar($reg['descricao']) ?></p>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="texto-centro" style="margin-top: 1.5rem;">
    <a href="/trabalho.pet/public/pets.php" class="botao botao-primario botao-grande">
        Meus pets
    </a>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
