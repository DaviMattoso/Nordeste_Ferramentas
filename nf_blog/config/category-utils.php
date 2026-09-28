<?php

/**
 * Reúne validações e consultas compartilhadas do cadastro de categorias.
 * É usado pelo CRUD administrativo e pela navegação das páginas públicas.
 */

/** Valida obrigatoriedade, codificação e limites definidos pela tabela `categories`. */
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
    /* O limite de TEXT é medido em bytes e inclui caracteres UTF-8 multibyte. */
    if (strlen($description) > 65535) {
        $errors[] = 'A descrição é longa demais.';
    }
    return $errors;
}

/** Verifica duplicidade de título, desconsiderando o próprio registro na edição. */
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

/** Retorna as categorias exibidas na navegação pública, em ordem alfabética estável. */
function publicCategories(mysqli $connection): array
{
    try {
        /* Não há entrada externa; o ID serve apenas como desempate determinístico. */
        $categories = [];
        $result = $connection->query('SELECT id, title FROM categories ORDER BY title ASC, id ASC');
        while ($category = $result->fetch_assoc()) {
            $categories[] = $category;
        }
        $result->free();
        return $categories;
    } catch (Throwable $exception) {
        /* Uma falha auxiliar não impede que o restante da página pública seja exibido. */
        error_log('NF Blog: falha ao listar categorias públicas. Código: ' . $exception->getCode());
        return [];
    }
}
