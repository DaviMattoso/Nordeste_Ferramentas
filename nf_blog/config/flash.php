<?php

function setFlash(string $type, string $message): void
{
    $_SESSION['user_flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['user_flash'] ?? null;
    unset($_SESSION['user_flash']);
    return is_array($flash) && in_array($flash['type'] ?? null, ['error', 'success'], true)
        && is_string($flash['message'] ?? null) ? $flash : null;
}
