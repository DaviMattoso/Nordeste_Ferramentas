-- Fundação de identidade do NF Aluguel.
-- Papéis e permissões iniciais traduzem a seção 3 da especificação.
-- Não há usuário administrador semeado: a senha não entra no repositório.
-- O limite numérico de desconto do atendimento não está definido; a permissão
-- pricing.discount apenas permite aplicar desconto. pricing.override fica no administrador.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    username VARCHAR(100) NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_username_unique (username),
    -- Rejeita senha em texto puro. bcrypt do PHP 8.2 tem 60 caracteres; Argon2id cabe em 255.
    CONSTRAINT users_password_hash_check CHECK (CHAR_LENGTH(password_hash) >= 60),
    CONSTRAINT users_display_name_check CHECK (CHAR_LENGTH(TRIM(display_name)) > 0),
    CONSTRAINT users_username_check CHECK (username IS NULL OR CHAR_LENGTH(TRIM(username)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY roles_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    permission_key VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY permissions_key_unique (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT role_permissions_role_fk FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT role_permissions_permission_fk FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT user_roles_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT user_roles_role_fk FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT user_roles_assigned_by_fk FOREIGN KEY (assigned_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trilha imutável pela aplicação. O privilégio do usuário de banco ainda permite DELETE;
-- o código não deve oferecer exclusão nem alteração destes registros.
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    before_data LONGTEXT NULL,
    after_data LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY audit_logs_user_idx (user_id, created_at),
    KEY audit_logs_entity_idx (entity_type, entity_id),
    KEY audit_logs_created_idx (created_at),
    CONSTRAINT audit_logs_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT audit_logs_before_json_check CHECK (before_data IS NULL OR JSON_VALID(before_data)),
    CONSTRAINT audit_logs_after_json_check CHECK (after_data IS NULL OR JSON_VALID(after_data))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parâmetros comerciais ficam vazios até a operação definir tolerância, multa e bloqueio.
CREATE TABLE settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    description VARCHAR(255) NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY settings_key_unique (setting_key),
    CONSTRAINT settings_updated_by_fk FOREIGN KEY (updated_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Códigos humanos (CLI-000001, ALU-2026-000341) não são chave primária.
-- A chave inclui o ano quando o número exibido reinicia por ano, por exemplo reservation-2026.
CREATE TABLE sequence_counters (
    sequence_key VARCHAR(50) NOT NULL,
    last_value INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (sequence_key),
    CONSTRAINT sequence_counters_key_check CHECK (CHAR_LENGTH(TRIM(sequence_key)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (name, label, description) VALUES
    ('administrador', 'Administrador', 'Acesso total, configurações, usuários, parâmetros, ajustes excepcionais e auditoria.'),
    ('atendimento', 'Atendimento/Balcão', 'Clientes, disponibilidade, orçamento, reserva, abertura de aluguel e recebimento conforme limite.'),
    ('armazem', 'Armazém', 'Separação, retirada, devolução, localização, transferências, inventário e inspeções.'),
    ('financeiro', 'Financeiro', 'Cobranças, pagamentos, cauções, estornos, inadimplência e relatórios financeiros.'),
    ('manutencao', 'Manutenção', 'Ordens de manutenção, diagnóstico, peças, custos e liberação técnica.'),
    ('consulta', 'Consulta/Gerência', 'Leitura de dashboards e relatórios, sem operações destrutivas.');

INSERT INTO permissions (permission_key, description) VALUES
    ('customer.view', 'Consultar clientes'),
    ('customer.create', 'Cadastrar clientes'),
    ('customer.edit', 'Editar clientes'),
    ('product.view', 'Consultar produtos'),
    ('product.manage', 'Gerenciar o catálogo de produtos'),
    ('asset.view', 'Consultar unidades patrimoniais'),
    ('asset.create', 'Cadastrar unidades patrimoniais'),
    ('asset.transfer', 'Transferir unidades entre locais'),
    ('asset.retire', 'Dar baixa em unidade patrimonial'),
    ('reservation.create', 'Criar reservas'),
    ('reservation.cancel', 'Cancelar reservas'),
    ('rental.create', 'Abrir contrato de aluguel'),
    ('rental.checkout', 'Confirmar retirada'),
    ('rental.extend', 'Prorrogar contrato'),
    ('rental.return', 'Registrar devolução'),
    ('rental.cancel', 'Cancelar contrato'),
    ('pricing.discount', 'Aplicar desconto'),
    ('pricing.override', 'Substituir preço fora da tarifa'),
    ('payment.receive', 'Receber pagamento'),
    ('payment.refund', 'Estornar pagamento'),
    ('deposit.release', 'Usar ou liberar caução'),
    ('maintenance.create', 'Abrir ordem de manutenção'),
    ('maintenance.close', 'Concluir manutenção e liberar tecnicamente'),
    ('report.view', 'Ver relatórios'),
    ('report.export', 'Exportar relatórios'),
    ('user.manage', 'Gerenciar usuários e papéis'),
    ('settings.manage', 'Gerenciar parâmetros'),
    ('audit.view', 'Consultar auditoria');

-- O administrador recebe todas as chaves da seção 3.1.
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
CROSS JOIN permissions
WHERE roles.name = 'administrador';

-- Atendimento não recebe checkout, estorno, baixa nem override de preço.
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.permission_key IN (
    'customer.view', 'customer.create', 'customer.edit',
    'product.view', 'asset.view',
    'reservation.create', 'reservation.cancel',
    'rental.create', 'payment.receive', 'pricing.discount'
)
WHERE roles.name = 'atendimento';

-- Inspeção e inventário não têm chave própria na seção 3.1; continuam ligados ao papel armazem.
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.permission_key IN (
    'product.view', 'asset.view', 'asset.transfer',
    'rental.checkout', 'rental.return'
)
WHERE roles.name = 'armazem';

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.permission_key IN (
    'customer.view', 'payment.receive', 'payment.refund',
    'deposit.release', 'report.view', 'report.export'
)
WHERE roles.name = 'financeiro';

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.permission_key IN (
    'asset.view', 'maintenance.create', 'maintenance.close'
)
WHERE roles.name = 'manutencao';

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.permission_key IN (
    'customer.view', 'product.view', 'asset.view',
    'report.view', 'report.export'
)
WHERE roles.name = 'consulta';
