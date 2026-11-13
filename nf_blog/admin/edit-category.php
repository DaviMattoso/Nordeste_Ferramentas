<?php
/**
 * Edita uma categoria existente.
 *
 * Valida o ID e os campos, reabre o registro com lock dentro da transação e
 * preserva a unicidade do título antes de confirmar a alteração.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/category-utils.php';

/* O ID da URL só entra nas consultas depois de validado como inteiro positivo. */
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    setFlash('error', 'ID de categoria inválido.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

try {
    /* A leitura inicial preenche o formulário e detecta IDs inexistentes no GET. */
    $statement = $connection->prepare('SELECT title, description FROM categories WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->bind_result($storedTitle, $storedDescription);
    $found = $statement->fetch();
    $statement->close();
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao buscar categoria. Código: ' . $exception->getCode());
    setFlash('error', 'Não foi possível carregar a categoria. Tente novamente.');
    header('Location: manage-categories.php', true, 303);
    exit;
}
if (!$found) {
    setFlash('error', 'Categoria não encontrada.');
    header('Location: manage-categories.php', true, 303);
    exit;
}

$title = $storedTitle;
$description = $storedDescription ?? '';
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* O servidor repete a validação porque os controles do HTML podem ser contornados. */
    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $descriptionInput = $_POST['description'] ?? '';
    $description = is_string($descriptionInput) ? $descriptionInput : '';
    $errors = categoryValidationErrors($title, $description);
    if (!is_string($descriptionInput)) {
        $errors[] = 'Descrição inválida.';
    }

    if (!$errors) {
        $inTransaction = false;
        try {
            $connection->begin_transaction();
            $inTransaction = true;
            /* FOR UPDATE revalida a existência e estabiliza o registro durante o UPDATE. */
            $statement = $connection->prepare('SELECT id FROM categories WHERE id = ? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->bind_result($foundId);
            $stillExists = $statement->fetch() === true;
            $statement->close();
            if (!$stillExists) {
                throw new DomainException('Categoria não encontrada.');
            }
            if (categoryTitleExists($connection, $title, $id)) {
                /* Em duplicidade, encerra a transação sem persistir alterações. */
                $errors[] = 'Categoria já existe.';
                $connection->rollback();
                $inTransaction = false;
            } else {
                $storedDescription = $description === '' ? null : $description;
                $statement = $connection->prepare('UPDATE categories SET title = ?, description = ? WHERE id = ?');
                $statement->bind_param('ssi', $title, $storedDescription, $id);
                $statement->execute();
                $statement->close();
                $connection->commit();
                $inTransaction = false;
                setFlash('success', 'Categoria atualizada com sucesso.');
                header('Location: manage-categories.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            /* Qualquer falha desfaz a transação para não deixar estado parcial. */
            if ($inTransaction) {
                try {
                    $connection->rollback();
                } catch (Throwable $rollbackException) {
                    error_log('NF Blog: falha ao desfazer edição de categoria. Código: ' . $rollbackException->getCode());
                }
            }
            if ($exception instanceof DomainException) {
                setFlash('error', $exception->getMessage());
                header('Location: manage-categories.php', true, 303);
                exit;
            }
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Categoria já existe.'
                : 'Não foi possível atualizar a categoria. Tente novamente.';
            error_log('NF Blog: falha ao atualizar categoria. Código: ' . $exception->getCode());
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>NF Blog</title>

        <link rel="icon" href="../Images/favicon.ico" />

        <link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação do blog com caminhos relativos ao diretório administrativo. -->
        <?php $blogBase = '../'; $dashboardHref = 'dashboard.php'; require __DIR__ . '/../partials/public-nav.php'; ?>

        <!-- Formulário preenchido com a categoria carregada e revalidada no backend. -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Editar categoria</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="edit-category.php?id=<?= (int) $id ?>" method="POST">
                    <?= csrfField() ?>
                    <input type="text" name="title" placeholder="Título" maxlength="150" value="<?= authEscape($title) ?>" required />
                    <textarea name="description" rows="4" placeholder="Descrição"><?= authEscape($description) ?></textarea>
                    <div class="form__control">
                        <button type="submit" class="btn">Editar</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
