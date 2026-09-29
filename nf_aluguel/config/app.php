<?php
declare(strict_types=1);

/**
 * Configurações gerais do Sistema de Aluguel.
 *
 * Este arquivo não contém regras de negócio. Valores específicos de cada
 * ambiente devem ser fornecidos pelo servidor, sem serem versionados.
 */

$environmentValue = static function (string $name, string $localDefault): string {
    $value = getenv($name);
    return is_string($value) && $value !== '' ? $value : $localDefault;
};

$config = [
    'name' => 'Sistema de Aluguel — Nordeste Ferramentas',
    'environment' => $environmentValue('NF_ALUGUEL_ENV', 'local'),
    'timezone' => $environmentValue('NF_ALUGUEL_TIMEZONE', 'America/Rio_Branco'),
    'base_url' => rtrim($environmentValue('NF_ALUGUEL_BASE_URL', '/nf_aluguel'), '/'),
];

unset($environmentValue);

return $config;
