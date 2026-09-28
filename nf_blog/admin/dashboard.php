<?php
/**
 * Dashboard de posts do NF Blog.
 *
 * Administradores visualizam todos os registros; autores visualizam somente os
 * próprios posts e mantêm acesso às operações autorizadas para sua conta.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/post-utils.php';
refreshPostActor($connection);

/* A mensagem do último CRUD é consumida uma única vez após o redirecionamento. */
$userFlash = getFlash();
$accessDenied = ($_SESSION['access_denied'] ?? false) === true;
unset($_SESSION['access_denied']);
$posts = [];
$listError = '';
try {
    /*
     * O administrador não recebe filtro. Para autores, o WHERE usa obrigatoriamente
     * o ID da sessão e nunca um identificador controlado pelo navegador.
     */
    $sql = 'SELECT p.id, p.title, p.category_id, c.title AS category_title, p.author_id, p.is_featured, p.created_at '
        . 'FROM posts AS p INNER JOIN categories AS c ON c.id = p.category_id';
    if (!isAdmin()) {
        $sql .= ' WHERE p.author_id = ?';
    }
    $statement = $connection->prepare($sql . ' ORDER BY p.id ASC');
    if (!isAdmin()) {
        $currentUserId = $_SESSION['user_id'];
        $statement->bind_param('i', $currentUserId);
    }
    $statement->execute();
    $statement->bind_result($postId, $postTitle, $categoryId, $categoryTitle, $authorId, $isFeatured, $createdAt);
    while ($statement->fetch()) {
        $posts[] = ['id' => $postId, 'title' => $postTitle, 'category_id' => $categoryId,
            'category_title' => $categoryTitle, 'author_id' => $authorId,
            'is_featured' => $isFeatured, 'created_at' => $createdAt];
    }
    $statement->close();
} catch (Throwable $exception) {
    /* A estrutura do painel permanece disponível mesmo quando a listagem falha. */
    error_log('NF Blog: falha ao listar posts. Código: ' . $exception->getCode());
    $listError = 'Não foi possível carregar os posts. Tente novamente.';
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

        <!-- Navegação do blog com acesso ao perfil autenticado. -->
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

        <!-- Menu de gestão e tabela de posts filtrada conforme o papel da sessão. -->
        <section class="dashboard">
            <div class="container dashboard__container">
                <aside>
                    <ul>
                        <li>
                            <a href="add-post.php">
                                <i class="fa-solid fa-pen"></i>
                                <h5>Adicionar Publicação</h5>
                            </a>
                        </li>

                        <li class="active">
                            <a href="dashboard.php">
                                <i class="fa-solid fa-file-image"></i>
                                <h5>Dashboard</h5>
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="add-user.php">
                                <i class="fa-solid fa-user-plus"></i>
                                <h5>Adicionar Usuário</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="manage-users.php">
                                <i class="fa-solid fa-user"></i>
                                <h5>Gerenciar Usuário</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="add-category.php">
                                <i class="fa-regular fa-pen-to-square"></i>
                                <h5>Adicionar Categoria</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="manage-categories.php">
                                <i class="fa-solid fa-list"></i>
                                <h5>Gerenciar Categorias</h5>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <main class="dashboard__content">
                    <h2>Dashboard</h2>
                    <?php if ($accessDenied): ?>
                    <div class="alert__message error" role="alert">
                        <p>Você não tem permissão para acessar essa área.</p>
                    </div>
                    <?php endif; ?>
                    <?php if ($userFlash): ?>
                    <div class="alert__message <?= authEscape($userFlash['type']) ?>" role="<?= $userFlash['type'] === 'error' ? 'alert' : 'status' ?>">
                        <p><?= authEscape($userFlash['message']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($listError !== ''): ?>
                    <div class="alert__message error" role="alert"><p><?= authEscape($listError) ?></p></div>
                    <?php endif; ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Categoria</th>
                                <th>Editar</th>
                                <th>Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= authEscape((string) $post['title']) ?></td>
                                <td><?= authEscape((string) $post['category_title']) ?></td>
                                <td><a href="edit-post.php?id=<?= (int) $post['id'] ?>" class="btn sm">Editar</a></td>
                                <td>
                                    <form action="delete-post.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este post?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>" />
                                        <button type="submit" class="btn sm danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$posts && $listError === ''): ?>
                            <tr><td colspan="4">Nenhum post encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </main>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
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
