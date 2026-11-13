<?php
/**
 * Exibe um post público individual do NF Blog.
 *
 * Valida o ID da URL, consulta post, categoria e autor em conjunto e prepara
 * metadados de apresentação antes de renderizar o conteúdo completo.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/post-utils.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
$post = null;
$pageError = '';

/* IDs ausentes, não numéricos ou fora da faixa válida são tratados como 404. */
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(404);
    $pageError = 'Post não encontrado.';
} else {
    try {
        /*
         * O ID permanece separado do SQL. Os JOINs carregam categoria e autor junto
         * ao post para evitar consultas adicionais durante a renderização.
         */
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
            /* O username funciona como fallback quando o nome completo estiver vazio. */
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
        /* Registra a falha internamente sem expor consulta ou banco ao visitante. */
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

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública com estado de login refletido no perfil. -->
        <?php require __DIR__ . '/partials/public-nav.php'; ?>

        <!-- Conteúdo do post ou mensagem correspondente ao status HTTP definido no PHP. -->
        <section class="singlepost">
            <div class="container singlepost__container">
                <?php if ($post === null): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($pageError) ?></p>
                </div>
                <?php else: ?>

                <a href="category-post.php?id=<?= (int) $post['category_id'] ?>" class="category__buttons"><?= authEscape($post['category_title']) ?></a>

                <h2><?= authEscape($post['title']) ?></h2>

                <div class="post__author">
                    <div class="post__author-avatar">
                        <img src="<?= authEscape(ROOT_URL . ltrim($post['avatar'], '/')) ?>" alt="<?= authEscape($post['author_name']) ?>" />
                    </div>

                    <div class="post__author-info">
                        <h5>Por: <a href="blog.php"><?= authEscape($post['author_name']) ?></a></h5>
                        <small>Publicado em: <?= authEscape($post['display_date']) ?></small>
                    </div>
                </div>

                <div class="singlepost__thumbnail">
                    <img src="<?= authEscape(ROOT_URL . ltrim($post['thumbnail'], '/')) ?>" alt="<?= authEscape($post['title']) ?>" />
                </div>

                <p>
                    <?= nl2br(authEscape($post['body'])) ?>
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Rodapé público com categorias, suporte e navegação. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
    </body>
</html>
