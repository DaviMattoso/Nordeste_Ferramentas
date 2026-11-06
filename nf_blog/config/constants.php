<?php

/**
 * Configuração central do NF Blog.
 *
 * Lê URL e acesso ao MySQL de variáveis NF_* e mantém valores locais como
 * fallback. As credenciais são consumidas somente por config/database.php.
 */

/* Evita persistir configurações de produção no repositório. */
$environmentValue = static function (string $name, string $localDefault): string {
    $value = getenv($name);
    return is_string($value) && $value !== '' ? $value : $localDefault;
};

/*
 * ROOT_URL é a base pública do blog nesta instalação do XAMPP.
 * A pasta publicada é /Nordeste_Ferramentas/nf_blog/; /blog/ não existe no Apache.
 * A barra final permanece porque páginas e redirects concatenam o caminho.
 */
$rootUrl = rtrim($environmentValue('NF_ROOT_URL', 'http://localhost/Nordeste_Ferramentas/nf_blog/'), '/') . '/';
define('ROOT_URL', $rootUrl);

/* Parâmetros usados pela conexão mysqli compartilhada. */
define('DB_HOST', $environmentValue('NF_DB_HOST', 'localhost'));
define('DB_USER', $environmentValue('NF_DB_USER', 'root'));
define('DB_PASS', $environmentValue('NF_DB_PASS', ''));
define('DB_NAME', $environmentValue('NF_DB_NAME', 'nf_blog'));

unset($environmentValue, $rootUrl);
