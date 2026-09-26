<?php
require_once __DIR__ . '/config/auth.php';

// Remove primeiro os dados da memória da sessão.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    // Expira no navegador o mesmo cookie e com os mesmos atributos usados na criação.
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ]);
}
// Invalida o armazenamento da sessão no servidor e evita cache da página protegida.
session_destroy();
header('Cache-Control: no-store');
header('Location: signin.php?logout=1', true, 303);
exit;
