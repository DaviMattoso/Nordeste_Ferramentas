<?php
/**
 * Lista administrativa de categorias.
 *
 * Restringe o acesso a administradores e apresenta os registros que podem ser
 * editados ou excluídos pelos fluxos protegidos correspondentes.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/flash.php';

$flash = getFlash();
$categories = [];
$listFailed = false;
try {
    /* A consulta não recebe filtros externos e mantém uma ordem estável pelo ID. */
    $result = $connection->query('SELECT id, title, description, created_at, updated_at FROM categories ORDER BY id ASC');
    while ($category = $result->fetch_assoc()) {
        $categories[] = $category;
    }
    $result->free();
} catch (Throwable $exception) {
    error_log('NF Blog: falha ao listar categorias. Código: ' . $exception->getCode());
    $listFailed = true;
    $flash = ['type' => 'error', 'message' => 'Não foi possível carregar as categorias. Tente novamente.'];
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

        <!-- Navegação do blog com acesso ao perfil administrativo. -->
        <?php $blogBase = '../'; $dashboardHref = 'dashboard.php'; require __DIR__ . '/../partials/public-nav.php'; ?>

        <!-- Menu de gestão e tabela das categorias cadastradas. -->
        <section class="dashboard">
            <div class="container dashboard__container">
                <aside>
                    <ul>
                        <li>
                            <a href="add-post.php">
                                <i class="fa-solid fa-pen"></i>
                                <h5>Adicionar Publicação</h5>
                            </a>
                        </li>

                        <li>
                            <a href="dashboard.php">
                                <i class="fa-solid fa-file-image"></i>
                                <h5>Dashboard</h5>
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="add-user.php">
                                <i class="fa-solid fa-user-plus"></i>
                                <h5>Adicionar Usuário</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="manage-users.php">
                                <i class="fa-solid fa-user"></i>
                                <h5>Gerenciar Usuário</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a href="add-category.php">
                                <i class="fa-regular fa-pen-to-square"></i>
                                <h5>Adicionar Categoria</h5>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li class="active">
                            <a href="manage-categories.php">
                                <i class="fa-solid fa-list"></i>
                                <h5>Gerenciar Categorias</h5>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <main class="dashboard__content">
                    <h2>Gerenciar Categorias</h2>
                    <?php if ($flash): ?>
                    <div class="alert__message <?= authEscape($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
                        <p><?= authEscape($flash['message']) ?></p>
                    </div>
                    <?php endif; ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Descrição</th>
                                <th>Editar</th>
                                <th>Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= authEscape($category['title']) ?></td>
                                <td><?= nl2br(authEscape($category['description'] ?? '')) ?></td>
                                <td><a href="edit-category.php?id=<?= (int) $category['id'] ?>" class="btn sm">Editar</a></td>
                                <td>
                                    <form action="delete-category.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $category['id'] ?>" />
                                        <button type="submit" class="btn sm danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$categories && !$listFailed): ?>
                            <tr><td colspan="4">Nenhuma categoria encontrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </main>
            </div>
        </section>

        <!-- Rodapé compartilhado pelas páginas do painel. -->
        <?php $blogBase = '../'; require __DIR__ . '/../partials/public-footer.php'; ?>
    </body>
</html>
