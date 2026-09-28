<?php
/**
 * Cria a conexão MySQL compartilhada pelo blog.
 *
 * Expõe `$connection` aos arquivos que incluem este módulo, aplica UTF-8
 * completo e impede que detalhes de conexão sejam enviados ao navegador.
 */

/* `__DIR__` mantém a inclusão estável independentemente da página solicitada. */
require_once __DIR__ . '/constants.php';

/* Exceções permitem que consultas e transações adotem um tratamento uniforme. */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    /* A aplicação reutiliza esta instância em consultas diretas e preparadas. */
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    /* utf8mb4 preserva acentos, símbolos e caracteres fora do plano básico. */
    $connection->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    /* O log recebe apenas o código; host, usuário e senha não são expostos. */
    error_log('NF Blog: falha na conexão MySQL. Código: ' . $exception->getCode());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

/* Consultas com entrada externa devem continuar usando prepare() e bind_param(). */
