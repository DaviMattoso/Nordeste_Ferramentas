<?php
// A homepage é pública: auth.php inicia a sessão para a navbar sem exigir login.
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/post-utils.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
$featuredPost = null;
$recentPosts = [];
$loadError = '';

// Centraliza os valores derivados usados tanto no destaque quanto nos cards recentes.
$preparePublicPost = static function (array $post): array {
    $authorName = trim($post['first_name'] . ' ' . $post['last_name']);
    $post['author_name'] = $authorName !== '' ? $authorName : $post['username'];
    $post['avatar'] = is_string($post['avatar']) && trim($post['avatar']) !== ''
        ? $post['avatar'] : 'Images/avatar2.jpg';
    $post['excerpt'] = postExcerpt($post['body']);
    $post['display_date'] = postDisplayDate($post['created_at']);
    return $post;
};

try {
    // As duas consultas usam os mesmos campos e JOINs. Não há input externo,
    // portanto uma consulta direta é segura e mais simples que um statement preparado.
    $postSelect = 'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, p.is_featured, '
        . 'c.id AS category_id, c.title AS category_title, '
        . 'u.id AS author_id, u.username, u.first_name, u.last_name, u.avatar '
        . 'FROM posts AS p '
        . 'INNER JOIN categories AS c ON c.id = p.category_id '
        . 'INNER JOIN users AS u ON u.id = p.author_id';

    // Se houver vários destaques, somente o mais recente ocupa a área principal.
    $result = $connection->query(
        $postSelect . ' WHERE p.is_featured = 1 ORDER BY p.created_at DESC LIMIT 1'
    );
    $featuredRow = $result->fetch_assoc();
    $result->free();
    if ($featuredRow !== null) {
        $featuredPost = $preparePublicPost($featuredRow);
    }

    // O layout possui sete cards. São carregados oito candidatos para ainda restarem
    // sete quando o destaque principal também estiver entre os posts mais recentes.
    $recentCandidates = [];
    $result = $connection->query($postSelect . ' ORDER BY p.created_at DESC LIMIT 8');
    while ($post = $result->fetch_assoc()) {
        $recentCandidates[] = $preparePublicPost($post);
    }
    $result->free();

    // Sem nenhum is_featured, o post geral mais recente assume o destaque.
    if ($featuredPost === null && $recentCandidates) {
        $featuredPost = array_shift($recentCandidates);
    }

    // Evita repetir o destaque e mantém no máximo os sete cards do design original.
    foreach ($recentCandidates as $post) {
        if ($featuredPost !== null && (int) $post['id'] === (int) $featuredPost['id']) {
            continue;
        }
        $recentPosts[] = $post;
        if (count($recentPosts) === 7) {
            break;
        }
    }
} catch (Throwable $exception) {
    // O visitante recebe uma mensagem genérica; o detalhe técnico fica apenas no log.
    error_log('NF Blog: falha ao carregar posts da homepage. Código: ' . $exception->getCode());
    http_response_code(500);
    $featuredPost = null;
    $recentPosts = [];
    $loadError = 'Não foi possível carregar os posts. Tente novamente.';
}

// O header compartilhado monta o início do HTML e a navbar existente.
require_once __DIR__ . '/admin/partials/header.php';
?>

        <section class="featured">

            <div class="container featured__container">
                <?php if ($loadError !== ''): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($loadError) ?></p>
                </div>
                <?php elseif ($featuredPost === null): ?>
                <p>Ainda não existem posts publicados.</p>
                <?php else: ?>

                <div class="post__thumbnail">

                    <img src="<?= authEscape(ROOT_URL . ltrim($featuredPost['thumbnail'], '/')) ?>" alt="<?= authEscape($featuredPost['title']) ?>" />
                </div>

                <div class="post__info">

                    <a href="category-post.php?id=<?= (int) $featuredPost['category_id'] ?>" class="category__buttons"><?= authEscape($featuredPost['category_title']) ?></a>

                    <h2 class="post__title">

                        <a href="post.php?id=<?= (int) $featuredPost['id'] ?>"><?= authEscape($featuredPost['title']) ?></a>
                    </h2>

                    <p class="post__body">
                        <?= authEscape($featuredPost['excerpt']) ?>
                    </p>

                    <div class="post__author">

                        <div class="post__author-avatar">

                            <img src="<?= authEscape(ROOT_URL . ltrim($featuredPost['avatar'], '/')) ?>" alt="<?= authEscape($featuredPost['author_name']) ?>" />
                        </div>

                        <div class="post__author-info">

                            <h5>Por: <?= authEscape($featuredPost['author_name']) ?></h5>

                            <small><?= authEscape($featuredPost['display_date']) ?></small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($recentPosts): ?>
        <section class="posts">
            <div class="container posts__container">
                <?php foreach ($recentPosts as $post): ?>
                <article class="post">
                    <div class="post__thumbnail">
                        <img src="<?= authEscape(ROOT_URL . ltrim($post['thumbnail'], '/')) ?>" alt="<?= authEscape($post['title']) ?>" />
                    </div>

                    <div class="post__info">
                        <a href="category-post.php?id=<?= (int) $post['category_id'] ?>" class="category__buttons"><?= authEscape($post['category_title']) ?></a>

                        <h3 class="post__title">
                            <a href="post.php?id=<?= (int) $post['id'] ?>"><?= authEscape($post['title']) ?></a>
                        </h3>

                        <p class="post__body">
                            <?= authEscape($post['excerpt']) ?>
                        </p>

                        <div class="post__author">
                            <div class="post__author-avatar">
                                <img src="<?= authEscape(ROOT_URL . ltrim($post['avatar'], '/')) ?>" alt="<?= authEscape($post['author_name']) ?>" />
                            </div>

                            <div class="post__author-info">
                                <h5>Por: <?= authEscape($post['author_name']) ?></h5>
                                <small><?= authEscape($post['display_date']) ?></small>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="category__buttons-section">
            <div class="container category__buttons-container">
                <?php foreach ($publicCategories as $publicCategory): ?>
                <a href="category-post.php?id=<?= (int) $publicCategory['id'] ?>" class="category__buttons"><?= authEscape((string) $publicCategory['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <footer>
            <div class="footer__socials">
                <a href="https://www.youtube.com/" target="_blank"
                    ><i class="fa-brands fa-youtube"></i
                ></a>

                <a href="https://www.facebook.com/?locale=pt_BR" target="_blank"
                    ><i class="fa-brands fa-facebook"></i
                ></a>

                <a href="https://x.com/?lang=pt" target="_blank"
                    ><i class="fa-brands fa-twitter"></i
                ></a>

                <a href="https://pt.linkedin.com/" target="_blank"
                    ><i class="fa-brands fa-linkedin"></i
                ></a>

                <a href="https://www.instagram.com/" target="_blank"
                    ><i class="fa-brands fa-instagram"></i
                ></a>
            </div>

            <div class="container footer__container">
                <article>
                    <h4>Categorias</h4>
                    <ul>
                        <?php foreach ($publicCategories as $publicCategory): ?>
                        <li><a href="category-post.php?id=<?= (int) $publicCategory['id'] ?>"><?= authEscape((string) $publicCategory['title']) ?></a></li>
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
                        <li><a href="contact.php">Numeros</a></li>
                        <li><a href="contact.php">Email</a></li>
                        <li><a href="contact.php">Localização</a></li>
                    </ul>
                </article>

                <article>
                    <h4>Navegação</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="about.php">Sobre</a></li>
                        <li><a href="services.php">Serviços</a></li>
                        <li><a href="contact.php">Contato</a></li>
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

        <script src="./js/main.js"></script>
    </body>
</html>
