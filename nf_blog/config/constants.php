<?php

// Variáveis NF_* permitem configurar produção sem armazenar credenciais no Git.
// Quando não estão definidas, os mesmos valores locais do XAMPP continuam ativos.
$environmentValue = static function (string $name, string $localDefault): string {
    $value = getenv($name);
    return is_string($value) && $value !== '' ? $value : $localDefault;
};

// A barra final mantém compatibilidade com todas as URLs montadas pela aplicação.
$rootUrl = rtrim($environmentValue('NF_ROOT_URL', 'http://localhost/blog/'), '/') . '/';
define('ROOT_URL', $rootUrl);

// Credenciais consumidas exclusivamente por config/database.php.
define('DB_HOST', $environmentValue('NF_DB_HOST', 'localhost'));
define('DB_USER', $environmentValue('NF_DB_USER', 'root'));
define('DB_PASS', $environmentValue('NF_DB_PASS', ''));
define('DB_NAME', $environmentValue('NF_DB_NAME', 'nf_blog'));

unset($environmentValue, $rootUrl);
