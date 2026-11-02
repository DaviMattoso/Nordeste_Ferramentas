-- Clientes, orçamentos, reservas e contratos.
-- O preço do contrato fica copiado no item. Mudar a tarifa do produto não reescreve o passado.
-- RESERVED da unidade não é um status do contrato: a reserva tem tabela própria.
-- O contrato usa CONFIRMED. O apêndice mostra RESERVED/CONFIRMED como alternativas dessa etapa.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_code VARCHAR(30) NOT NULL,
    customer_type ENUM('PERSON', 'COMPANY') NOT NULL,
    legal_name VARCHAR(200) NOT NULL,
    trade_name VARCHAR(200) NULL,
    tax_id VARCHAR(32) NULL,
    email VARCHAR(254) NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    status ENUM('ACTIVE', 'BLOCKED', 'DELINQUENT', 'INCOMPLETE', 'INACTIVE') NOT NULL DEFAULT 'INCOMPLETE',
    credit_limit DECIMAL(12,2) NULL,
    default_deposit DECIMAL(12,2) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY customers_code_unique (customer_code),
    UNIQUE KEY customers_tax_id_unique (tax_id),
    KEY customers_status_idx (status),
    CONSTRAINT customers_legal_name_check CHECK (CHAR_LENGTH(TRIM(legal_name)) > 0),
    CONSTRAINT customers_credit_limit_check CHECK (credit_limit IS NULL OR credit_limit >= 0),
    CONSTRAINT customers_default_deposit_check CHECK (default_deposit IS NULL OR default_deposit >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    address_type ENUM('billing', 'delivery', 'worksite') NOT NULL,
    street VARCHAR(200) NOT NULL,
    street_number VARCHAR(20) NULL,
    complement VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(20) NOT NULL,
    postal_code VARCHAR(12) NOT NULL,
    country VARCHAR(2) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY customer_addresses_customer_idx (customer_id, address_type),
    CONSTRAINT customer_addresses_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY customer_documents_customer_idx (customer_id),
    CONSTRAINT customer_documents_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT customer_documents_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT customer_documents_path_check CHECK (
        LOCATE('..', file_path) = 0 AND LOCATE(':', file_path) = 0 AND LEFT(file_path, 1) <> '/'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_number VARCHAR(30) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status ENUM('PENDING', 'CONFIRMED', 'EXPIRED', 'CANCELLED', 'CONVERTED') NOT NULL DEFAULT 'PENDING',
    expires_at DATETIME NULL,
    estimated_total DECIMAL(12,2) NULL,
    deposit_estimate DECIMAL(12,2) NULL,
    notes TEXT NULL,
    origin VARCHAR(50) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY reservations_number_unique (reservation_number),
    KEY reservations_customer_idx (customer_id),
    KEY reservations_period_idx (status, start_at, end_at),
    KEY reservations_warehouse_idx (warehouse_id),
    CONSTRAINT reservations_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservations_warehouse_fk FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservations_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservations_period_check CHECK (end_at > start_at),
    CONSTRAINT reservations_estimated_total_check CHECK (estimated_total IS NULL OR estimated_total >= 0),
    CONSTRAINT reservations_deposit_check CHECK (deposit_estimate IS NULL OR deposit_estimate >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservation_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_price_estimate DECIMAL(12,2) NULL,
    deposit_estimate DECIMAL(12,2) NULL,
    PRIMARY KEY (id),
    KEY reservation_items_reservation_idx (reservation_id),
    KEY reservation_items_product_idx (product_id),
    KEY reservation_items_asset_idx (asset_id),
    CONSTRAINT reservation_items_reservation_fk FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservation_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservation_items_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reservation_items_quantity_check CHECK (quantity > 0),
    CONSTRAINT reservation_items_price_check CHECK (unit_price_estimate IS NULL OR unit_price_estimate >= 0),
    CONSTRAINT reservation_items_deposit_check CHECK (deposit_estimate IS NULL OR deposit_estimate >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- quote_number é único. version_number guarda a versão atual; o histórico de versões anteriores
-- ainda não tem tabela. Ele precisa existir antes de a tela de orçamento ser considerada pronta.
CREATE TABLE quotes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_number VARCHAR(30) NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    customer_id BIGINT UNSIGNED NULL,
    prospect_name VARCHAR(200) NULL,
    status ENUM('DRAFT', 'APPROVED', 'REJECTED', 'EXPIRED', 'CONVERTED') NOT NULL DEFAULT 'DRAFT',
    valid_until DATETIME NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    fees_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    terms TEXT NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY quotes_number_unique (quote_number),
    KEY quotes_customer_idx (customer_id),
    KEY quotes_status_idx (status, valid_until),
    CONSTRAINT quotes_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT quotes_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT quotes_party_check CHECK (
        customer_id IS NOT NULL
        OR (prospect_name IS NOT NULL AND CHAR_LENGTH(TRIM(prospect_name)) > 0)
    ),
    CONSTRAINT quotes_version_check CHECK (version_number >= 1),
    CONSTRAINT quotes_subtotal_check CHECK (subtotal >= 0),
    CONSTRAINT quotes_discount_check CHECK (discount_total >= 0),
    CONSTRAINT quotes_fees_check CHECK (fees_total >= 0),
    CONSTRAINT quotes_total_check CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quote_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    description_snapshot VARCHAR(255) NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    period_start DATETIME NULL,
    period_end DATETIME NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY quote_items_quote_idx (quote_id),
    KEY quote_items_product_idx (product_id),
    CONSTRAINT quote_items_quote_fk FOREIGN KEY (quote_id) REFERENCES quotes (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT quote_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT quote_items_quantity_check CHECK (quantity > 0),
    CONSTRAINT quote_items_price_check CHECK (unit_price >= 0),
    CONSTRAINT quote_items_discount_check CHECK (discount_amount >= 0),
    CONSTRAINT quote_items_total_check CHECK (line_total >= 0),
    CONSTRAINT quote_items_period_check CHECK (
        period_end IS NULL OR period_start IS NULL OR period_end > period_start
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rentals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_number VARCHAR(30) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    warehouse_out_id BIGINT UNSIGNED NOT NULL,
    warehouse_return_id BIGINT UNSIGNED NOT NULL,
    reservation_id BIGINT UNSIGNED NULL,
    quote_id BIGINT UNSIGNED NULL,
    planned_start_at DATETIME NOT NULL,
    planned_end_at DATETIME NOT NULL,
    actual_checkout_at DATETIME NULL,
    actual_return_at DATETIME NULL,
    status ENUM(
        'DRAFT', 'CONFIRMED', 'READY_FOR_PICKUP', 'ACTIVE',
        'OVERDUE', 'RETURNING', 'CLOSED', 'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    fees_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    damage_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    deposit_required DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance_due DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY rentals_number_unique (rental_number),
    KEY rentals_customer_idx (customer_id),
    KEY rentals_period_idx (status, planned_start_at, planned_end_at),
    KEY rentals_warehouse_out_idx (warehouse_out_id),
    KEY rentals_warehouse_return_idx (warehouse_return_id),
    KEY rentals_reservation_idx (reservation_id),
    KEY rentals_quote_idx (quote_id),
    CONSTRAINT rentals_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_warehouse_out_fk FOREIGN KEY (warehouse_out_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_warehouse_return_fk FOREIGN KEY (warehouse_return_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_reservation_fk FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_quote_fk FOREIGN KEY (quote_id) REFERENCES quotes (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rentals_planned_period_check CHECK (planned_end_at > planned_start_at),
    CONSTRAINT rentals_return_after_checkout_check CHECK (
        actual_return_at IS NULL
        OR (actual_checkout_at IS NOT NULL AND actual_return_at >= actual_checkout_at)
    ),
    CONSTRAINT rentals_subtotal_check CHECK (subtotal >= 0),
    CONSTRAINT rentals_discount_check CHECK (discount_total >= 0),
    CONSTRAINT rentals_fees_check CHECK (fees_total >= 0),
    CONSTRAINT rentals_damage_check CHECK (damage_total >= 0),
    CONSTRAINT rentals_total_check CHECK (total_amount >= 0),
    CONSTRAINT rentals_deposit_check CHECK (deposit_required >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rental_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NULL,
    description_snapshot VARCHAR(255) NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    rate_type ENUM('HOURLY', 'DAILY', 'WEEKEND', 'WEEKLY', 'BIWEEKLY', 'MONTHLY', 'CUSTOM') NOT NULL,
    rate_amount DECIMAL(12,2) NOT NULL,
    planned_start_at DATETIME NOT NULL,
    planned_end_at DATETIME NOT NULL,
    actual_return_at DATETIME NULL,
    line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    late_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    damage_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('PENDING', 'OUT', 'RETURNED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY (id),
    KEY rental_items_rental_idx (rental_id),
    KEY rental_items_product_idx (product_id),
    KEY rental_items_asset_period_idx (asset_id, planned_start_at, planned_end_at),
    CONSTRAINT rental_items_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rental_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rental_items_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rental_items_quantity_check CHECK (quantity > 0),
    CONSTRAINT rental_items_rate_check CHECK (rate_amount >= 0),
    CONSTRAINT rental_items_period_check CHECK (planned_end_at > planned_start_at),
    CONSTRAINT rental_items_subtotal_check CHECK (line_subtotal >= 0),
    CONSTRAINT rental_items_late_fee_check CHECK (late_fee >= 0),
    CONSTRAINT rental_items_damage_check CHECK (damage_charge >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rental_extensions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_id BIGINT UNSIGNED NOT NULL,
    old_end_at DATETIME NOT NULL,
    new_end_at DATETIME NOT NULL,
    additional_amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY rental_extensions_rental_idx (rental_id),
    CONSTRAINT rental_extensions_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rental_extensions_approved_by_fk FOREIGN KEY (approved_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT rental_extensions_period_check CHECK (new_end_at > old_end_at),
    CONSTRAINT rental_extensions_amount_check CHECK (additional_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
