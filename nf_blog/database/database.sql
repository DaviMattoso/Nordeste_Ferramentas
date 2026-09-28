-- Estrutura inicial do banco do NF Blog.
-- Pode ser importada pelo phpMyAdmin: IF NOT EXISTS preserva estruturas e dados existentes.
CREATE DATABASE IF NOT EXISTS nf_blog
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nf_blog;

-- Contas usadas na autenticação e na autoria das publicações.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    -- Recebe somente hashes produzidos por password_hash(); senhas puras não são persistidas.
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL DEFAULT NULL,
    role ENUM('admin', 'author') NOT NULL DEFAULT 'author',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classificação pública dos posts e opções dos formulários administrativos.
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY categories_title_unique (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conteúdo publicado. O modelo atual considera todo registro público e usa is_featured
-- apenas para escolher o destaque da homepage; ainda não existe status de rascunho.
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    thumbnail VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY posts_category_idx (category_id),
    KEY posts_author_idx (author_id),
    KEY posts_featured_idx (is_featured),
    CONSTRAINT posts_category_fk
        FOREIGN KEY (category_id) REFERENCES categories(id)
        -- RESTRICT evita excluir uma categoria enquanto houver posts associados.
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT posts_author_fk
        FOREIGN KEY (author_id) REFERENCES users(id)
        -- RESTRICT preserva a autoria e exige tratar os posts antes de excluir a conta.
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
