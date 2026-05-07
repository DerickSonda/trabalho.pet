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
        <script src="<?= escapar($caminhoScript) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
