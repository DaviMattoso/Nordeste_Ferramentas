<?php
declare(strict_types=1);

$appConfig = isset($appConfig) && is_array($appConfig)
    ? $appConfig
    : require __DIR__ . '/../config/app.php';
$baseUrl = (string) ($appConfig['base_url'] ?? '');
$applicationName = (string) ($appConfig['name'] ?? 'Sistema de Aluguel');
$documentTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' | ' . $applicationName
    : $applicationName;
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />
        <title><?= htmlspecialchars($documentTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . '/assets/css/style.css', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" />
        <script src="<?= htmlspecialchars($baseUrl . '/assets/js/app.js', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" defer></script>
    </head>
    <body>
        <header class="app-header">
            <a class="app-brand" href="<?= htmlspecialchars($baseUrl . '/index.php', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <span class="app-brand__mark" aria-hidden="true">NF</span>
                <span>
                    <strong>Nordeste Ferramentas</strong>
                    <small>Sistema de Aluguel</small>
                </span>
            </a>
            <span class="status-badge">Arquitetura inicial</span>
        </header>
