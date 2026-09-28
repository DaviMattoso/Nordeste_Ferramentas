<?php
/**
 * Encerra de forma explícita uma sessão autenticada.
 *
 * Aceita somente POST com CSRF válido, limpa os dados em memória, expira o
 * cookie correspondente e remove a sessão no servidor antes do redirecionamento.
 */

require_once __DIR__ . '/config/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('O logout exige envio pelo formulário.');
}
requireValidCsrfToken();

/* Descarta os dados associados à identidade antes de invalidar o armazenamento. */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    /* Expira o cookie com os mesmos atributos usados quando a sessão foi criada. */
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
/* Invalida a sessão no servidor e impede o cache da resposta de logout. */
session_destroy();
header('Cache-Control: no-store');
header('Location: signin.php?logout=1', true, 303);
exit;
