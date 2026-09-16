-- Importar pelo phpMyAdmin. Não apaga nem substitui tabelas ou dados existentes.
CREATE DATABASE IF NOT EXISTS nf_blog
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nf_blog;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    -- Armazenar somente hashes gerados por password_hash(), nunca senhas em texto puro.
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL DEFAULT NULL,
    role ENUM('admin', 'author') NOT NULL DEFAULT 'author',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
