<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
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
        <!-- ======== Fim da navbar ======== -->

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

                        <li>
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
                        <li class="active">
                            <a href="manage-categories.php">
                                <i class="fa-solid fa-list"></i>
                                <h5>Gerenciar Categorias</h5>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <main class="dashboard__content">
                    <h2>Gerenciar Categorias</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Editar</th>
                                <th>Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ferramentas</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td>Construção</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td>Marcenaria</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td>Segurança no Trabalho</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td>Dicas e Tutoriais</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td>Maquinaria Pesada</td>
                                <td>
                                    <a href="edit-category.php" class="btn sm"
                                        >Editar</a
                                    >
                                </td>
                                <td>
                                    <a
                                        href="delete-category.php"
                                        class="btn sm danger"
                                        >Excluir</a
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </main>
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
