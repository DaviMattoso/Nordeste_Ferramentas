<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set((string) $appConfig['timezone']);
$pageTitle = 'Início';

require __DIR__ . '/includes/header.php';
?>
        <div class="app-layout">
            <?php require __DIR__ . '/includes/sidebar.php'; ?>
            <main class="app-content">
                <section class="welcome-card">
                    <span class="eyebrow">Módulo interno</span>
                    <h1>Sistema de Aluguel — Nordeste Ferramentas</h1>
                    <p>A arquitetura inicial está pronta para receber as funcionalidades do sistema. Nenhum fluxo operacional foi implementado nesta etapa.</p>
                </section>

                <section class="concept-grid" aria-label="Conceitos centrais">
                    <article class="concept-card">
                        <h2>Produto</h2>
                        <p>Representa o modelo comercial, como “Furadeira Bosch GSB 13 RE”.</p>
                    </article>
                    <article class="concept-card concept-card--highlight">
                        <h2>Patrimônio</h2>
                        <p>Representa cada unidade física, com identificação, status e histórico próprios.</p>
                    </article>
                </section>
            </main>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
