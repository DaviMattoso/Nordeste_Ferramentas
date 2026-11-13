<?php
/**
 * Autentica usuários do NF Blog por username ou email.
 *
 * Verifica a senha armazenada, renova o identificador e o token CSRF da sessão
 * e direciona a conta autenticada ao painel administrativo.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

/* Uma sessão já autenticada segue diretamente para o painel. */
if (isLoggedIn()) {
    header('Location: ' . ROOT_URL . 'admin/dashboard.php', true, 302);
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
$login = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* O identificador aceita username ou email; espaços continuam válidos na senha. */
    $login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if ($login === '' || $password === '') {
        $error = 'Preencha o usuário/email e a senha.';
    } else {
        try {
            /* O statement mantém a entrada do visitante separada da consulta SQL. */
            $statement = $connection->prepare('SELECT id, first_name, last_name, username, email, password, avatar, role FROM users WHERE username = ? OR email = ? LIMIT 2');
            $statement->bind_param('ss', $login, $login);
            $statement->execute();
            $statement->store_result();
            $statement->bind_result($id, $firstName, $lastName, $username, $email, $hash, $avatar, $role);
            /*
             * Username de uma conta pode coincidir com o email de outra em dados antigos.
             * Só entra a conta cuja senha confere. Se as duas conferirem, nenhuma é escolhida.
             */
            $accounts = [];
            while ($statement->fetch()) {
                $accounts[] = [
                    'id' => (int) $id,
                    'username' => $username,
                    'hash' => $hash,
                    'avatar' => $avatar,
                    'role' => $role,
                ];
            }
            $statement->close();
            $authenticated = null;
            $ambiguous = false;
            foreach ($accounts as $account) {
                if (!password_verify($password, $account['hash'])) {
                    continue;
                }
                if ($authenticated !== null) {
                    $ambiguous = true;
                    break;
                }
                $authenticated = $account;
            }
            if (!$ambiguous && $authenticated !== null) {
                /* A troca do identificador antes do login protege contra fixação de sessão. */
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException('Falha ao renovar sessão.');
                }
                $newCsrfToken = regenerateCsrfToken();
                $_SESSION = [
                    'user_id' => $authenticated['id'],
                    'username' => $authenticated['username'],
                    'role' => $authenticated['role'],
                    'avatar' => $authenticated['avatar'],
                    'csrf_token' => $newCsrfToken,
                ];
                /* POST/Redirect/GET impede o reenvio da senha ao atualizar o dashboard. */
                header('Location: ' . ROOT_URL . 'admin/dashboard.php', true, 303);
                exit;
            }
            $error = 'Usuário/email ou senha inválidos.';
        } catch (Throwable $exception) {
            /* A resposta não expõe exceção, consulta ou detalhes do banco. */
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

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública reduzida para visitantes ainda não autenticados. -->
        <?php $hideAuthNav = true; require __DIR__ . '/partials/public-nav.php'; ?>

        <!-- Formulário protegido por CSRF; o processamento ocorre antes do HTML. -->
        <section class="form__section">
            <div class="container form__section-container">

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
                    <?= csrfField() ?>
                    <input type="text" name="login" placeholder="Username ou Email" value="<?= authEscape($login) ?>" autocomplete="username" required />
                    <input type="password" name="password" placeholder="Senha" autocomplete="current-password" required />
                    <div class="form__control">
                        <button type="submit" class="btn">Entrar</button>
                        <small
                            >Não tem uma conta?
                            <a href="signup.php">Cadastrar</a></small
                        >
                    </div>
                </form>
            </div>
        </section>

        <!-- Rodapé público com categorias, suporte e navegação. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
    </body>
</html>
