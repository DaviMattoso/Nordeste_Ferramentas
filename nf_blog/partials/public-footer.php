<?php
/**
 * Rodapé público compartilhado.
 *
 * Quando a página já conectou ao banco, as categorias vêm da consulta atual
 * em vez de uma lista estática. O script de navegação recebe versionamento
 * por filemtime para evitar cache após alterações.
 */

$blogBase = $blogBase ?? '';
if (!isset($publicCategories) || !is_array($publicCategories)) {
    $publicCategories = [];
    if (isset($connection) && $connection instanceof mysqli) {
        require_once __DIR__ . '/../config/category-utils.php';
        $publicCategories = publicCategories($connection);
    }
}
$mainJsFile = __DIR__ . '/../js/main.js';
?>
        <footer>
            <div class="footer__socials">
                <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer"
                    ><i class="fa-brands fa-youtube"></i
                ></a>

                <a href="https://www.facebook.com/?locale=pt_BR" target="_blank" rel="noopener noreferrer"
                    ><i class="fa-brands fa-facebook"></i
                ></a>

                <a href="https://x.com/?lang=pt" target="_blank" rel="noopener noreferrer"
                    ><i class="fa-brands fa-twitter"></i
                ></a>

                <a href="https://pt.linkedin.com/" target="_blank" rel="noopener noreferrer"
                    ><i class="fa-brands fa-linkedin"></i
                ></a>

                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer"
                    ><i class="fa-brands fa-instagram"></i
                ></a>
            </div>

            <div class="container footer__container">
                <article>
                    <h4>Categorias</h4>
                    <ul>
                        <?php foreach ($publicCategories as $publicCategory): ?>
                        <li><a href="<?= authEscape($blogBase) ?>category-post.php?id=<?= (int) $publicCategory['id'] ?>"><?= authEscape((string) $publicCategory['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </article>

                <article>
                    <h4>Suporte</h4>
                    <ul>
                        <li>
                            <a
                                href="https://wa.me/5581995607222"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Suporte Online</a
                            >
                        </li>
                        <li><a href="<?= authEscape($blogBase) ?>contact.php">Números</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>contact.php">Email</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>contact.php">Localização</a></li>
                    </ul>
                </article>

                <article>
                    <h4>Navegação</h4>
                    <ul>
                        <li><a href="<?= authEscape($blogBase) ?>index.php">Home</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>blog.php">Blog</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>about.php">Sobre</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>services.php">Serviços</a></li>
                        <li><a href="<?= authEscape($blogBase) ?>contact.php">Contato</a></li>
                    </ul>
                </article>

                <div class="footer__copyright">
                    <small>
                        &copy; 2026 NF Ferramentas - Todos os direitos
                        reservados
                    </small>
                </div>
            </div>
        </footer>

        <script src="<?= authEscape($blogBase) ?>js/main.js?v=<?= filemtime($mainJsFile) ?>"></script>
