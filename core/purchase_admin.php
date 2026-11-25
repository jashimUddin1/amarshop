<?php

function ensure_purchase_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS suppliers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(180) NOT NULL,
            phone VARCHAR(40) NOT NULL DEFAULT '',
            email VARCHAR(191) NULL,
            address TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_suppliers_status_name (status, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchases (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            purchase_code VARCHAR(40) NOT NULL,
            supplier_id BIGINT UNSIGNED NOT NULL,
            supplier_invoice VARCHAR(100) NULL,
            purchase_date DATE NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            extra_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_by_admin_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_purchases_code (purchase_code),
            KEY idx_purchases_supplier_date (supplier_id, purchase_date),
            KEY idx_purchases_date (purchase_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            purchase_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_code VARCHAR(50) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            size VARCHAR(100) NOT NULL DEFAULT '',
            quantity INT UNSIGNED NOT NULL,
            returned_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            unit_cost DECIMAL(12,2) NOT NULL,
            line_total DECIMAL(12,2) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_purchase_items_purchase (purchase_id, id),
            KEY idx_purchase_items_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            purchase_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(30) NOT NULL,
            reference_no VARCHAR(100) NULL,
            note VARCHAR(500) NULL,
            received_by_admin_id BIGINT UNSIGNED NOT NULL,
            paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_purchase_payments_purchase (purchase_id, paid_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_returns (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            return_code VARCHAR(40) NOT NULL,
            purchase_id BIGINT UNSIGNED NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            reason VARCHAR(500) NULL,
            created_by_admin_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_purchase_returns_code (return_code),
            KEY idx_purchase_returns_purchase (purchase_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_return_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            return_id BIGINT UNSIGNED NOT NULL,
            purchase_item_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            unit_cost DECIMAL(12,2) NOT NULL,
            line_total DECIMAL(12,2) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_purchase_return_items_return (return_id, id),
            KEY idx_purchase_return_items_purchase_item (purchase_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_movements (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            movement_type VARCHAR(24) NOT NULL,
            quantity INT NOT NULL,
            stock_before INT NOT NULL,
            stock_after INT NOT NULL,
            reference_type VARCHAR(24) NOT NULL,
            reference_id BIGINT UNSIGNED NOT NULL,
            note VARCHAR(500) NULL,
            admin_user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inventory_movements_product (product_id, created_at),
            KEY idx_inventory_movements_reference (reference_type, reference_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
