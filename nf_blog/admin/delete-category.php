<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setFlash('error', 'A exclusão de categoria exige envio pelo formulário.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de categoria inválido.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

$inTransaction = false;
try {
    $connection->begin_transaction();
    $inTransaction = true;
    $statement = $connection->prepare('SELECT id FROM categories WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($foundId);
    $found = $statement->fetch() === true;
    $statement->close();
    if (!$found) {
        throw new DomainException('Categoria não encontrada.');
    }
    $statement = $connection->prepare('DELETE FROM categories WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
    $connection->commit();
    $inTransaction = false;
    setFlash('success', 'Categoria excluída com sucesso.');
} catch (Throwable $exception) {
    if ($inTransaction) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackException) {
            error_log('NF Blog: falha ao desfazer exclusão de categoria. Código: ' . $rollbackException->getCode());
        }
    }
    setFlash('error', $exception instanceof DomainException
        ? $exception->getMessage()
        : ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1451
            ? 'Não é possível excluir esta categoria porque existem posts vinculados a ela.'
            : 'Não foi possível excluir a categoria. Tente novamente.'));
    if (!($exception instanceof DomainException)) {
        error_log('NF Blog: falha ao excluir categoria. Código: ' . $exception->getCode());
    }
}
header('Location: manage-categories.php', true, 303);
exit;
