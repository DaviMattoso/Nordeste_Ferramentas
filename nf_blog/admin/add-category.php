<?php
/**
 * Formulário administrativo de criação de categorias.
 *
 * Restringe a operação a administradores, valida título e descrição e utiliza
 * o índice único do banco como proteção final contra cadastros concorrentes.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';
require_once __DIR__ . '/../config/category-utils.php';

/* Preserva os campos preenchidos para repopular o formulário após uma falha. */
$title = '';
$description = '';
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireValidCsrfToken();
    /* O servidor verifica tipos mesmo que o formulário HTML controle a interface comum. */
    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $descriptionInput = $_POST['description'] ?? '';
    $description = is_string($descriptionInput) ? $descriptionInput : '';
    $errors = categoryValidationErrors($title, $description);
    if (!is_string($descriptionInput)) {
        $errors[] = 'Descrição inválida.';
    }

    if (!$errors) {
        try {
            /* A consulta antecipa uma mensagem clara; o índice UNIQUE resolve a concorrência. */
            if (categoryTitleExists($connection, $title)) {
                $errors[] = 'Categoria já existe.';
            } else {
                /* Descrição vazia vira NULL para representar a ausência do campo opcional. */
                $storedDescription = $description === '' ? null : $description;
                $statement = $connection->prepare('INSERT INTO categories (title, description) VALUES (?, ?)');
                $statement->bind_param('ss', $title, $storedDescription);
                $statement->execute();
                $statement->close();
                setFlash('success', 'Categoria criada com sucesso.');
                header('Location: manage-categories.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            $errors[] = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
                ? 'Categoria já existe.'
                : 'Não foi possível criar a categoria. Tente novamente.';
            error_log('NF Blog: falha ao criar categoria. Código: ' . $exception->getCode());
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

        <!-- Formulário administrativo de criação de categoria. -->
        <section class="form__section">
            <div class="container form__section-container">
                <h2>Adicionar categoria</h2>
                <?php if ($errors): ?>
                <div class="alert__message error" role="alert">
                    <?php foreach (array_unique($errors) as $error): ?>
                    <p><?= authEscape($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form action="add-category.php" method="POST">
                    <?= csrfField() ?>
                    <input type="text" name="title" placeholder="Título" maxlength="150" value="<?= authEscape($title) ?>" required />
                    <textarea name="description" rows="4" placeholder="Descrição"><?= authEscape($description) ?></textarea>
                    <div class="form__control">
                        <button type="submit" class="btn">
                            Adicionar categoria
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
