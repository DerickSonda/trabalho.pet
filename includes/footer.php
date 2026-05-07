<?php
/**
 * Rodape HTML compartilhado.
 *
 * Variaveis esperadas (opcionais):
 *   $scripts  array de caminhos para arquivos JS a serem incluidos.
 *             Ex.: $scripts = ['/trabalho.pet/public/js/login.js'];
 */
$scripts = isset($scripts) && is_array($scripts) ? $scripts : [];
?>
    </main>

    <footer class="rodape">
        <p>Trabalho academico - 2026</p>
    </footer>

    <?php foreach ($scripts as $caminhoScript): ?>
        <?php
            // Cache busting: anexa filemtime na URL para forcar o navegador
            // a baixar a versao nova do JS toda vez que o arquivo for alterado.
            $arquivoFisico = $_SERVER['DOCUMENT_ROOT'] . $caminhoScript;
            $versao = is_file($arquivoFisico) ? filemtime($arquivoFisico) : time();
        ?>
        <script src="<?= escapar($caminhoScript) ?>?v=<?= $versao ?>"></script>
    <?php endforeach; ?>
</body>
</html>
