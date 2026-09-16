<?php
// __DIR__ resolve o caminho a partir deste arquivo, não da página que o inclui.
require_once __DIR__ . '/constants.php';

// Padroniza o tratamento de falhas do mysqli por exceções.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // A variável $connection fica disponível para os arquivos que incluem este arquivo.
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // UTF-8 completo para acentos, caracteres especiais e emojis.
    $connection->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    // Registra somente o código técnico no log; não exibe credenciais ao visitante.
    error_log('NF Blog: falha na conexão MySQL. Código: ' . $exception->getCode());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

// Futuras consultas com dados de formulários devem usar prepare() e bind_param().
