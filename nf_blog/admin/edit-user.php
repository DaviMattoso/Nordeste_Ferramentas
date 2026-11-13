<?php
/**
 * Edita cadastro, papel, senha e avatar de um usuário.
 *
 * A operação é exclusiva de administradores, protege a permanência de ao menos
 * um admin e sincroniza a sessão quando a própria conta é modificada.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-utils.php';
require_once __DIR__ . '/../config/flash.php';

/* O ID da URL só entra nas consultas depois de validado como inteiro positivo. */
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de usuário inválido.');
    header('Location: manage-users.php', true, 303);
    exit;
}

try {
    /* A leitura inicial preenche o formulário e detecta contas inexistentes. */
    $statement = $connection->prepare('SELECT first_name, last_name, username, email, avatar, role FROM users WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($firstName, $lastName, $username, $email, $currentAvatar, $currentRole);
    $found = $statement->fetch();
    $statement->close();
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao buscar usuário. Código: ' . $exception->getCode());
    setFlash('error', 'Não foi possível carregar o usuário. Tente novamente.');
    header('Location: manage-users.php', true, 303);
    exit;
}
if (!$found) {
    setFlash('error', 'Usuário não encontrado.');
    header('Location: manage-users.php', true, 303);
    exit;
}

$values = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email];
$role = $currentRole;
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* Senha vazia mantém o hash atual; uma confirmação isolada continua sendo erro. */
    foreach ($values as $field => $unused) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    $role = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
    $password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $errors = userFieldErrors($values);
    if (!in_array($role, ['author', 'admin'], true)) {
        $errors[] = 'Permissão inválida.';
    }
    if ($password !== '') {
        $errors = array_merge($errors, userPasswordErrors($password, $confirmation));
    } elseif ($confirmation !== '') {
        $errors[] = 'Informe a nova senha para confirmar a alteração.';
    }
    [$avatarExtension, $avatarErrors] = userAvatarValidation($_FILES['avatar'] ?? null);
    $errors = array_merge($errors, $avatarErrors);

    if (!$errors) {
        $newAvatar = null;
        $inTransaction = false;
        try {
            $connection->begin_transaction();
            $inTransaction = true;
            /* Os locks impedem que operações concorrentes rebaixem o último administrador. */
            $adminCount = lockedAdminCount($connection);
            $statement = $connection->prepare('SELECT avatar, role FROM users WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->bind_result($oldAvatar, $storedRole);
            $stillExists = $statement->fetch();
            $statement->close();
            if (!$stillExists) {
                throw new DomainException('Usuário não encontrado.');
            }
            if ($storedRole === 'admin' && $role === 'author' && $adminCount <= 1) {
                throw new DomainException('O último administrador não pode ser alterado para autor.');
            }
            $duplicates = userDuplicateErrors($connection, $values, $id);
            if ($duplicates) {
                /* Conflitos de username ou email encerram a transação sem UPDATE. */
                $errors = array_merge($errors, $duplicates);
                $connection->rollback();
                $inTransaction = false;
            } else {
                /* A ausência de novo upload preserva o avatar atual. */
                $avatar = $oldAvatar;
                if ($avatarExtension !== null) {
                    $newAvatar = saveUserAvatar($_FILES['avatar'], $avatarExtension);
                    $avatar = $newAvatar;
                }
                /* COALESCE mantém a senha anterior quando nenhum novo hash foi gerado. */
                $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
                $statement = $connection->prepare('UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, role = ?, avatar = ?, password = COALESCE(?, password) WHERE id = ?');
                $statement->bind_param('sssssssi', $values['first_name'], $values['last_name'], $values['username'], $values['email'], $role, $avatar, $hash, $id);
                $statement->execute();
                $statement->close();
                $connection->commit();
                $inTransaction = false;
                /* O avatar antigo só é removido depois que o novo caminho foi confirmado. */
                if ($newAvatar !== null) {
                    removeManagedUserAvatar($oldAvatar);
                }
                if ($_SESSION['user_id'] === $id) {
                    /* Alterações na própria conta são refletidas imediatamente na sessão. */
                    $_SESSION['username'] = $values['username'];
                    $_SESSION['role'] = $role;
                    $_SESSION['avatar'] = $avatar;
                }
                setFlash('success', 'Usuário atualizado com sucesso.');
                header('Location: ' . ($_SESSION['user_id'] === $id && $role === 'author' ? 'dashboard.php' : 'manage-users.php'), true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            /* Em falha, reverte o banco e limpa apenas o upload ainda não confirmado. */
            if ($inTransaction) {
                try {
                    $connection->rollback();
                } catch (Throwable $rollbackException) {
                    error_log('NF Blog: falha ao desfazer edição de usuário. Código: ' . $rollbackException->getCode());
                }
            }
            removeManagedUserAvatar($newAvatar);
            $errors[] = $exception instanceof DomainException ? $exception->getMessage()
                : ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                    ? 'Username ou email já está cadastrado.'
                    : 'Não foi possível atualizar o usuário. Tente novamente.');
            if (!($exception instanceof DomainException)) {
                error_log('NF Blog: falha ao atualizar usuário. Código: ' . $exception->getCode());
            }
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

        <!-- Formulário de edição; senha e avatar permanecem opcionais. -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Editar usuário</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="edit-user.php?id=<?= (int) $id ?>" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="text" name="first_name" placeholder="Primeiro Nome" maxlength="100" value="<?= authEscape($values['first_name']) ?>" required />
                    <input type="text" name="last_name" placeholder="Sobrenome" maxlength="100" value="<?= authEscape($values['last_name']) ?>" required />
                    <input type="text" name="username" placeholder="Username" maxlength="100" value="<?= authEscape($values['username']) ?>" required />
                    <input type="email" name="email" placeholder="Email" maxlength="254" value="<?= authEscape($values['email']) ?>" required />
                    <select name="role" aria-label="Permissão" required>
                        <option value="author" <?= $role === 'author' ? 'selected' : '' ?>>Autor</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <input type="password" name="new_password" placeholder="Nova senha (opcional)" minlength="8" autocomplete="new-password" />
                    <input type="password" name="confirm_password" placeholder="Confirmar nova senha" minlength="8" autocomplete="new-password" />
                    <div class="form__control">
                        <label for="avatar">Novo avatar (opcional)</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <button type="submit" class="btn">Editar</button>
                </form>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
