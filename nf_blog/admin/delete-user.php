<?php
// Exclusão de usuário é restrita a administradores autenticados.
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-utils.php';
require_once __DIR__ . '/../config/flash.php';

// Impede exclusões acionadas por URL/GET; a ação deve vir do formulário do painel.
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
    // Os locks impedem corrida entre duas tentativas de remover administradores.
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
        // Evita que o usuário destrua a própria sessão administrativa ativa.
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
    // O avatar só é apagado quando a exclusão do banco já foi confirmada.
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
    // O erro 1451 representa a FOREIGN KEY RESTRICT de posts.author_id.
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
