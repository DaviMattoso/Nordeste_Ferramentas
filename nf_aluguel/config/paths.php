<?php
declare(strict_types=1);

/**
 * Caminhos internos do módulo.
 *
 * São caminhos do sistema de arquivos; URLs públicas permanecem em app.php.
 */

$rootPath = dirname(__DIR__);

return [
    'root' => $rootPath,
    'api' => $rootPath . DIRECTORY_SEPARATOR . 'api',
    'assets' => $rootPath . DIRECTORY_SEPARATOR . 'assets',
    'config' => $rootPath . DIRECTORY_SEPARATOR . 'config',
    'database' => $rootPath . DIRECTORY_SEPARATOR . 'database',
    'docs' => $rootPath . DIRECTORY_SEPARATOR . 'docs',
    'includes' => $rootPath . DIRECTORY_SEPARATOR . 'includes',
    'modules' => $rootPath . DIRECTORY_SEPARATOR . 'modules',
    'storage' => $rootPath . DIRECTORY_SEPARATOR . 'storage',
    'logs' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
    'uploads' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads',
];
