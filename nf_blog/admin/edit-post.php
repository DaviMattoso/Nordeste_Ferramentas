<?php
// Admin edita qualquer post; author só pode editar um post de sua própria autoria.
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/post-utils.php';
refreshPostActor($connection);

// O ID da URL é dado externo e precisa ser um inteiro positivo.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de post inválido.');
    header('Location: dashboard.php', true, 303);
    exit;
}

try {
    // Esta primeira leitura preenche o formulário e bloqueia acesso indevido já no GET.
    $statement = $connection->prepare('SELECT title, body, thumbnail, category_id, author_id, is_featured FROM posts WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($storedTitle, $storedBody, $storedThumbnail, $storedCategoryId, $storedAuthorId, $storedFeatured);
    $found = $statement->fetch() === true;
    $statement->close();
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao buscar post. Código: ' . $exception->getCode());
    setFlash('error', 'Não foi possível carregar o post. Tente novamente.');
    header('Location: dashboard.php', true, 303);
    exit;
}
if (!$found) {
    setFlash('error', 'Post não encontrado.');
    header('Location: dashboard.php', true, 303);
    exit;
}
if (!canManagePost((int) $storedAuthorId)) {
    setFlash('error', 'Você não tem permissão para editar este post.');
    header('Location: dashboard.php', true, 303);
    exit;
}

$title = $storedTitle;
$body = $storedBody;
$categoryId = (int) $storedCategoryId;
$isFeatured = (int) $storedFeatured;
$errors = [];
try {
    $categories = postCategories($connection);
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao carregar categorias para edição. Código: ' . $exception->getCode());
    $categories = [];
    $errors[] = 'Não foi possível carregar as categorias. Tente novamente.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    // Repete toda validação no servidor porque o formulário pode ser manipulado.
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
    [$thumbnailExtension, $thumbnailErrors] = postThumbnailValidation($_FILES['thumbnail'] ?? null, false);
    $errors = array_merge($errors, $thumbnailErrors);

    if (!$errors) {
        $newThumbnail = null;
        $inTransaction = false;
        try {
            $connection->begin_transaction();
            $inTransaction = true;
            // FOR UPDATE trava o registro e permite revalidar existência e autoria
            // dentro da mesma transação que fará a alteração.
            $statement = $connection->prepare('SELECT thumbnail, author_id FROM posts WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->bind_result($oldThumbnail, $currentAuthorId);
            $stillExists = $statement->fetch() === true;
            $statement->close();
            if (!$stillExists) {
                throw new DomainException('Post não encontrado.');
            }
            if (!canManagePost((int) $currentAuthorId)) {
                throw new DomainException('Você não tem permissão para editar este post.');
            }
            if (!postCategoryExists($connection, $categoryId)) {
                $errors[] = 'Categoria selecionada não existe. Escolha outra.';
                $connection->rollback();
                $inTransaction = false;
            } else {
                // Sem novo upload, mantém o caminho antigo. Com upload, só apaga a
                // imagem anterior depois que o UPDATE for confirmado pelo COMMIT.
                $thumbnail = $oldThumbnail;
                if ($thumbnailExtension !== null) {
                    $newThumbnail = savePostThumbnail($_FILES['thumbnail'], $thumbnailExtension);
                    $thumbnail = $newThumbnail;
                }
                $statement = $connection->prepare('UPDATE posts SET title = ?, body = ?, thumbnail = ?, category_id = ?, is_featured = ? WHERE id = ?');
                $statement->bind_param('sssiii', $title, $body, $thumbnail, $categoryId, $isFeatured, $id);
                $statement->execute();
                $statement->close();
                $connection->commit();
                $inTransaction = false;
                if ($newThumbnail !== null) {
                    removeManagedPostThumbnail($oldThumbnail);
                }
                setFlash('success', 'Post atualizado com sucesso.');
                header('Location: dashboard.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            // Qualquer falha volta o banco ao estado anterior e limpa somente o novo
            // arquivo, caso ele tenha sido salvo antes do erro.
            if ($inTransaction) {
                try {
                    $connection->rollback();
                } catch (Throwable $rollbackException) {
                    error_log('NF Blog: falha ao desfazer edição de post. Código: ' . $rollbackException->getCode());
                }
            }
            removeManagedPostThumbnail($newThumbnail);
            if ($exception instanceof DomainException) {
                setFlash('error', $exception->getMessage());
                header('Location: dashboard.php', true, 303);
                exit;
            }
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1452
                ? 'Categoria selecionada não está mais disponível. Escolha outra.'
                : 'Não foi possível atualizar o post. Tente novamente.';
            error_log('NF Blog: falha ao atualizar post. Código: ' . $exception->getCode());
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

        <link rel="stylesheet" href="../css/style.css" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <nav>
            <div class="container nav__container">
                <a href="../index.php" class="nav__logo">
                    <img
                        src="../Images/logo.png"
                        alt="Logo NFB"
                        class="nav__logo-image"
                    />
                    <span>NF BLOG</span>
                </a>
                <ul class="nav__items">
                    <li><a href="../blog.php">Posts</a></li>
                    <li><a href="../about.php">Sobre</a></li>
                    <li><a href="../services.php">Serviços</a></li>
                    <li><a href="../contact.php">Contato</a></li>
                    <?php if (!isLoggedIn()): ?>
                    <li><a href="../signin.php">Signin</a></li>
                    <?php else: ?>
                    <li class="nav__profile">
                        <div class="avatar">
                            <img src="<?= authEscape('../' . authAvatar()) ?>" alt="<?= authEscape($_SESSION['username'] ?? '') ?>" />
                        </div>
                        <ul>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li>
                                <form action="../logout.php" method="POST" class="logout__form">
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

        <section class="form__section">
            <div class="container form__section-container">
                <h2>Editar Post</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="edit-post.php?id=<?= (int) $id ?>" method="POST" enctype="multipart/form-data">
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
                        <label for="thumbnail">Mudar Thumbnail</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <button type="submit" class="btn" <?= !$categories ? 'disabled' : '' ?>>Editar</button>
                </form>
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
                        <li>Ferramentas</li>
                        <li>Construção</li>
                        <li>Marcenaria</li>
                        <li>Seg. no Trabalho</li>
                        <li>Dicas e Tutoriais</li>
                        <li>Maquinaria Pesada</li>
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
                        <li><a href="../contact.php">Numeros</a></li>
                        <li><a href="../contact.php">Email</a></li>
                        <li><a href="../contact.php">Localização</a></li>
                    </ul>
                </article>

                <article>
                    <h4>Navegação</h4>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../blog.php">Blog</a></li>
                        <li><a href="../about.php">Sobre</a></li>
                        <li><a href="../services.php">Serviços</a></li>
                        <li><a href="../contact.php">Contato</a></li>
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

        <script src="../js/main.js"></script>
    </body>
</html>
