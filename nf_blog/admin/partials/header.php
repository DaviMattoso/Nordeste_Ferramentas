<?php
/**
 * Abre o documento HTML e renderiza a navegação compartilhada da homepage.
 *
 * Este partial inicia a sessão para apresentar login ou perfil, mas não protege
 * rotas por conta própria; a página que o inclui define sua regra de acesso.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';
?>

<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>NF Blog</title>

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública compartilhada; o conteúdo seguinte é definido pela página que inclui este partial. -->
        <?php require __DIR__ . '/../../partials/public-nav.php'; ?>
