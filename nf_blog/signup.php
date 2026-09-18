<?php
$errors = [];
$values = array_fill_keys(['first_name', 'last_name', 'username', 'email'], '');
$escape = static function ($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

// TODO: adicionar proteção CSRF na revisão de segurança, antes da publicação.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($values as $field => $value) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    // Senhas não recebem trim: espaços podem fazer parte da senha escolhida.
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if (in_array('', $values, true) || $password === '' || $confirmation === '') {
        $errors[] = 'Preencha todos os campos obrigatórios.';
    }
    foreach (['first_name' => 100, 'last_name' => 100, 'username' => 100, 'email' => 254] as $field => $limit) {
        if (preg_match('//u', $values[$field]) !== 1) {
            $errors[] = 'Há texto com codificação inválida no formulário.';
            break;
        }
        if (preg_match_all('/./us', $values[$field]) > $limit) {
            $errors[] = 'Nome, sobrenome e username permitem até 100 caracteres; email, até 254.';
            break;
        }
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    if (preg_match('//u', $password) !== 1 || preg_match_all('/./us', $password) < 8) {
        $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    // Evita truncamento silencioso pelo bcrypt usado em PASSWORD_DEFAULT.
    if (strlen($password) > 72 || strpos($password, "\0") !== false) {
        $errors[] = 'A senha deve ter no máximo 72 bytes e não pode conter caracteres nulos.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'As senhas não coincidem.';
    }

    $upload = $_FILES['avatar'] ?? null;
    $avatarExtension = null;
    if ($upload !== null) {
        if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
            $errors[] = 'Arquivo de avatar inválido.';
        } elseif ($upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Falha no envio do avatar. Envie uma imagem de até 2 MB.';
            } elseif (!is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
                $errors[] = 'Arquivo de avatar inválido.';
            } elseif (filesize($upload['tmp_name']) > 2 * 1024 * 1024) {
                $errors[] = 'O avatar deve ter no máximo 2 MB.';
            } else {
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $image = @getimagesize($upload['tmp_name']);
                if (!isset($allowedTypes[$mime]) || $image === false || $image['mime'] !== $mime) {
                    $errors[] = 'Arquivo de avatar inválido. Use JPG, PNG ou WebP.';
                } else {
                    $avatarExtension = $allowedTypes[$mime];
                }
            }
        }
    }

    if (!$errors) {
        require_once __DIR__ . '/config/database.php';
        $avatar = null;
        $avatarPath = null;
        try {
            $statement = $connection->prepare('SELECT username = ?, email = ? FROM users WHERE username = ? OR email = ?');
            $statement->bind_param('ssss', $values['username'], $values['email'], $values['username'], $values['email']);
            $statement->execute();
            $statement->bind_result($sameUsername, $sameEmail);
            while ($statement->fetch()) {
                if ($sameUsername) {
                    $errors[] = 'Username já está em uso.';
                }
                if ($sameEmail) {
                    $errors[] = 'Email já está cadastrado.';
                }
            }
            $statement->close();

            if (!$errors) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($avatarExtension !== null) {
                    $directory = __DIR__ . '/Images/avatars';
                    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
                        throw new RuntimeException('Falha ao criar diretório de avatars.');
                    }
                    $avatar = 'Images/avatars/' . bin2hex(random_bytes(16)) . '.' . $avatarExtension;
                    $avatarPath = __DIR__ . '/' . $avatar;
                    if (!@move_uploaded_file($upload['tmp_name'], $avatarPath)) {
                        throw new RuntimeException('Falha ao salvar avatar.');
                    }
                }
                $role = 'author';
                $statement = $connection->prepare('INSERT INTO users (first_name, last_name, username, email, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->bind_param('sssssss', $values['first_name'], $values['last_name'], $values['username'], $values['email'], $hash, $avatar, $role);
                $statement->execute();
            }
        } catch (Throwable $exception) {
            if ($avatarPath !== null && is_file($avatarPath) && !@unlink($avatarPath)) {
                error_log('NF Blog: falha ao remover avatar de cadastro não concluído.');
            }
            // Os índices UNIQUE também protegem contra cadastros simultâneos.
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Username ou email já está cadastrado.'
                : 'Não foi possível concluir o cadastro. Tente novamente.';
            error_log('NF Blog: falha no cadastro. Código: ' . $exception->getCode());
        }
        if (!$errors) {
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

        <!-- ======== Formulário de Cadastro ======== -->
        <section class="form__section">
            <div class="container form__section-container">
                <!-- ======== Imagem da página ======== -->
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
