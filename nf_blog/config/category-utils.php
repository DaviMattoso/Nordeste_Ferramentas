<?php

function categoryValidationErrors(string $title, string $description): array
{
    $errors = [];
    if ($title === '') {
        $errors[] = 'Informe o título da categoria.';
    }
    if (preg_match('//u', $title) !== 1 || preg_match('//u', $description) !== 1) {
        $errors[] = 'Há texto com codificação inválida no formulário.';
        return $errors;
    }
    if (preg_match_all('/./us', $title) > 150) {
        $errors[] = 'O título deve ter no máximo 150 caracteres.';
    }
    // TEXT armazena até 65.535 bytes, incluindo caracteres UTF-8 multibyte.
    if (strlen($description) > 65535) {
        $errors[] = 'A descrição é longa demais.';
    }
    return $errors;
}

function categoryTitleExists(mysqli $connection, string $title, ?int $excludedId = null): bool
{
    if ($excludedId === null) {
        $statement = $connection->prepare('SELECT id FROM categories WHERE title = ? LIMIT 1');
        $statement->bind_param('s', $title);
    } else {
        $statement = $connection->prepare('SELECT id FROM categories WHERE title = ? AND id <> ? LIMIT 1');
        $statement->bind_param('si', $title, $excludedId);
    }
    $statement->execute();
    $statement->bind_result($foundId);
    $exists = $statement->fetch() === true;
    $statement->close();
    return $exists;
}
