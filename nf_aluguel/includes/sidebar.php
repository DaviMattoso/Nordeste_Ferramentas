<?php
declare(strict_types=1);

$plannedModules = [
    'Clientes',
    'Categorias',
    'Produtos',
    'Patrimônio',
    'Estoque',
    'Reservas',
    'Aluguéis',
    'Devoluções',
    'Entregas',
    'Inspeções',
    'Danos',
    'Manutenção',
    'Relatórios',
    'Configurações',
];
?>
<aside class="app-sidebar" aria-label="Módulos planejados">
    <a class="app-sidebar__active" href="<?= htmlspecialchars($baseUrl . '/index.php', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Dashboard</a>
    <p class="app-sidebar__label">Módulos futuros</p>
    <ul>
        <?php foreach ($plannedModules as $moduleName): ?>
        <li><span aria-disabled="true"><?= htmlspecialchars($moduleName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></li>
        <?php endforeach; ?>
    </ul>
</aside>
