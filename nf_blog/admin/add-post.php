<?php
// Criação de posts exige login, mas aceita tanto admin quanto author.
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/post-utils.php';
refreshPostActor($connection);

// Valores iniciais também servem para repopular o formulário após um erro.
$title = '';
$body = '';
$categoryId = null;
$isFeatured = 0;
$errors = [];
try {
    // As categorias vêm do banco para impedir opções fixas e desatualizadas no HTML.
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
    // Todo valor recebido é normalizado/validado no servidor; o atributo required
    // do HTML melhora a experiência, mas não é uma barreira de segurança.
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
            // Reconfirma a categoria porque ela pode ter sido removida após abrir a tela.
            if (!postCategoryExists($connection, $categoryId)) {
                $errors[] = 'Categoria selecionada não existe. Escolha outra.';
            } else {
                $thumbnail = savePostThumbnail($_FILES['thumbnail'], $thumbnailExtension);
                // A autoria sempre vem da sessão autenticada, nunca de input do formulário.
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
            // Se o banco falhar depois do upload, remove o arquivo que ficaria órfão.
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
        <!-- Fav icon -->
        <link rel="icon" href="../Images/favicon.ico" />
        <!-- Custom style css -->
        <link rel="stylesheet" href="../css/style.css" />
        <!-- Font-awesome cdn -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>
        <!-- ======== Navbar ======== -->
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
                            <li><a href="../logout.php">Logout</a></li>
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

        <!-- ======== Formulário de post ======== -->
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

        <!-- ======== JS ======== -->
        <script src="../js/main.js"></script>
    </body>
</html>
