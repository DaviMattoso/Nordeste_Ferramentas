<?php
/**
 * Exclui uma conta de usuário pelo painel.
 *
 * Impede autoexclusão e remoção do último administrador, respeita posts ligados
 * por chave estrangeira e apaga o avatar somente após o COMMIT.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-utils.php';
require_once __DIR__ . '/../config/flash.php';

/* A ação exige POST com CSRF para não ser disparada por uma simples URL. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setFlash('error', 'A exclusão de usuário exige envio pelo formulário.');
    header('Location: manage-users.php', true, 303);
    exit;
}
requireValidCsrfToken();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de usuário inválido.');
    header('Location: manage-users.php', true, 303);
    exit;
}

$inTransaction = false;
try {
    $connection->begin_transaction();
    $inTransaction = true;
    /* Os locks serializam tentativas concorrentes que poderiam remover o último admin. */
    $adminCount = lockedAdminCount($connection);
    $statement = $connection->prepare('SELECT avatar, role FROM users WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($avatar, $role);
    $found = $statement->fetch();
    $statement->close();
    if (!$found) {
        throw new DomainException('Usuário não encontrado.');
    }
    if ($_SESSION['user_id'] === $id) {
        /* A conta que mantém a sessão administrativa atual não pode excluir a si mesma. */
        throw new DomainException('Você não pode excluir sua própria conta.');
    }
    if ($role === 'admin' && $adminCount <= 1) {
        throw new DomainException('O último administrador não pode ser removido.');
    }
    $statement = $connection->prepare('DELETE FROM users WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
    $connection->commit();
    $inTransaction = false;
    /* O avatar só é apagado depois que o banco confirma a exclusão. */
    removeManagedUserAvatar($avatar);
    setFlash('success', 'Usuário excluído com sucesso.');
} catch (Throwable $exception) {
    if ($inTransaction) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackException) {
            error_log('NF Blog: falha ao desfazer exclusão de usuário. Código: ' . $rollbackException->getCode());
        }
    }
    /* A chave posts.author_id usa RESTRICT e retorna 1451 quando há autoria vinculada. */
    setFlash('error', $exception instanceof DomainException ? $exception->getMessage()
        : ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1451
            ? 'Não é possível excluir este usuário porque existem posts vinculados a ele.'
            : 'Não foi possível excluir o usuário. Tente novamente.'));
    if (!($exception instanceof DomainException)) {
        error_log('NF Blog: falha ao excluir usuário. Código: ' . $exception->getCode());
    }
}
header('Location: manage-users.php', true, 303);
exit;
