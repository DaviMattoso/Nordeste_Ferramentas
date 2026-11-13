<?php
/**
 * Pesquisa pública de posts por título ou conteúdo.
 *
 * Valida o termo recebido por GET, executa uma busca parametrizada e reutiliza
 * os helpers de posts para montar os mesmos cards das demais listagens.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/post-utils.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';

const SEARCH_MAX_LENGTH = 200;

$publicCategories = publicCategories($connection);
$rawSearchTerm = $_GET['q'] ?? null;
$searchTerm = is_string($rawSearchTerm) ? trim($rawSearchTerm) : '';
$searchResults = [];
$searchMessage = '';
$searchError = '';
$searchWasPerformed = false;

/* A validação também impede LIKE '%%', que transformaria uma busca vazia em listagem geral. */
if ($rawSearchTerm === null || (is_string($rawSearchTerm) && $searchTerm === '')) {
    $searchMessage = 'Digite algo para pesquisar.';
} elseif (!is_string($rawSearchTerm) || preg_match('//u', $searchTerm) !== 1) {
    $searchMessage = 'Pesquisa inválida.';
} elseif (preg_match_all('/./us', $searchTerm) > SEARCH_MAX_LENGTH) {
    $searchMessage = 'A pesquisa deve ter no máximo ' . SEARCH_MAX_LENGTH . ' caracteres.';
} else {
    $searchWasPerformed = true;

    try {
        /*
         * Os curingas do visitante são escapados. Sem isso, "%" listaria todos os posts.
         * A barra invertida é o caractere de escape declarado na consulta.
         */
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
        $searchPattern = '%' . $escapedTerm . '%';
        $statement = $connection->prepare(
            'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, '
            . 'c.id AS category_id, c.title AS category_title, '
            . 'u.username, u.first_name, u.last_name, u.avatar '
            . 'FROM posts AS p '
            . 'INNER JOIN categories AS c ON c.id = p.category_id '
            . 'INNER JOIN users AS u ON u.id = p.author_id '
            . 'WHERE p.title LIKE ? ESCAPE \'\\\\\' OR p.body LIKE ? ESCAPE \'\\\\\' '
            . 'ORDER BY p.created_at DESC'
        );
        $statement->bind_param('ss', $searchPattern, $searchPattern);
        $statement->execute();
        $result = $statement->get_result();

        while ($post = $result->fetch_assoc()) {
            /* Usa o username como fallback quando o nome completo estiver vazio. */
            $authorName = trim($post['first_name'] . ' ' . $post['last_name']);
            $post['author_name'] = $authorName !== '' ? $authorName : $post['username'];
            $post['excerpt'] = postExcerpt($post['body']);
            $post['display_date'] = postDisplayDate($post['created_at']);
            $searchResults[] = $post;
        }

        $result->free();
        $statement->close();
    } catch (Throwable $exception) {
        /* A exceção fica no log e não revela a consulta ou o banco na resposta pública. */
        error_log('NF Blog: falha na pesquisa pública. Código: ' . $exception->getCode());
        http_response_code(500);
        $searchError = 'Não foi possível realizar a pesquisa agora. Tente novamente.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Pesquisa - NF Blog</title>

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

        <!-- Formulário GET que preserva o termo validado no campo de pesquisa. -->
        <section class="search__bar">

            <form action="search.php" method="GET" class="container search__bar-container">
                <div>
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        type="search"
                        name="q"
                        maxlength="<?= SEARCH_MAX_LENGTH ?>"
                        value="<?= authEscape($searchTerm) ?>"
                        placeholder="Pesquisar"
                    />
                </div>
                <button type="submit" class="btn">Go</button>
            </form>
        </section>

        <!-- Resultado da consulta parametrizada ou mensagem do estado da pesquisa. -->
        <section class="posts">
            <div class="container">
                <?php if ($searchWasPerformed): ?>
                <h2>Resultados para: &quot;<?= authEscape($searchTerm) ?>&quot;</h2>
                <?php endif; ?>

                <?php if ($searchError !== ''): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($searchError) ?></p>
                </div>
                <?php elseif ($searchMessage !== ''): ?>
                <p><?= authEscape($searchMessage) ?></p>
                <?php elseif (!$searchResults): ?>
                <p>Nenhum post encontrado para esta pesquisa.</p>
                <?php endif; ?>
            </div>

            <?php if ($searchError === '' && $searchResults): ?>
            <div class="container posts__container">
                <?php foreach ($searchResults as $post): ?>

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
            </div>
            <?php endif; ?>
        </section>

        <!-- Atalhos para continuar a navegação por categoria. -->
        <section class="category__buttons-section">
            <div class="container category__buttons-container">
                <?php foreach ($publicCategories as $publicCategory): ?>
                <a href="category-post.php?id=<?= (int) $publicCategory['id'] ?>" class="category__buttons"><?= authEscape((string) $publicCategory['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Rodapé público com categorias, suporte e navegação. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
    </body>
</html>
