<?php
// Centraliza a sessão, as verificações de acesso e helpers usados na saída HTML.
// Toda página que usa autenticação deve incluir este arquivo antes de emitir HTML.
if (session_status() === PHP_SESSION_NONE) {
    // Aceita somente IDs de sessão criados pelo servidor e transportados por cookie.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        // HTTP continua funcionando no XAMPP; HTTPS recebe cookie Secure.
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool
{
    // O login grava user_id como inteiro; validar também o tipo evita valores forjados.
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin(): void
{
    // Guarda reutilizável para qualquer página que exige uma conta autenticada.
    // Evita reutilizar páginas protegidas do cache depois do logout.
    header('Cache-Control: no-store');
    if (!isLoggedIn()) {
        header('Location: ../signin.php', true, 302);
        exit;
    }
}

function isAdmin(): bool
{
    // A role da sessão é atualizada no banco antes das ações administrativas sensíveis.
    return isLoggedIn() && ($_SESSION['role'] ?? null) === 'admin';
}

function requireAdmin(): void
{
    // Revalida no banco: exclusão ou rebaixamento por outro admin revoga a sessão antiga.
    requireLogin();
    global $connection;
    require_once __DIR__ . '/database.php';
    try {
        // Não confia apenas na sessão: busca a conta novamente para detectar exclusão
        // ou mudança de permissão feita por outro administrador.
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
    // Mantém os dados da sessão sincronizados com o cadastro atual.
    $_SESSION['username'] = $username;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['role'] = $role;
    if (!isAdmin()) {
        // Flash: a mensagem só nasce de uma tentativa realmente bloqueada.
        $_SESSION['access_denied'] = true;
        header('Location: dashboard.php?denied=1', true, 303);
        exit;
    }
}

function authEscape(string $value): string
{
    // Escapa texto para contexto HTML e substitui bytes UTF-8 inválidos com segurança.
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function authAvatar(): string
{
    $avatar = $_SESSION['avatar'] ?? null;
    // Aceita apenas o padrão de caminho produzido pelo upload; qualquer outro valor
    // recebe a imagem padrão e não pode apontar para um arquivo arbitrário.
    return is_string($avatar) && preg_match('~\AImages/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $avatar)
        ? $avatar : 'Images/avatar2.jpg';
}

// Pendência: proteção CSRF dos formulários e do logout na revisão de segurança.
