-- NF Aluguel. Banco exclusivo: nf_aluguel.
-- Não executar no nf_blog: as tabelas do blog já usam outros nomes e outras regras.
-- O registro em schema_migrations é feito por quem aplica o arquivo, só depois do sucesso.
-- DDL no MariaDB confirma a transação implicitamente; um ROLLBACK não desfaz este CREATE.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE schema_migrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY schema_migrations_filename_unique (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
