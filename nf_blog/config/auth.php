<?php
// Toda página que usa autenticação deve incluir este arquivo antes de emitir HTML.
if (session_status() === PHP_SESSION_NONE) {
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
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin(): void
{
    // Evita reutilizar páginas protegidas do cache depois do logout.
    header('Cache-Control: no-store');
    if (!isLoggedIn()) {
        header('Location: ../signin.php', true, 302);
        exit;
    }
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? null) === 'admin';
}

function requireAdmin(): void
{
    // Primeiro autentica; somente depois verifica o role mantido na sessão.
    requireLogin();
    if (!isAdmin()) {
        // Flash: a mensagem só nasce de uma tentativa realmente bloqueada.
        $_SESSION['access_denied'] = true;
        header('Location: dashboard.php?denied=1', true, 303);
        exit;
    }
}

function authEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function authAvatar(): string
{
    $avatar = $_SESSION['avatar'] ?? null;
    // Aceita apenas o caminho local produzido pelo cadastro público.
    return is_string($avatar) && preg_match('~\AImages/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $avatar)
        ? $avatar : 'Images/avatar2.jpg';
}

// Pendência: proteção CSRF dos formulários e do logout na revisão de segurança.
