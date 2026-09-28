<?php
/**
 * Centraliza sessão, autenticação, autorização e helpers seguros de apresentação.
 *
 * Deve ser incluído antes de qualquer HTML para que cookies, redirecionamentos
 * e bloqueios de acesso possam ser enviados corretamente.
 */

if (session_status() === PHP_SESSION_NONE) {
    /* Restringe a sessão a IDs do servidor e ao transporte por cookie. */
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        /* Mantém desenvolvimento em HTTP; em HTTPS, o cookie passa a exigir canal seguro. */
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Verifica se a sessão contém um identificador de usuário válido. */
function isLoggedIn(): bool
{
    /* O login grava um inteiro; tipo e faixa também são conferidos antes do uso. */
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/** Protege uma rota e direciona visitantes não autenticados para o login. */
function requireLogin(): void
{
    /* Evita reutilizar conteúdo protegido armazenado em cache após o logout. */
    header('Cache-Control: no-store');
    if (!isLoggedIn()) {
        header('Location: ../signin.php', true, 302);
        exit;
    }
}

/** Informa se a identidade já validada na sessão possui papel de administrador. */
function isAdmin(): bool
{
    /* Rotas sensíveis chamam requireAdmin(), que sincroniza esta role com o banco. */
    return isLoggedIn() && ($_SESSION['role'] ?? null) === 'admin';
}

/**
 * Protege operações exclusivas de administrador.
 * Reconsulta a conta para que exclusões e mudanças de papel revoguem sessões antigas.
 */
function requireAdmin(): void
{
    requireLogin();
    global $connection;
    require_once __DIR__ . '/database.php';
    try {
        /* A autorização deriva do estado atual no banco, não apenas do cookie existente. */
        $statement = $connection->prepare('SELECT username, avatar, role FROM users WHERE id = ?');
        $statement->bind_param('i', $_SESSION['user_id']);
        $statement->execute();
        $statement->bind_result($username, $avatar, $role);
        $found = $statement->fetch();
        $statement->close();
    } catch (Throwable $exception) {
        error_log('NF Blog: falha ao verificar permissão. Código: ' . $exception->getCode());
        http_response_code(500);
        exit('Não foi possível verificar a permissão.');
    }
    if (!$found) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['avatar']);
        header('Location: ../signin.php', true, 303);
        exit;
    }
    /* Atualiza os dados usados pela navbar e pelas verificações seguintes. */
    $_SESSION['username'] = $username;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['role'] = $role;
    if (!isAdmin()) {
        /* Registra a negativa apenas quando uma rota administrativa foi solicitada. */
        $_SESSION['access_denied'] = true;
        header('Location: dashboard.php?denied=1', true, 303);
        exit;
    }
}

/** Escapa texto destinado ao contexto HTML e tolera bytes UTF-8 inválidos. */
function authEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Retorna somente avatars gerenciados pela aplicação ou a imagem padrão. */
function authAvatar(): string
{
    $avatar = $_SESSION['avatar'] ?? null;
    /* A lista permitida de caminho impede que um valor de sessão aponte para outro arquivo. */
    return is_string($avatar) && preg_match('~\AImages/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $avatar)
        ? $avatar : 'Images/avatar2.jpg';
}
