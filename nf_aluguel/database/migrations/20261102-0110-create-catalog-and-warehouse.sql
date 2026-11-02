-- Catálogo, patrimônio e armazém do NF Aluguel.
-- Produto é o modelo comercial. Asset é a ferramenta física.
-- Estoque de unidade patrimonial não é uma coluna de quantidade no produto.
-- A sobreposição de reservas não cabe em UNIQUE: o MariaDB 10.4 não tem restrição de exclusão.
-- Essa garantia fica na transação da aplicação, com bloqueio da unidade.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE suppliers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    tax_id VARCHAR(32) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(254) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY suppliers_tax_id_unique (tax_id),
    CONSTRAINT suppliers_name_check CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    street VARCHAR(200) NULL,
    street_number VARCHAR(20) NULL,
    district VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(20) NULL,
    postal_code VARCHAR(12) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY warehouses_code_unique (code),
    CONSTRAINT warehouses_name_check CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE storage_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    zone VARCHAR(80) NULL,
    aisle VARCHAR(40) NULL,
    rack VARCHAR(40) NULL,
    level_code VARCHAR(40) NULL,
    position_code VARCHAR(40) NULL,
    location_code VARCHAR(50) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY storage_locations_code_unique (location_code),
    KEY storage_locations_warehouse_idx (warehouse_id),
    CONSTRAINT storage_locations_warehouse_fk FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY categories_slug_unique (slug),
    KEY categories_parent_idx (parent_id),
    CONSTRAINT categories_parent_fk FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT categories_name_check CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY brands_name_unique (name),
    CONSTRAINT brands_name_check CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    brand_id BIGINT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    model VARCHAR(150) NULL,
    short_description VARCHAR(500) NULL,
    description TEXT NULL,
    specifications TEXT NULL,
    power_label VARCHAR(50) NULL,
    voltage_label VARCHAR(50) NULL,
    capacity_label VARCHAR(50) NULL,
    weight_label VARCHAR(50) NULL,
    usage_instructions TEXT NULL,
    included_items TEXT NULL,
    recommended_ppe TEXT NULL,
    manual_path VARCHAR(255) NULL,
    control_type ENUM('ASSET', 'QUANTITY', 'KIT') NOT NULL,
    replacement_value DECIMAL(12,2) NULL,
    default_deposit DECIMAL(12,2) NULL,
    commercial_status ENUM('ACTIVE', 'TEMPORARILY_UNAVAILABLE', 'DISCONTINUED') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY products_sku_unique (sku),
    KEY products_category_idx (category_id),
    KEY products_brand_idx (brand_id),
    KEY products_status_idx (commercial_status),
    CONSTRAINT products_category_fk FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT products_brand_fk FOREIGN KEY (brand_id) REFERENCES brands (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT products_name_check CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT products_replacement_check CHECK (replacement_value IS NULL OR replacement_value >= 0),
    CONSTRAINT products_deposit_check CHECK (default_deposit IS NULL OR default_deposit >= 0),
    CONSTRAINT products_manual_path_check CHECK (
        manual_path IS NULL
        OR (LOCATE('..', manual_path) = 0 AND LOCATE(':', manual_path) = 0 AND LEFT(manual_path, 1) <> '/')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WEEKEND e BIWEEKLY vêm da política de preço da seção 5.2.
-- A fórmula que transforma hora, diária e mês em valor final ainda não está definida.
CREATE TABLE product_prices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    price_type ENUM('HOURLY', 'DAILY', 'WEEKEND', 'WEEKLY', 'BIWEEKLY', 'MONTHLY', 'CUSTOM') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    minimum_period INT UNSIGNED NULL,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_prices_product_idx (product_id, price_type, is_active),
    CONSTRAINT product_prices_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT product_prices_amount_check CHECK (amount >= 0),
    CONSTRAINT product_prices_period_check CHECK (valid_to IS NULL OR valid_to > valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_accessories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    accessory_product_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(150) NULL,
    quantity DECIMAL(12,3) NOT NULL,
    required_on_checkout TINYINT(1) NOT NULL DEFAULT 0,
    charge_if_missing DECIMAL(12,2) NULL,
    PRIMARY KEY (id),
    KEY product_accessories_product_idx (product_id),
    KEY product_accessories_accessory_idx (accessory_product_id),
    CONSTRAINT product_accessories_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT product_accessories_accessory_fk FOREIGN KEY (accessory_product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT product_accessories_quantity_check CHECK (quantity > 0),
    CONSTRAINT product_accessories_charge_check CHECK (charge_if_missing IS NULL OR charge_if_missing >= 0),
    CONSTRAINT product_accessories_target_check CHECK (
        accessory_product_id IS NOT NULL OR item_name IS NOT NULL
    ),
    CONSTRAINT product_accessories_self_check CHECK (
        accessory_product_id IS NULL OR accessory_product_id <> product_id
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_photos_product_idx (product_id),
    CONSTRAINT product_photos_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT product_photos_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT product_photos_path_check CHECK (
        LOCATE('..', file_path) = 0 AND LOCATE(':', file_path) = 0 AND LEFT(file_path, 1) <> '/'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sem status padrão de propósito: uma unidade nova não pode nascer disponível por omissão.
CREATE TABLE assets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_code VARCHAR(30) NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    manufacturer_serial VARCHAR(100) NULL,
    barcode VARCHAR(100) NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    operational_status ENUM(
        'AVAILABLE', 'RESERVED', 'PICKING', 'RENTED', 'RETURN_INSPECTION',
        'MAINTENANCE', 'DAMAGED', 'QUARANTINE', 'LOST', 'RETIRED'
    ) NOT NULL,
    condition_grade VARCHAR(30) NULL,
    purchase_date DATE NULL,
    invoice_number VARCHAR(60) NULL,
    purchase_cost DECIMAL(12,2) NULL,
    replacement_value DECIMAL(12,2) NULL,
    warranty_until DATE NULL,
    meter_value DECIMAL(12,2) NULL,
    last_maintenance_at DATETIME NULL,
    next_maintenance_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY assets_code_unique (asset_code),
    UNIQUE KEY assets_serial_unique (manufacturer_serial),
    UNIQUE KEY assets_barcode_unique (barcode),
    KEY assets_availability_idx (product_id, warehouse_id, operational_status),
    KEY assets_warehouse_idx (warehouse_id),
    KEY assets_location_idx (location_id),
    KEY assets_supplier_idx (supplier_id),
    CONSTRAINT assets_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT assets_warehouse_fk FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT assets_location_fk FOREIGN KEY (location_id) REFERENCES storage_locations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT assets_supplier_fk FOREIGN KEY (supplier_id) REFERENCES suppliers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT assets_purchase_cost_check CHECK (purchase_cost IS NULL OR purchase_cost >= 0),
    CONSTRAINT assets_replacement_check CHECK (replacement_value IS NULL OR replacement_value >= 0),
    CONSTRAINT assets_meter_check CHECK (meter_value IS NULL OR meter_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asset_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY asset_photos_asset_idx (asset_id),
    CONSTRAINT asset_photos_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT asset_photos_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT asset_photos_path_check CHECK (
        LOCATE('..', file_path) = 0 AND LOCATE(':', file_path) = 0 AND LEFT(file_path, 1) <> '/'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asset_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(40) NULL,
    to_status VARCHAR(40) NOT NULL,
    reason VARCHAR(255) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    changed_by BIGINT UNSIGNED NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY asset_status_history_asset_idx (asset_id, changed_at),
    CONSTRAINT asset_status_history_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT asset_status_history_user_fk FOREIGN KEY (changed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Livro de movimentação. Correção é um registro REVERSAL, não um DELETE.
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    movement_type VARCHAR(40) NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    asset_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(12,3) NOT NULL,
    from_warehouse_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    to_warehouse_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    reason VARCHAR(255) NULL,
    reverses_movement_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY stock_movements_product_idx (product_id, created_at),
    KEY stock_movements_asset_idx (asset_id, created_at),
    KEY stock_movements_from_warehouse_idx (from_warehouse_id),
    KEY stock_movements_to_warehouse_idx (to_warehouse_id),
    KEY stock_movements_from_location_idx (from_location_id),
    KEY stock_movements_to_location_idx (to_location_id),
    KEY stock_movements_reversal_idx (reverses_movement_id),
    CONSTRAINT stock_movements_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_from_warehouse_fk FOREIGN KEY (from_warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_to_warehouse_fk FOREIGN KEY (to_warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_from_location_fk FOREIGN KEY (from_location_id) REFERENCES storage_locations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_to_location_fk FOREIGN KEY (to_location_id) REFERENCES storage_locations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_reversal_fk FOREIGN KEY (reverses_movement_id) REFERENCES stock_movements (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_user_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT stock_movements_quantity_check CHECK (quantity > 0),
    CONSTRAINT stock_movements_target_check CHECK (product_id IS NOT NULL OR asset_id IS NOT NULL),
    CONSTRAINT stock_movements_type_check CHECK (movement_type IN (
        'PURCHASE_IN', 'TRANSFER', 'PICKING', 'RENTAL_OUT', 'PHYSICAL_RETURN',
        'MAINTENANCE_OUT', 'MAINTENANCE_RETURN', 'INVENTORY_ADJUSTMENT',
        'RETIREMENT', 'CONSUMABLE_IN', 'CONSUMABLE_OUT', 'REVERSAL'
    ))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_counts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    counted_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    PRIMARY KEY (id),
    KEY inventory_counts_warehouse_idx (warehouse_id, status),
    CONSTRAINT inventory_counts_warehouse_fk FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_counts_user_fk FOREIGN KEY (counted_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_counts_closed_check CHECK (
        (status = 'OPEN' AND closed_at IS NULL)
        OR (status = 'CLOSED' AND closed_at IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_count_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    count_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    asset_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    expected_qty DECIMAL(12,3) NOT NULL,
    counted_qty DECIMAL(12,3) NULL,
    variance DECIMAL(12,3) GENERATED ALWAYS AS (
        CASE
            WHEN counted_qty IS NULL THEN NULL
            ELSE counted_qty - expected_qty
        END
    ) STORED,
    adjustment_movement_id BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY inventory_count_items_count_idx (count_id),
    KEY inventory_count_items_product_idx (product_id),
    KEY inventory_count_items_asset_idx (asset_id),
    KEY inventory_count_items_location_idx (location_id),
    KEY inventory_count_items_movement_idx (adjustment_movement_id),
    CONSTRAINT inventory_count_items_count_fk FOREIGN KEY (count_id) REFERENCES inventory_counts (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_count_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_count_items_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_count_items_location_fk FOREIGN KEY (location_id) REFERENCES storage_locations (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_count_items_movement_fk FOREIGN KEY (adjustment_movement_id) REFERENCES stock_movements (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inventory_count_items_expected_check CHECK (expected_qty >= 0),
    CONSTRAINT inventory_count_items_counted_check CHECK (counted_qty IS NULL OR counted_qty >= 0),
    CONSTRAINT inventory_count_items_target_check CHECK (product_id IS NOT NULL OR asset_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
