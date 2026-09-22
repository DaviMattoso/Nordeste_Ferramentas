<?php

function userFieldErrors(array $values): array
{
    $errors = [];
    if (in_array('', $values, true)) {
        $errors[] = 'Preencha todos os campos obrigatórios.';
    }
    foreach (['first_name' => 100, 'last_name' => 100, 'username' => 100, 'email' => 254] as $field => $limit) {
        if (preg_match('//u', $values[$field]) !== 1) {
            $errors[] = 'Há texto com codificação inválida no formulário.';
            break;
        }
        if (preg_match_all('/./us', $values[$field]) > $limit) {
            $errors[] = 'Nome, sobrenome e username permitem até 100 caracteres; email, até 254.';
            break;
        }
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    return $errors;
}

function userPasswordErrors(string $password, string $confirmation): array
{
    $errors = [];
    if (preg_match('//u', $password) !== 1 || preg_match_all('/./us', $password) < 8) {
        $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    // PASSWORD_DEFAULT usa bcrypt atualmente, que considera somente 72 bytes.
    if (strlen($password) > 72 || strpos($password, "\0") !== false) {
        $errors[] = 'A senha deve ter no máximo 72 bytes e não pode conter caracteres nulos.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'As senhas não coincidem.';
    }
    return $errors;
}

function userAvatarValidation($upload): array
{
    if ($upload === null) {
        return [null, []];
    }
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
        return [null, ['Arquivo de avatar inválido.']];
    }
    if ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, []];
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        return [null, ['Falha no envio do avatar. Envie uma imagem de até 2 MB.']];
    }
    if (!is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
        return [null, ['Arquivo de avatar inválido.']];
    }
    if (filesize($upload['tmp_name']) > 2 * 1024 * 1024) {
        return [null, ['O avatar deve ter no máximo 2 MB.']];
    }
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $image = @getimagesize($upload['tmp_name']);
    if (!isset($allowedTypes[$mime]) || $image === false || $image['mime'] !== $mime) {
        return [null, ['Arquivo de avatar inválido. Use JPG, PNG ou WebP.']];
    }
    return [$allowedTypes[$mime], []];
}

function saveUserAvatar(array $upload, string $extension): string
{
    $directory = __DIR__ . '/../Images/avatars';
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Falha ao criar diretório de avatars.');
    }
    $avatar = 'Images/avatars/' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (!@move_uploaded_file($upload['tmp_name'], __DIR__ . '/../' . $avatar)) {
        throw new RuntimeException('Falha ao salvar avatar.');
    }
    return $avatar;
}

function removeManagedUserAvatar(?string $avatar): void
{
    if ($avatar === null || !preg_match('~\AImages/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $avatar)) {
        return;
    }
    $path = __DIR__ . '/../' . $avatar;
    if (is_file($path) && !@unlink($path)) {
        error_log('NF Blog: falha ao remover avatar gerenciado.');
    }
}

function userDuplicateErrors(mysqli $connection, array $values, ?int $excludedId = null): array
{
    if ($excludedId === null) {
        $statement = $connection->prepare('SELECT username = ?, email = ? FROM users WHERE username = ? OR email = ?');
        $statement->bind_param('ssss', $values['username'], $values['email'], $values['username'], $values['email']);
    } else {
        $statement = $connection->prepare('SELECT username = ?, email = ? FROM users WHERE (username = ? OR email = ?) AND id <> ?');
        $statement->bind_param('ssssi', $values['username'], $values['email'], $values['username'], $values['email'], $excludedId);
    }
    $statement->execute();
    $statement->bind_result($sameUsername, $sameEmail);
    $errors = [];
    while ($statement->fetch()) {
        if ($sameUsername) {
            $errors[] = 'Username já está em uso.';
        }
        if ($sameEmail) {
            $errors[] = 'Email já está cadastrado.';
        }
    }
    $statement->close();
    return array_unique($errors);
}

function lockedAdminCount(mysqli $connection): int
{
    // Bloqueia os administradores atuais até o COMMIT das alterações de role/exclusão.
    $statement = $connection->prepare("SELECT id FROM users WHERE role = 'admin' ORDER BY id FOR UPDATE");
    $statement->execute();
    $statement->bind_result($adminId);
    $count = 0;
    while ($statement->fetch()) {
        $count++;
    }
    $statement->close();
    return $count;
}
