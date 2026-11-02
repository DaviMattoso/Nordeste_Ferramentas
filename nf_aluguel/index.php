<?php

/**
 * Página inicial do módulo enquanto login e cadastros ainda não existem.
 *
 * Confirma a conexão e as migrações de fundação sem mostrar estrutura, usuário ou senha.
 */

require_once __DIR__ . '/config/database.php';

$requiredMigrations = [
    '20261102-0050-create-schema-migrations.sql',
    '20261102-0100-create-identity-and-audit.sql',
    '20261102-0110-create-catalog-and-warehouse.sql',
    '20261102-0120-create-customers-and-rentals.sql',
    '20261102-0130-create-operations-and-finance.sql',
];

$foundationReady = false;

try {
    $statement = $connection->prepare(
        'SELECT COUNT(*) FROM schema_migrations WHERE filename IN (?, ?, ?, ?, ?)'
    );
    /* Variáveis nomeadas: bind_param exige referências e não aceita o array direto. */
    $migrationControl = $requiredMigrations[0];
    $migrationIdentity = $requiredMigrations[1];
    $migrationCatalog = $requiredMigrations[2];
    $migrationRentals = $requiredMigrations[3];
    $migrationOperations = $requiredMigrations[4];
    $statement->bind_param(
        'sssss',
        $migrationControl,
        $migrationIdentity,
        $migrationCatalog,
        $migrationRentals,
        $migrationOperations
    );
    $statement->execute();
    $statement->bind_result($appliedCount);
    $statement->fetch();
    $statement->close();
    $foundationReady = (int) $appliedCount === count($requiredMigrations);
} catch (mysqli_sql_exception $exception) {
    error_log('NF Aluguel: falha ao consultar migrações. Código: ' . $exception->getCode());
    http_response_code(500);
    exit('Não foi possível confirmar a base do aluguel.');
}

$styleVersion = (int) filemtime(__DIR__ . '/css/style.css');
$pageTitle = $foundationReady
    ? 'Base do aluguel preparada'
    : 'Base do aluguel incompleta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | Nordeste Ferramentas</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo $styleVersion; ?>">
</head>
<body>
    <header class="topbar">
        <p class="brand">Nordeste Ferramentas</p>
    </header>
    <main class="panel">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($foundationReady) : ?>
            <p>
                O módulo respondeu e a estrutura inicial do banco está registrada.
                Login, cadastros e o fluxo de aluguel entram nas próximas etapas.
            </p>
        <?php else : ?>
            <p>
                A conexão foi aberta, mas as migrações de fundação não estão todas registradas.
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
