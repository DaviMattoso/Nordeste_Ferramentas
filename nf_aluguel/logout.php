<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set((string) $appConfig['timezone']);
$pageTitle = 'Logout';

/* Nenhuma sessão é alterada enquanto a autenticação do módulo não existir. */
require __DIR__ . '/includes/header.php';
?>
        <main class="standalone-content">
            <section class="placeholder-card">
                <span class="eyebrow">Funcionalidade futura</span>
                <h1>Logout</h1>
                <p>O encerramento da sessão será implementado junto da autenticação exclusiva do Sistema de Aluguel. A sessão do NF Blog permanece intocada.</p>
                <a class="text-link" href="<?= htmlspecialchars($baseUrl . '/index.php', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Voltar ao início</a>
            </section>
        </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
