<?php

/**
 * Abre a conexão mysqli exclusiva do aluguel.
 *
 * Não inclui a conexão do blog: ela seleciona nf_blog e segue os papéis author/admin.
 */

require_once __DIR__ . '/constants.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $connection = new mysqli(ALUGUEL_DB_HOST, ALUGUEL_DB_USER, ALUGUEL_DB_PASS, ALUGUEL_DB_NAME);
    $connection->set_charset('utf8mb4');

    /* Modo estrito impede gravar texto cortado ou data zero sem erro visível. */
    $connection->query(
        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
    );
    $connection->query("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (mysqli_sql_exception $exception) {
    error_log('NF Aluguel: falha na conexão MySQL. Código: ' . $exception->getCode());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados do aluguel.');
}
