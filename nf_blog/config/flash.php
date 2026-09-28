<?php

/**
 * Implementa mensagens temporárias do painel administrativo.
 * O fluxo POST/Redirect/GET grava a mensagem antes do redirecionamento e a
 * página de destino a consome uma única vez.
 */

/** Guarda uma mensagem de sucesso ou erro na sessão atual. */
function setFlash(string $type, string $message): void
{
    $_SESSION['user_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retorna e remove a mensagem da sessão.
 * A remoção impede que o aviso reapareça ao atualizar a página de destino.
 */
function getFlash(): ?array
{
    $flash = $_SESSION['user_flash'] ?? null;
    unset($_SESSION['user_flash']);
    return is_array($flash) && in_array($flash['type'] ?? null, ['error', 'success'], true)
        && is_string($flash['message'] ?? null) ? $flash : null;
}
