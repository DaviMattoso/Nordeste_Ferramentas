<?php
declare(strict_types=1);

/**
 * Configuração da futura conexão PDO do Sistema de Aluguel.
 *
 * O módulo utilizará o mesmo banco MySQL do restante da Nordeste Ferramentas
 * e, por isso, reaproveita as variáveis NF_DB_* já adotadas pelo NF Blog.
 * Incluir este arquivo apenas retorna configurações: nenhuma conexão, migration
 * ou alteração no banco é executada automaticamente.
 */

$environmentValue = static function (string $name, string $localDefault): string {
    $value = getenv($name);
    return is_string($value) && $value !== '' ? $value : $localDefault;
};

$config = [
    'driver' => 'mysql',
    'host' => $environmentValue('NF_DB_HOST', 'localhost'),
    'port' => $environmentValue('NF_DB_PORT', '3306'),
    'database' => $environmentValue('NF_DB_NAME', 'nf_blog'),
    'username' => $environmentValue('NF_DB_USER', 'root'),
    'password' => $environmentValue('NF_DB_PASS', ''),
    'charset' => 'utf8mb4',
    'pdo_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

unset($environmentValue);

return $config;
