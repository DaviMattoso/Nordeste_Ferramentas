<?php
/**
 * Formulário administrativo de criação de usuários.
 *
 * Permite ao administrador definir o papel inicial, valida senha e avatar e
 * impede duplicidade de username ou email antes de persistir a conta.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-utils.php';
require_once __DIR__ . '/../config/flash.php';

/* Campos não sensíveis são preservados para repopular o formulário após erro. */
$values = array_fill_keys(['first_name', 'last_name', 'username', 'email'], '');
$role = 'author';
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* Entradas textuais são normalizadas e a role é comparada com uma lista fechada. */
    foreach ($values as $field => $unused) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    $role = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $errors = array_merge(userFieldErrors($values), userPasswordErrors($password, $confirmation));
    if ($password === '' || $confirmation === '') {
        $errors[] = 'Preencha todos os campos obrigatórios.';
    }
    if (!in_array($role, ['author', 'admin'], true)) {
        $errors[] = 'Permissão inválida.';
    }
    [$avatarExtension, $avatarErrors] = userAvatarValidation($_FILES['avatar'] ?? null);
    $errors = array_merge($errors, $avatarErrors);

    if (!$errors) {
        $avatar = null;
        try {
            /* A consulta melhora a mensagem; os índices UNIQUE protegem a concorrência. */
            $errors = userDuplicateErrors($connection, $values);
            if (!$errors) {
                /* A senha é persistida apenas pelo hash gerado com o algoritmo padrão. */
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($avatarExtension !== null) {
                    $avatar = saveUserAvatar($_FILES['avatar'], $avatarExtension);
                }
                $statement = $connection->prepare('INSERT INTO users (first_name, last_name, username, email, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->bind_param('sssssss', $values['first_name'], $values['last_name'], $values['username'], $values['email'], $hash, $avatar, $role);
                $statement->execute();
                $statement->close();
                setFlash('success', 'Usuário criado com sucesso.');
                header('Location: manage-users.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            /* Remove o avatar salvo se a conta não puder ser gravada no banco. */
            removeManagedUserAvatar($avatar);
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Username ou email já está cadastrado.'
                : 'Não foi possível criar o usuário. Tente novamente.';
            error_log('NF Blog: falha ao criar usuário. Código: ' . $exception->getCode());
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

        <!-- Formulário administrativo de criação de conta e avatar opcional. -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Adicionar usuário</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="add-user.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="text" name="first_name" placeholder="Primeiro Nome" maxlength="100" value="<?= authEscape($values['first_name']) ?>" required />
                    <input type="text" name="last_name" placeholder="Sobrenome" maxlength="100" value="<?= authEscape($values['last_name']) ?>" required />
                    <input type="text" name="username" placeholder="Username" maxlength="100" value="<?= authEscape($values['username']) ?>" required />
                    <input type="email" name="email" placeholder="Email" maxlength="254" value="<?= authEscape($values['email']) ?>" required />
                    <input type="password" name="password" placeholder="Crie uma senha" minlength="8" autocomplete="new-password" required />
                    <input type="password" name="confirm_password" placeholder="Confirmar Senha" minlength="8" autocomplete="new-password" required />
                    <select name="role" aria-label="Permissão" required>
                        <option value="author" <?= $role === 'author' ? 'selected' : '' ?>>Autor</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <div class="form__control">
                        <label for="avatar">Avatar</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <button type="submit" class="btn">Adicionar usuário</button>
                </form>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
