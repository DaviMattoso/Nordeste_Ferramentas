<?php

/**
 * Fornece a proteção CSRF dos formulários que alteram estado.
 *
 * O token pertence à sessão iniciada por auth.php e é comparado em tempo
 * constante antes de qualquer operação de cadastro, edição, exclusão ou logout.
 */

require_once __DIR__ . '/auth.php';

/** Retorna o token da sessão atual, criando um valor criptográfico quando necessário. */
function csrfToken(): string
{
    $token = $_SESSION['csrf_token'] ?? null;
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }
    return $token;
}

/** Gera o campo oculto já escapado que acompanha os formulários POST protegidos. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . authEscape(csrfToken()) . '" />';
}

/** Compara um token recebido com o valor da sessão sem alterar o estado. */
function isValidCsrfToken(mixed $token): bool
{
    $expectedToken = $_SESSION['csrf_token'] ?? null;
    return is_string($token)
        && is_string($expectedToken)
        && hash_equals($expectedToken, $token);
}

/** Interrompe a requisição com 403 quando o token enviado é ausente ou inválido. */
function requireValidCsrfToken(): void
{
    if (isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        return;
    }
    http_response_code(403);
    exit('Sessão inválida ou formulário expirado. Tente novamente.');
}

/** Renova o token após mudanças de autenticação, invalidando formulários anteriores. */
function regenerateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}
