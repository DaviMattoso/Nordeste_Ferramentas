<?php

/** Guarda uma mensagem na sessão para ser exibida depois de um redirecionamento. */
function setFlash(string $type, string $message): void
{
    $_SESSION['user_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Lê a mensagem uma única vez e a remove da sessão.
 * Esse padrão evita reenvio de formulário e repetição da mensagem ao atualizar a página.
 */
function getFlash(): ?array
{
    $flash = $_SESSION['user_flash'] ?? null;
    unset($_SESSION['user_flash']);
    return is_array($flash) && in_array($flash['type'] ?? null, ['error', 'success'], true)
        && is_string($flash['message'] ?? null) ? $flash : null;
}
