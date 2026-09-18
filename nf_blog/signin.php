<?php
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    header('Location: admin/dashboard.php', true, 302);
    exit;
}

$login = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if ($login === '' || $password === '') {
        $error = 'Preencha o usuário/email e a senha.';
    } else {
        require_once __DIR__ . '/config/database.php';
        try {
            $statement = $connection->prepare('SELECT id, first_name, last_name, username, email, password, avatar, role FROM users WHERE username = ? OR email = ? LIMIT 2');
            $statement->bind_param('ss', $login, $login);
            $statement->execute();
            $statement->store_result();
            $statement->bind_result($id, $firstName, $lastName, $username, $email, $hash, $avatar, $role);
            // Em caso de username igual ao email de outra conta, não escolhe uma conta arbitrariamente.
            $found = $statement->num_rows === 1 && $statement->fetch();
            $statement->close();
            if ($found && password_verify($password, $hash)) {
                // Troca o identificador antes de gravar a identidade autenticada.
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException('Falha ao renovar sessão.');
                }
                $_SESSION = [
                    'user_id' => (int) $id,
                    'username' => $username,
                    'role' => $role,
                    'avatar' => $avatar,
                ];
                header('Location: admin/dashboard.php', true, 303);
                exit;
            }
            $error = 'Usuário/email ou senha inválidos.';
        } catch (Throwable $exception) {
            error_log('NF Blog: falha no login. Código: ' . $exception->getCode());
            $error = 'Não foi possível entrar. Tente novamente.';
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
        <link rel="icon" href="./Images/favicon.ico" />
        <!-- Custom style css -->
        <link rel="stylesheet" href="./css/style.css" />
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
                </ul>

                <button id="open__nav-btn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="close__nav-btn">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>
        </nav>

        <!-- ======== Formulário de Login ======== -->
        <section class="form__section">
            <div class="container form__section-container">
                <!-- ======== Imagem da página ======== -->
                <div class="form__image">
                    <img src="./Images/signin.png" alt="Entrando no NF Blog" />
                </div>

                <h2>Login</h2>
                <?php if ($error !== ''): ?>
                <div class="alert__message error" role="alert">
                    <p><?= authEscape($error) ?></p>
                </div>
                <?php elseif (($_GET['registered'] ?? null) === '1'): ?>
                <div class="alert__message success" role="status">
                    <p>Conta criada com sucesso. Faça login.</p>
                </div>
                <?php elseif (($_GET['logout'] ?? null) === '1'): ?>
                <div class="alert__message success" role="status">
                    <p>Você saiu da conta.</p>
                </div>
                <?php endif; ?>
                <form action="signin.php" method="POST">
                    <input type="text" name="login" placeholder="Username ou Email" value="<?= authEscape($login) ?>" autocomplete="username" required />
                    <input type="password" name="password" placeholder="Senha" autocomplete="current-password" required />
                    <div class="form__control">
                        <button type="submit" class="btn">Entrar</button>
                        <small
                            >Nao tem uma conta?
                            <a href="signup.php">Cadastrar</a></small
                        >
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

        <!-- ======== JS ======== -->
        <script src="./js/main.js"></script>
    </body>
</html>
