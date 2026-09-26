<?php
// A página é pública: auth.php inicia a sessão para a navbar, mas não exige login.
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/post-utils.php';

$post = null;
$pageError = '';

// O ID vem da URL e só é aceito quando representa um inteiro positivo.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(404);
    $pageError = 'Post não encontrado.';
} else {
    require_once __DIR__ . '/config/database.php';
    try {
        // O prepared statement mantém o ID separado do SQL. Os JOINs carregam todos
        // os dados de categoria e autor necessários para montar a página completa.
        $statement = $connection->prepare(
            'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, p.updated_at, p.is_featured, '
            . 'c.id AS category_id, c.title AS category_title, '
            . 'u.id AS author_id, u.username, u.first_name, u.last_name, u.avatar '
            . 'FROM posts AS p '
            . 'INNER JOIN categories AS c ON c.id = p.category_id '
            . 'INNER JOIN users AS u ON u.id = p.author_id '
            . 'WHERE p.id = ? LIMIT 1'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->bind_result(
            $postId,
            $postTitle,
            $postBody,
            $postThumbnail,
            $postCreatedAt,
            $postUpdatedAt,
            $postIsFeatured,
            $categoryId,
            $categoryTitle,
            $authorId,
            $username,
            $firstName,
            $lastName,
            $avatar
        );
        $found = $statement->fetch() === true;
        $statement->close();

        if (!$found) {
            http_response_code(404);
            $pageError = 'Post não encontrado.';
        } else {
            // Nome completo é preferido; username cobre cadastros sem nome preenchido.
            $authorName = trim($firstName . ' ' . $lastName);
            $post = [
                'id' => $postId,
                'title' => $postTitle,
                'body' => $postBody,
                'thumbnail' => $postThumbnail,
                'created_at' => $postCreatedAt,
                'updated_at' => $postUpdatedAt,
                'is_featured' => $postIsFeatured,
                'category_id' => $categoryId,
                'category_title' => $categoryTitle,
                'author_id' => $authorId,
                'username' => $username,
                'author_name' => $authorName !== '' ? $authorName : $username,
                'avatar' => is_string($avatar) && trim($avatar) !== '' ? $avatar : 'Images/avatar2.jpg',
                'display_date' => postDisplayDate($postCreatedAt),
            ];
        }
    } catch (Throwable $exception) {
        // Detalhes técnicos ficam no log; o visitante recebe somente uma mensagem segura.
        error_log('NF Blog: falha ao carregar post público. Código: ' . $exception->getCode());
        http_response_code(500);
        $pageError = 'Não foi possível carregar o post. Tente novamente.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?= $post ? authEscape($post['title']) . ' | NF Blog' : 'NF Blog' ?></title>
        <!-- Fav icon -->
        <link rel="icon" href="./Images/favicon.ico" />
        <!-- Custom style css -->
        <link rel="stylesheet" href="./css/style.css" />
        <!-- Font-awesome cdn -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <!-- ======== Colocar o fav icon ======== -->
    <body>
        <!-- ======== Navbar ======== -->
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
                            <li><a href="logout.php">Logout</a></li>
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

        <!-- ======== Single Post ======== -->

        <section class="singlepost">
            <div class="container singlepost__container">
                <?php if ($post === null): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($pageError) ?></p>
                </div>
                <?php else: ?>
                <!-- Categoria -->
                <a href="category-post.php?id=<?= (int) $post['category_id'] ?>" class="category__buttons"><?= authEscape($post['category_title']) ?></a>

                <!-- Título -->
                <h2><?= authEscape($post['title']) ?></h2>

                <!-- Autor -->
                <div class="post__author">
                    <div class="post__author-avatar">
                        <img src="<?= authEscape(ROOT_URL . ltrim($post['avatar'], '/')) ?>" alt="<?= authEscape($post['author_name']) ?>" />
                    </div>

                    <div class="post__author-info">
                        <h5>Por: <a href="blog.php"><?= authEscape($post['author_name']) ?></a></h5>
                        <small>Publicado em: <?= authEscape($post['display_date']) ?></small>
                    </div>
                </div>

                <!-- Imagem -->
                <div class="singlepost__thumbnail">
                    <img src="<?= authEscape(ROOT_URL . ltrim($post['thumbnail'], '/')) ?>" alt="<?= authEscape($post['title']) ?>" />
                </div>

                <!-- Conteúdo: escapa HTML do banco antes de converter quebras de linha. -->
                <p>
                    <?= nl2br(authEscape($post['body'])) ?>
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ======== Footer ======== -->
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
                    <!-- ======================================================
                TODO:
                Adicionar os links das categorias quando as páginas
                individuais estiverem prontas.
            ====================================================== -->
                    <h4>Categorias</h4>
                    <ul>
                        <li><a href="">Ferramentas</a></li>
                        <li><a href="">Construção</a></li>
                        <li><a href="">Marcenaria</a></li>
                        <li><a href="">Seg. no Trabalho</a></li>
                        <li><a href="">Dicas e Tutoriais</a></li>
                        <li><a href="">Maquinaria Pesada</a></li>
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
