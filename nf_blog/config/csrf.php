<?php

// auth.php centraliza o início da sessão usada para armazenar o token.
require_once __DIR__ . '/auth.php';

/** Retorna o token estável da sessão atual, criando-o quando necessário. */
function csrfToken(): string
{
    $token = $_SESSION['csrf_token'] ?? null;
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }
    return $token;
}

/** Gera o campo hidden usado pelos formulários POST protegidos. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . authEscape(csrfToken()) . '" />';
}

/** Valida sem efeitos colaterais um token recebido pelo servidor. */
function isValidCsrfToken(mixed $token): bool
{
    $expectedToken = $_SESSION['csrf_token'] ?? null;
    return is_string($token)
        && is_string($expectedToken)
        && hash_equals($expectedToken, $token);
}

/** Interrompe a requisição antes de qualquer mutação quando o token é inválido. */
function requireValidCsrfToken(): void
{
    if (isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        return;
    }
    http_response_code(403);
    exit('Sessão inválida ou formulário expirado. Tente novamente.');
}

/** Renova o token após uma mudança importante na autenticação. */
function regenerateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}
