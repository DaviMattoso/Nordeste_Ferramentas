<?php
// Edição de categoria é uma operação exclusiva de administrador.
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/category-utils.php';

// O ID recebido pela URL só entra na consulta depois de validado como inteiro positivo.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de categoria inválido.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

try {
    // Carrega o estado atual para preencher o formulário no primeiro acesso.
    $statement = $connection->prepare('SELECT title, description FROM categories WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($storedTitle, $storedDescription);
    $found = $statement->fetch();
    $statement->close();
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao buscar categoria. Código: ' . $exception->getCode());
    setFlash('error', 'Não foi possível carregar a categoria. Tente novamente.');
    header('Location: manage-categories.php', true, 303);
    exit;
}
if (!$found) {
    setFlash('error', 'Categoria não encontrada.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

$title = $storedTitle;
$description = $storedDescription ?? '';
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Revalida todo o conteúdo no servidor mesmo que o HTML tenha campos required.
    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $descriptionInput = $_POST['description'] ?? '';
    $description = is_string($descriptionInput) ? $descriptionInput : '';
    $errors = categoryValidationErrors($title, $description);
    if (!is_string($descriptionInput)) {
        $errors[] = 'Descrição inválida.';
    }

    if (!$errors) {
        $inTransaction = false;
        try {
            $connection->begin_transaction();
            $inTransaction = true;
            // FOR UPDATE confirma que a categoria ainda existe e impede mudança
            // concorrente até o fim desta transação.
            $statement = $connection->prepare('SELECT id FROM categories WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->bind_result($foundId);
            $stillExists = $statement->fetch() === true;
            $statement->close();
            if (!$stillExists) {
                throw new DomainException('Categoria não encontrada.');
            }
            if (categoryTitleExists($connection, $title, $id)) {
                // Ao detectar duplicidade, desfaz o lock sem executar UPDATE.
                $errors[] = 'Categoria já existe.';
                $connection->rollback();
                $inTransaction = false;
            } else {
                $storedDescription = $description === '' ? null : $description;
                $statement = $connection->prepare('UPDATE categories SET title = ?, description = ? WHERE id = ?');
                $statement->bind_param('ssi', $title, $storedDescription, $id);
                $statement->execute();
                $statement->close();
                $connection->commit();
                $inTransaction = false;
                setFlash('success', 'Categoria atualizada com sucesso.');
                header('Location: manage-categories.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            // Se qualquer etapa falhar, garante que nenhuma alteração parcial permaneça.
            if ($inTransaction) {
                try {
                    $connection->rollback();
                } catch (Throwable $rollbackException) {
                    error_log('NF Blog: falha ao desfazer edição de categoria. Código: ' . $rollbackException->getCode());
                }
            }
            if ($exception instanceof DomainException) {
                setFlash('error', $exception->getMessage());
                header('Location: manage-categories.php', true, 303);
                exit;
            }
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Categoria já existe.'
                : 'Não foi possível atualizar a categoria. Tente novamente.';
            error_log('NF Blog: falha ao atualizar categoria. Código: ' . $exception->getCode());
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

        <!-- ======== Formulário de categoria ======== -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Editar categoria</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="edit-category.php?id=<?= (int) $id ?>" method="POST">
                    <input type="text" name="title" placeholder="Título" maxlength="150" value="<?= authEscape($title) ?>" required />
                    <textarea name="description" rows="4" placeholder="Descrição"><?= authEscape($description) ?></textarea>
                    <div class="form__control">
                        <button type="submit" class="btn">Editar</button>
                    </div>
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
