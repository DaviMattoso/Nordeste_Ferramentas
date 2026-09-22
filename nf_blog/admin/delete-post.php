<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/post-utils.php';
refreshPostActor($connection);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setFlash('error', 'A exclusão de post exige envio pelo formulário.');
    header('Location: dashboard.php', true, 303);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de post inválido.');
    header('Location: dashboard.php', true, 303);
    exit;
}

$inTransaction = false;
try {
    $connection->begin_transaction();
    $inTransaction = true;
    $statement = $connection->prepare('SELECT author_id, thumbnail FROM posts WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($authorId, $thumbnail);
    $found = $statement->fetch() === true;
    $statement->close();
    if (!$found) {
        throw new DomainException('Post não encontrado.');
    }
    if (!canManagePost((int) $authorId)) {
        throw new DomainException('Você não tem permissão para excluir este post.');
    }
    $statement = $connection->prepare('DELETE FROM posts WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
    $connection->commit();
    $inTransaction = false;
    removeManagedPostThumbnail($thumbnail);
    setFlash('success', 'Post excluído com sucesso.');
} catch (Throwable $exception) {
    if ($inTransaction) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackException) {
            error_log('NF Blog: falha ao desfazer exclusão de post. Código: ' . $rollbackException->getCode());
        }
    }
    setFlash('error', $exception instanceof DomainException
        ? $exception->getMessage()
        : 'Não foi possível excluir o post. Tente novamente.');
    if (!($exception instanceof DomainException)) {
        error_log('NF Blog: falha ao excluir post. Código: ' . $exception->getCode());
    }
}
header('Location: dashboard.php', true, 303);
exit;
