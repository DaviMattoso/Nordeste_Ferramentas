<?php
/**
 * Cadastro público de autores do NF Blog.
 *
 * Valida dados, senha e avatar, impede username/email duplicados e cria toda
 * conta pública com o papel fixo de `author`.
 */

require_once __DIR__ . '/config/user-utils.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';
require_once __DIR__ . '/config/csrf.php';

/* Campos não sensíveis são repopulados após erro; senhas nunca retornam ao HTML. */
$publicCategories = publicCategories($connection);
$errors = [];
$values = array_fill_keys(['first_name', 'last_name', 'username', 'email'], '');
$escape = static function ($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* Campos textuais aceitam somente strings e têm espaços externos removidos. */
    foreach ($values as $field => $value) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    /* Senhas não recebem trim porque espaços podem fazer parte do valor escolhido. */
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if ($password === '' || $confirmation === '') {
        $errors[] = 'Preencha todos os campos obrigatórios.';
    }
    $errors = array_merge($errors, userFieldErrors($values), userPasswordErrors($password, $confirmation));

    $upload = $_FILES['avatar'] ?? null;
    /* A extensão confiável deriva do conteúdo do arquivo, não do nome enviado. */
    [$avatarExtension, $avatarErrors] = userAvatarValidation($upload);
    $errors = array_merge($errors, $avatarErrors);

    if (!$errors) {
        $avatar = null;
        try {
            $errors = userDuplicateErrors($connection, $values);

            if (!$errors) {
                /* Somente o hash com salt e custo do algoritmo padrão é persistido. */
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($avatarExtension !== null) {
                    $avatar = saveUserAvatar($upload, $avatarExtension);
                }
                $role = 'author';
                $statement = $connection->prepare('INSERT INTO users (first_name, last_name, username, email, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->bind_param('sssssss', $values['first_name'], $values['last_name'], $values['username'], $values['email'], $hash, $avatar, $role);
                $statement->execute();
            }
        } catch (Throwable $exception) {
            /* Remove o avatar salvo caso o INSERT não possa ser concluído. */
            removeManagedUserAvatar($avatar);
            /* Os índices UNIQUE são a garantia final contra requisições concorrentes. */
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Username ou email já está cadastrado.'
                : 'Não foi possível concluir o cadastro. Tente novamente.';
            error_log('NF Blog: falha no cadastro. Código: ' . $exception->getCode());
        }
        if (!$errors) {
            /* POST/Redirect/GET impede novo cadastro ao atualizar a página de destino. */
            header('Location: signin.php?registered=1', true, 303);
            exit;
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

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública exibida durante a criação da conta. -->
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

        <!-- Formulário multipart para dados da conta e avatar opcional. -->
        <section class="form__section">
            <div class="container form__section-container">

                <div class="form__image">
                    <img src="./Images/signup.png" alt="Criando uma conta" />
                </div>

                <h2>Criar conta</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= $escape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="signup.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="text" name="first_name" placeholder="Primeiro Nome" maxlength="100" value="<?= $escape($values['first_name']) ?>" required />
                    <input type="text" name="last_name" placeholder="Sobrenome" maxlength="100" value="<?= $escape($values['last_name']) ?>" required />
                    <input type="text" name="username" placeholder="Username" maxlength="100" value="<?= $escape($values['username']) ?>" required />
                    <input type="email" name="email" placeholder="Email" maxlength="254" value="<?= $escape($values['email']) ?>" required />
                    <input type="password" name="password" placeholder="Crie uma senha" minlength="8" autocomplete="new-password" required />
                    <input type="password" name="confirm_password" placeholder="Confirmar Senha" minlength="8" autocomplete="new-password" required />
                    <div class="form__control">
                        <label for="avatar">Avatar</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <button type="submit" class="btn">Cadastrar</button>
                    <small
                        >Já tem uma conta?
                        <a href="signin.php">Login</a></small
                    >
                </form>
            </div>
        </section>

        <!-- Rodapé público com categorias, suporte e navegação. -->
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
                        <?php foreach ($publicCategories as $publicCategory): ?>
                        <li><a href="category-post.php?id=<?= (int) $publicCategory['id'] ?>"><?= $escape((string) $publicCategory['title']) ?></a></li>
                        <?php endforeach; ?>
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
