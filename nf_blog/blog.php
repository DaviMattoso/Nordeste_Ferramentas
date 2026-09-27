<?php
// A página é pública: auth.php apenas inicia a sessão para montar a navbar,
// sem chamar requireLogin(). A conexão é usada para buscar os cards reais.
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/post-utils.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
$posts = [];
$listError = '';
try {
    // INNER JOIN traz, em uma única consulta, o post, sua categoria e seu autor.
    // Como não há entrada externa nesta listagem, não existem parâmetros para bind.
    $result = $connection->query(
        'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, p.is_featured, '
        . 'c.id AS category_id, c.title AS category_title, '
        . 'u.id AS author_id, u.username, u.first_name, u.last_name, u.avatar '
        . 'FROM posts AS p '
        . 'INNER JOIN categories AS c ON c.id = p.category_id '
        . 'INNER JOIN users AS u ON u.id = p.author_id '
        . 'ORDER BY p.created_at DESC'
    );
    while ($post = $result->fetch_assoc()) {
        // Prepara somente valores de apresentação; os dados originais continuam intactos.
        $authorName = trim($post['first_name'] . ' ' . $post['last_name']);
        $post['author_name'] = $authorName !== '' ? $authorName : $post['username'];
        $post['excerpt'] = postExcerpt($post['body']);
        $post['display_date'] = postDisplayDate($post['created_at']);
        $posts[] = $post;
    }
    $result->free();
} catch (Throwable $exception) {
    // O visitante recebe uma mensagem genérica; detalhes técnicos ficam apenas no log.
    error_log('NF Blog: falha ao listar posts públicos. Código: ' . $exception->getCode());
    $listError = 'Não foi possível carregar os posts. Tente novamente.';
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>NF Blog</title>

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <nav>
            <div class="container nav__container">
                <a href="index.php" class="nav__logo">
                    <img
                        src="./Images/logo.png"
                        alt="Logo NFB"
                        class="nav__logo-image"
                    />
                    <span>NF BLOG</span>
                </a>
                <ul class="nav__items">
                    <li><a href="blog.php">Posts</a></li>
                    <li><a href="about.php">Sobre</a></li>
                    <li><a href="services.php">Serviços</a></li>
                    <li><a href="contact.php">Contato</a></li>
                    <?php if (!isLoggedIn()): ?>
                    <li><a href="signin.php">Signin</a></li>
                    <?php else: ?>
                    <li class="nav__profile">
                        <div class="avatar">
                            <img src="<?= authEscape(authAvatar()) ?>" alt="<?= authEscape($_SESSION['username'] ?? '') ?>" />
                        </div>
                        <ul>
                            <li><a href="admin/dashboard.php">Dashboard</a></li>
                            <li>
                                <form action="logout.php" method="POST" class="logout__form">
                                    <?= csrfField() ?>
                                    <button type="submit" class="logout__button">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <button id="open__nav-btn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="close__nav-btn">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>
        </nav>

        <section class="search__bar">

            <form action="search.php" method="GET" class="container search__bar-container">
                <div>
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="q" maxlength="200" placeholder="Pesquisar" />
                </div>
                <button type="submit" class="btn">Go</button>
            </form>
        </section>

        <section class="posts">
            <div class="container posts__container">
                <?php if ($listError !== ''): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($listError) ?></p>
                </div>
                <?php elseif (!$posts): ?>
                <p>Ainda não existem posts publicados.</p>
                <?php else: ?>
                <?php foreach ($posts as $post): ?>
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
                                <img src="<?= authEscape(ROOT_URL . ltrim($post['avatar'] ?: 'Images/avatar2.jpg', '/')) ?>" alt="<?= authEscape($post['author_name']) ?>" />
                            </div>

                            <div class="post__author-info">
                                <h5>Por: <?= authEscape($post['author_name']) ?></h5>
                                <small><?= authEscape($post['display_date']) ?></small>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

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
