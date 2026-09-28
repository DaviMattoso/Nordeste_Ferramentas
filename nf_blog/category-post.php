<?php
/**
 * Lista os posts públicos de uma categoria.
 *
 * Distingue categoria inexistente de categoria ainda sem posts e prepara os
 * dados de autor, resumo e data necessários para cada card.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/post-utils.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
$category = null;
$posts = [];
$pageError = '';

/* O parâmetro da URL só entra nas consultas depois de validado como inteiro positivo. */
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(404);
    $pageError = 'Categoria não encontrada.';
} else {
    try {
        /* A primeira consulta separa uma categoria vazia de um ID inexistente. */
        $statement = $connection->prepare('SELECT id, title, description FROM categories WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->bind_result($categoryId, $categoryTitle, $categoryDescription);
        $found = $statement->fetch() === true;
        $statement->close();

        if (!$found) {
            http_response_code(404);
            $pageError = 'Categoria não encontrada.';
        } else {
            $category = [
                'id' => $categoryId,
                'title' => $categoryTitle,
                'description' => $categoryDescription,
            ];

            /* O statement filtra pelo ID validado e o JOIN fornece o autor de cada card. */
            $statement = $connection->prepare(
                'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, p.is_featured, '
                . 'u.id AS author_id, u.username, u.first_name, u.last_name, u.avatar '
                . 'FROM posts AS p '
                . 'INNER JOIN users AS u ON u.id = p.author_id '
                . 'WHERE p.category_id = ? '
                . 'ORDER BY p.created_at DESC'
            );
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->bind_result(
                $postId,
                $postTitle,
                $postBody,
                $postThumbnail,
                $postCreatedAt,
                $postIsFeatured,
                $authorId,
                $username,
                $firstName,
                $lastName,
                $avatar
            );
            while ($statement->fetch()) {
                $authorName = trim($firstName . ' ' . $lastName);
                $posts[] = [
                    'id' => $postId,
                    'title' => $postTitle,
                    'thumbnail' => $postThumbnail,
                    'is_featured' => $postIsFeatured,
                    'author_id' => $authorId,
                    'author_name' => $authorName !== '' ? $authorName : $username,
                    'avatar' => is_string($avatar) && trim($avatar) !== '' ? $avatar : 'Images/avatar2.jpg',
                    'excerpt' => postExcerpt($postBody),
                    'display_date' => postDisplayDate($postCreatedAt),
                ];
            }
            $statement->close();
        }
    } catch (Throwable $exception) {
        /* Registra a falha internamente e preserva uma resposta pública genérica. */
        error_log('NF Blog: falha ao carregar categoria pública. Código: ' . $exception->getCode());
        http_response_code(500);
        $category = null;
        $posts = [];
        $pageError = 'Não foi possível carregar a categoria. Tente novamente.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?= $category ? authEscape($category['title']) . ' | NF Blog' : 'NF Blog' ?></title>

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública com estado de login refletido no perfil. -->
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

        <!-- Identificação da categoria ou mensagem de erro preparada pelo backend. -->
        <section class="search__bar">
            <div class="container">
                <?php if ($pageError !== ''): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($pageError) ?></p>
                </div>
                <?php else: ?>
                <h2><?= authEscape($category['title']) ?></h2>
                <?php if (is_string($category['description']) && trim($category['description']) !== ''): ?>
                <p><?= nl2br(authEscape($category['description'])) ?></p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($category !== null && $pageError === ''): ?>

        <!-- Cards pertencentes exclusivamente à categoria validada. -->
        <section class="posts">
            <div class="container posts__container">
                <?php if (!$posts): ?>
                <p>Ainda não existem posts nesta categoria.</p>
                <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post__thumbnail">
                        <img src="<?= authEscape(ROOT_URL . ltrim($post['thumbnail'], '/')) ?>" alt="<?= authEscape($post['title']) ?>" />
                    </div>

                    <div class="post__info">
                        <a href="category-post.php?id=<?= (int) $category['id'] ?>" class="category__buttons"><?= authEscape($category['title']) ?></a>

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
                <?php endif; ?>
            </div>
        </section>

        <?php endif; ?>

        <!-- Rodapé público com categorias, suporte e navegação. -->
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
