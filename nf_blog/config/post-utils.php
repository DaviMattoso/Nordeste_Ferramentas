<?php

/**
 * Funções compartilhadas pelo CRUD e pela apresentação de posts.
 *
 * Centraliza validação textual, categorias, resumos, datas, thumbnails e a
 * regra que limita autores aos próprios posts.
 */

/** Revalida no banco a conta que está prestes a criar ou gerenciar um post. */
function refreshPostActor(mysqli $connection): void
{
    try {
        /* Atualiza identidade e papel para não confiar em dados antigos da sessão. */
        $statement = $connection->prepare('SELECT username, avatar, role FROM users WHERE id = ?');
        $statement->bind_param('i', $_SESSION['user_id']);
        $statement->execute();
        $statement->bind_result($username, $avatar, $role);
        $found = $statement->fetch() === true;
        $statement->close();
    } catch (Throwable $exception) {
        error_log('NF Blog: falha ao verificar autor do post. Código: ' . $exception->getCode());
        http_response_code(500);
        exit('Não foi possível verificar sua conta.');
    }
    if (!$found) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['avatar']);
        header('Location: ../signin.php', true, 303);
        exit;
    }
    $_SESSION['username'] = $username;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['role'] = $role;
}

/** Valida título e conteúdo conforme os limites das colunas do banco. */
function postValidationErrors(string $title, string $body): array
{
    $errors = [];
    if ($title === '') {
        $errors[] = 'Informe o título do post.';
    }
    if (trim($body) === '') {
        $errors[] = 'Informe o conteúdo do post.';
    }
    if (preg_match('//u', $title) !== 1 || preg_match('//u', $body) !== 1) {
        $errors[] = 'Há texto com codificação inválida no formulário.';
        return $errors;
    }
    if (preg_match_all('/./us', $title) > 255) {
        $errors[] = 'O título deve ter no máximo 255 caracteres.';
    }
    if (strlen($body) > 65535) {
        $errors[] = 'O conteúdo é longo demais.';
    }
    return $errors;
}

/** Produz texto simples e curto para cards sem cortar marcação HTML no meio. */
function postExcerpt(string $body, int $maxLength = 220): string
{
    /* A remoção de tags ocorre antes do corte para não produzir marcação incompleta. */
    $text = trim(strip_tags($body));
    /* Quebras e espaços repetidos são normalizados para o resumo ocupar uma linha contínua. */
    $normalizedText = preg_replace('/\s+/u', ' ', $text);
    if ($normalizedText !== null) {
        $text = $normalizedText;
    }
    if ($text === '' || $maxLength < 1) {
        return '';
    }
    /* O corte considera caracteres Unicode e não divide letras acentuadas por byte. */
    $length = preg_match_all('/./us', $text, $characters);
    if ($length === false || $length <= $maxLength) {
        return $text;
    }
    return implode('', array_slice($characters[0], 0, $maxLength)) . '…';
}

/** Formata a data armazenada somente para exibição, preservando o valor original. */
function postDisplayDate(string $createdAt): string
{
    $timestamp = strtotime($createdAt);
    return $timestamp === false ? $createdAt : date('d/m/Y - H:i', $timestamp);
}

/** Normaliza o valor opcional do checkbox de destaque e rejeita entradas inesperadas. */
function postFeaturedInput($input): array
{
    /* Checkbox desmarcado não é enviado pelo navegador; por isso null equivale a zero. */
    if ($input === null || $input === '0') {
        return [0, null];
    }
    if ($input === '1') {
        return [1, null];
    }
    return [0, 'Valor de destaque inválido.'];
}

/** Retorna as opções atuais do seletor de categoria no CRUD de posts. */
function postCategories(mysqli $connection): array
{
    $categories = [];
    $result = $connection->query('SELECT id, title FROM categories ORDER BY title ASC, id ASC');
    while ($category = $result->fetch_assoc()) {
        $categories[] = $category;
    }
    $result->free();
    return $categories;
}

/** Confirma que uma categoria recebida do formulário ainda existe. */
function postCategoryExists(mysqli $connection, int $categoryId): bool
{
    $statement = $connection->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
    $statement->bind_param('i', $categoryId);
    $statement->execute();
    $statement->bind_result($foundId);
    $exists = $statement->fetch() === true;
    $statement->close();
    return $exists;
}

/**
 * Valida uma thumbnail por estado do upload, tamanho, MIME real e leitura da imagem.
 * Retorna a extensão confiável e uma lista de erros; na edição o arquivo é opcional.
 */
function postThumbnailValidation($upload, bool $required): array
{
    if ($upload === null) {
        return [null, $required ? ['Selecione uma thumbnail para o post.'] : []];
    }
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
        return [null, ['Arquivo de thumbnail inválido.']];
    }
    if ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, $required ? ['Selecione uma thumbnail para o post.'] : []];
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        return [null, ['Falha no envio da thumbnail. Envie uma imagem de até 5 MB.']];
    }
    if (!is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
        return [null, ['Arquivo de thumbnail inválido.']];
    }
    $size = filesize($upload['tmp_name']);
    if ($size === false || $size > 5 * 1024 * 1024) {
        return [null, ['A thumbnail deve ter no máximo 5 MB.']];
    }
    /* MIME e metadados da imagem evitam confiar na extensão fornecida pelo navegador. */
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $image = @getimagesize($upload['tmp_name']);
    if (!isset($allowedTypes[$mime]) || $image === false || $image['mime'] !== $mime) {
        return [null, ['Arquivo de thumbnail inválido. Use JPG, PNG ou WebP.']];
    }
    return [$allowedTypes[$mime], []];
}

/** Salva uma thumbnail validada com nome aleatório e retorna seu caminho relativo. */
function savePostThumbnail(array $upload, string $extension): string
{
    /* O nome criptograficamente aleatório evita colisões e nomes controlados pelo usuário. */
    $directory = __DIR__ . '/../Images/posts';
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Falha ao criar diretório de thumbnails.');
    }
    $thumbnail = 'Images/posts/' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (!@move_uploaded_file($upload['tmp_name'], __DIR__ . '/../' . $thumbnail)) {
        throw new RuntimeException('Falha ao salvar thumbnail.');
    }
    return $thumbnail;
}

/** Remove somente thumbnails cujo caminho corresponda ao padrão gerenciado. */
function removeManagedPostThumbnail(?string $thumbnail): void
{
    /* A lista permitida impede que um valor inesperado provoque exclusão fora de Images/posts. */
    if ($thumbnail === null || !preg_match('~\AImages/posts/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $thumbnail)) {
        return;
    }
    $path = __DIR__ . '/../' . $thumbnail;
    if (is_file($path) && !@unlink($path)) {
        error_log('NF Blog: falha ao remover thumbnail gerenciada.');
    }
}

/** Autoriza administradores em qualquer post e autores apenas nos próprios registros. */
function canManagePost(int $authorId): bool
{
    return isAdmin() || $_SESSION['user_id'] === $authorId;
}
