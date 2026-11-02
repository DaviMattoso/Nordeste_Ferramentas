<?php

/**
 * Configuração central do NF Aluguel.
 *
 * Usa variáveis próprias para não herdar o banco do blog. NF_DB_NAME aponta
 * para nf_blog; reaproveitar essa constante misturaria patrimônio e posts.
 */

/* Um arquivo .env solto não entra no PHP. O servidor precisa exportar as variáveis. */
$environmentValue = static function (string $name, string $localDefault): string {
    $value = getenv($name);
    return is_string($value) && $value !== '' ? $value : $localDefault;
};

/* A barra final evita concatenar /aluguel com login.php e perder o separador. */
$rootUrl = rtrim($environmentValue('NF_ALUGUEL_ROOT_URL', 'http://localhost/aluguel/'), '/') . '/';
define('ALUGUEL_ROOT_URL', $rootUrl);

define('ALUGUEL_DB_HOST', $environmentValue('NF_ALUGUEL_DB_HOST', 'localhost'));
define('ALUGUEL_DB_USER', $environmentValue('NF_ALUGUEL_DB_USER', 'root'));
define('ALUGUEL_DB_PASS', $environmentValue('NF_ALUGUEL_DB_PASS', ''));
define('ALUGUEL_DB_NAME', $environmentValue('NF_ALUGUEL_DB_NAME', 'nf_aluguel'));

unset($environmentValue, $rootUrl);
