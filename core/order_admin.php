<?php

function ensure_order_delivery_assignments_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_delivery_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            delivery_type VARCHAR(20) NOT NULL,
            provider_name VARCHAR(120) NOT NULL,
            contact_name VARCHAR(120) NOT NULL DEFAULT '',
            contact_phone VARCHAR(40) NOT NULL DEFAULT '',
            tracking_number VARCHAR(120) NOT NULL DEFAULT '',
            notes TEXT NULL,
            assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_order_delivery_assignment (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function ensure_order_requests_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            request_type VARCHAR(24) NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            size VARCHAR(100) NOT NULL DEFAULT '',
            quantity INT UNSIGNED NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_order_requests_status_created (status, created_at),
            KEY idx_order_requests_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function ensure_order_number_sequence_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_number_sequences (
            id TINYINT UNSIGNED NOT NULL,
            next_number BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB
    ");
    $pdo->exec("
        INSERT IGNORE INTO order_number_sequences (id, next_number)
        SELECT 1, COALESCE(MAX(CAST(order_number AS UNSIGNED)), 0) + 1
        FROM orders
        WHERE order_number REGEXP '^[0-9]+$'
    ");
}
