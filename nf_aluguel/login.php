<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set((string) $appConfig['timezone']);
$pageTitle = 'Login';

require __DIR__ . '/includes/header.php';
?>
        <main class="standalone-content">
            <section class="placeholder-card">
                <span class="eyebrow">Acesso interno</span>
                <h1>Login</h1>
                <p>A autenticação do Sistema de Aluguel será implementada em uma etapa futura, com sessão própria e controle de acesso por perfil.</p>
                <a class="text-link" href="<?= htmlspecialchars($baseUrl . '/index.php', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Voltar ao início</a>
            </section>
        </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
