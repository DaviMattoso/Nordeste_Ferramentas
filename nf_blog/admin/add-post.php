<?php
/**
 * Formulário de criação de posts do painel.
 *
 * Permite acesso a administradores e autores, valida categoria e thumbnail e
 * atribui a autoria exclusivamente à identidade autenticada na sessão.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/post-utils.php';
refreshPostActor($connection);

/* Estes valores também repopulam o formulário quando a validação falha. */
$title = '';
$body = '';
$categoryId = null;
$isFeatured = 0;
$errors = [];
try {
    /* As opções vêm do banco para que o formulário reflita as categorias atuais. */
    $categories = postCategories($connection);
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao carregar categorias para post. Código: ' . $exception->getCode());
    $categories = [];
    $errors[] = 'Não foi possível carregar as categorias. Tente novamente.';
}
if (!$categories && !$errors) {
    $errors[] = 'Cadastre uma categoria antes de criar um post.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* A validação no servidor não depende de atributos HTML manipuláveis. */
    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $body = is_string($_POST['body'] ?? null) ? $_POST['body'] : '';
    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    [$isFeatured, $featuredError] = postFeaturedInput($_POST['is_featured'] ?? null);
    $errors = array_merge($errors, postValidationErrors($title, $body));
    if ($categoryId === false || $categoryId === null) {
        $errors[] = 'Selecione uma categoria válida.';
    }
    if ($featuredError !== null) {
        $errors[] = $featuredError;
    }
    [$thumbnailExtension, $thumbnailErrors] = postThumbnailValidation($_FILES['thumbnail'] ?? null, true);
    $errors = array_merge($errors, $thumbnailErrors);

    if (!$errors) {
        $thumbnail = null;
        try {
            /* Reconfirma a categoria caso ela tenha sido removida após a abertura da tela. */
            if (!postCategoryExists($connection, $categoryId)) {
                $errors[] = 'Categoria selecionada não existe. Escolha outra.';
            } else {
                $thumbnail = savePostThumbnail($_FILES['thumbnail'], $thumbnailExtension);
                /* A autoria vem da sessão e não pode ser escolhida pelo formulário. */
                $authorId = $_SESSION['user_id'];
                $statement = $connection->prepare('INSERT INTO posts (title, body, thumbnail, category_id, author_id, is_featured) VALUES (?, ?, ?, ?, ?, ?)');
                $statement->bind_param('sssiii', $title, $body, $thumbnail, $categoryId, $authorId, $isFeatured);
                $statement->execute();
                $statement->close();
                setFlash('success', 'Post criado com sucesso.');
                header('Location: dashboard.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            /* Uma falha posterior ao upload não deve deixar thumbnail órfã. */
            removeManagedPostThumbnail($thumbnail);
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1452
                ? 'Categoria selecionada não está mais disponível. Escolha outra.'
                : 'Não foi possível criar o post. Tente novamente.';
            error_log('NF Blog: falha ao criar post. Código: ' . $exception->getCode());
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>NF Blog</title>

        <link rel="icon" href="../Images/favicon.ico" />

        <link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação do blog com caminhos relativos ao diretório administrativo. -->
        <?php $blogBase = '../'; $dashboardHref = 'dashboard.php'; require __DIR__ . '/../partials/public-nav.php'; ?>

        <!-- Formulário multipart de criação de post e thumbnail. -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Adicionar Post</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                    <?php if (!$categories && isAdmin()): ?>
                    <p><a href="add-category.php">Adicionar categoria</a></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <form action="add-post.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="text" name="title" placeholder="Título" maxlength="255" value="<?= authEscape($title) ?>" required />
                    <select name="category_id" aria-label="Categoria" <?= !$categories ? 'disabled' : 'required' ?>>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= authEscape($category['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="body" rows="10" placeholder="Conteúdo" required><?= authEscape($body) ?></textarea>
                    <div class="form__control inline">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $isFeatured === 1 ? 'checked' : '' ?> />
                        <label for="is_featured">POST EM DESTAQUE</label>
                    </div>
                    <div class="form__control">
                        <label for="thumbnail">Adicionar Thumbnail</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" required />
                    </div>
                    <button type="submit" class="btn" <?= !$categories ? 'disabled' : '' ?>>Adicionar post</button>
                </form>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
