-- Inspeção, manutenção e financeiro.
-- Valores monetários são DECIMAL. A fórmula de multa, juros e imposto não está definida
-- e não foi transformada em coluna calculada.
-- Pagamento confirmado não é apagado. Estorno é outro registro com status REVERSED.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE inspections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_id BIGINT UNSIGNED NOT NULL,
    rental_id BIGINT UNSIGNED NULL,
    inspection_type ENUM('CHECKOUT', 'RETURN', 'PERIODIC') NOT NULL,
    meter_value DECIMAL(12,2) NULL,
    condition_grade VARCHAR(30) NULL,
    result ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    notes TEXT NULL,
    performed_by BIGINT UNSIGNED NULL,
    performed_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY inspections_asset_idx (asset_id, performed_at),
    KEY inspections_rental_idx (rental_id),
    CONSTRAINT inspections_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inspections_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inspections_performed_by_fk FOREIGN KEY (performed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inspections_meter_check CHECK (meter_value IS NULL OR meter_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inspection_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL,
    check_key VARCHAR(50) NOT NULL,
    label_snapshot VARCHAR(150) NOT NULL,
    result ENUM('OK', 'WARNING', 'FAIL', 'NA') NOT NULL,
    notes TEXT NULL,
    PRIMARY KEY (id),
    KEY inspection_items_inspection_idx (inspection_id),
    CONSTRAINT inspection_items_inspection_fk FOREIGN KEY (inspection_id) REFERENCES inspections (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inspection_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY inspection_photos_inspection_idx (inspection_id),
    CONSTRAINT inspection_photos_inspection_fk FOREIGN KEY (inspection_id) REFERENCES inspections (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inspection_photos_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT inspection_photos_path_check CHECK (
        LOCATE('..', file_path) = 0 AND LOCATE(':', file_path) = 0 AND LEFT(file_path, 1) <> '/'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Severidade e status de cobrança continuam texto porque a especificação não fixa a escala.
CREATE TABLE damage_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_id BIGINT UNSIGNED NOT NULL,
    rental_id BIGINT UNSIGNED NULL,
    inspection_id BIGINT UNSIGNED NULL,
    severity VARCHAR(30) NOT NULL,
    description TEXT NOT NULL,
    responsibility ENUM('CUSTOMER', 'NORMAL_WEAR', 'TRANSPORT', 'INTERNAL', 'UNDEFINED') NOT NULL,
    estimated_cost DECIMAL(12,2) NULL,
    charged_amount DECIMAL(12,2) NULL,
    status VARCHAR(30) NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY damage_reports_asset_idx (asset_id),
    KEY damage_reports_rental_idx (rental_id),
    KEY damage_reports_inspection_idx (inspection_id),
    CONSTRAINT damage_reports_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT damage_reports_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT damage_reports_inspection_fk FOREIGN KEY (inspection_id) REFERENCES inspections (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT damage_reports_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT damage_reports_estimated_check CHECK (estimated_cost IS NULL OR estimated_cost >= 0),
    CONSTRAINT damage_reports_charged_check CHECK (charged_amount IS NULL OR charged_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maintenance_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    maintenance_number VARCHAR(30) NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    maintenance_type ENUM(
        'PREVENTIVE_CALENDAR', 'PREVENTIVE_METER', 'CORRECTIVE',
        'PERIODIC_INSPECTION', 'CALIBRATION', 'TECHNICAL_CLEANING'
    ) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    status ENUM(
        'OPEN', 'DIAGNOSING', 'WAITING_PART', 'IN_SERVICE',
        'TESTING', 'COMPLETED', 'CANCELLED'
    ) NOT NULL DEFAULT 'OPEN',
    opened_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    diagnosis TEXT NULL,
    service_description TEXT NULL,
    labor_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    parts_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    vendor_id BIGINT UNSIGNED NULL,
    meter_in DECIMAL(12,2) NULL,
    meter_out DECIMAL(12,2) NULL,
    next_due_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY maintenance_orders_number_unique (maintenance_number),
    KEY maintenance_orders_asset_idx (asset_id, status),
    KEY maintenance_orders_status_idx (status, opened_at),
    KEY maintenance_orders_vendor_idx (vendor_id),
    CONSTRAINT maintenance_orders_asset_fk FOREIGN KEY (asset_id) REFERENCES assets (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT maintenance_orders_vendor_fk FOREIGN KEY (vendor_id) REFERENCES suppliers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT maintenance_orders_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT maintenance_orders_labor_check CHECK (labor_cost >= 0),
    CONSTRAINT maintenance_orders_parts_check CHECK (parts_cost >= 0),
    CONSTRAINT maintenance_orders_meter_in_check CHECK (meter_in IS NULL OR meter_in >= 0),
    CONSTRAINT maintenance_orders_meter_out_check CHECK (meter_out IS NULL OR meter_out >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maintenance_parts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    maintenance_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    part_name VARCHAR(150) NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY maintenance_parts_maintenance_idx (maintenance_id),
    KEY maintenance_parts_product_idx (product_id),
    CONSTRAINT maintenance_parts_maintenance_fk FOREIGN KEY (maintenance_id) REFERENCES maintenance_orders (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT maintenance_parts_product_fk FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT maintenance_parts_quantity_check CHECK (quantity > 0),
    CONSTRAINT maintenance_parts_unit_cost_check CHECK (unit_cost >= 0),
    CONSTRAINT maintenance_parts_total_cost_check CHECK (total_cost >= 0),
    CONSTRAINT maintenance_parts_target_check CHECK (product_id IS NOT NULL OR part_name IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receivables (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_id BIGINT UNSIGNED NOT NULL,
    installment_number INT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('OPEN', 'PARTIAL', 'PAID', 'CANCELLED') NOT NULL DEFAULT 'OPEN',
    late_fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    interest_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY receivables_installment_unique (rental_id, installment_number),
    KEY receivables_due_idx (status, due_date),
    CONSTRAINT receivables_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT receivables_installment_check CHECK (installment_number >= 1),
    CONSTRAINT receivables_amount_check CHECK (amount > 0),
    CONSTRAINT receivables_paid_check CHECK (paid_amount >= 0),
    CONSTRAINT receivables_late_fee_check CHECK (late_fee_amount >= 0),
    CONSTRAINT receivables_interest_check CHECK (interest_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    receipt_number VARCHAR(30) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    rental_id BIGINT UNSIGNED NULL,
    payment_method VARCHAR(30) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_at DATETIME NOT NULL,
    external_reference VARCHAR(100) NULL,
    status ENUM('RECEIVED', 'REVERSED') NOT NULL,
    reverses_payment_id BIGINT UNSIGNED NULL,
    received_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY payments_receipt_unique (receipt_number),
    KEY payments_customer_idx (customer_id),
    KEY payments_rental_idx (rental_id),
    KEY payments_reversal_idx (reverses_payment_id),
    CONSTRAINT payments_customer_fk FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payments_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payments_reversal_fk FOREIGN KEY (reverses_payment_id) REFERENCES payments (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payments_received_by_fk FOREIGN KEY (received_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payments_amount_check CHECK (amount > 0),
    CONSTRAINT payments_reversal_shape_check CHECK (
        (status = 'RECEIVED' AND reverses_payment_id IS NULL)
        OR (status = 'REVERSED' AND reverses_payment_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,
    receivable_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY payment_allocations_payment_idx (payment_id),
    KEY payment_allocations_receivable_idx (receivable_id),
    CONSTRAINT payment_allocations_payment_fk FOREIGN KEY (payment_id) REFERENCES payments (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payment_allocations_receivable_fk FOREIGN KEY (receivable_id) REFERENCES receivables (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT payment_allocations_amount_check CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE deposits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_id BIGINT UNSIGNED NOT NULL,
    amount_received DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_used DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_released DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('PENDING', 'RECEIVED', 'PARTIALLY_USED', 'RELEASED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    received_at DATETIME NULL,
    released_at DATETIME NULL,
    payment_id BIGINT UNSIGNED NULL,
    authorized_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY deposits_rental_idx (rental_id),
    KEY deposits_payment_idx (payment_id),
    CONSTRAINT deposits_rental_fk FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT deposits_payment_fk FOREIGN KEY (payment_id) REFERENCES payments (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT deposits_authorized_by_fk FOREIGN KEY (authorized_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT deposits_received_check CHECK (amount_received >= 0),
    CONSTRAINT deposits_used_check CHECK (amount_used >= 0),
    CONSTRAINT deposits_released_check CHECK (amount_released >= 0),
    CONSTRAINT deposits_balance_check CHECK (amount_used + amount_released <= amount_received)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- entity_id é polimórfico de propósito. A integridade do alvo é da aplicação,
-- porque uma nota pode apontar para cliente, unidade ou contrato.
CREATE TABLE notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY notes_entity_idx (entity_type, entity_id),
    CONSTRAINT notes_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT notes_body_check CHECK (CHAR_LENGTH(TRIM(body)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
