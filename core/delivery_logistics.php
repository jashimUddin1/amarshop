<?php

require_once __DIR__ . '/order_status.php';
require_once __DIR__ . '/order_admin.php';

function delivery_logistics_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $pdo->quote($column));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' .
            str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function ensure_delivery_logistics_tables(PDO $pdo): void
{
    ensure_order_status_history_table($pdo);
    ensure_order_delivery_assignments_table($pdo);
    ensure_order_issues_table($pdo);
    delivery_logistics_ensure_column($pdo, 'order_delivery_assignments', 'deliveryman_id', 'BIGINT UNSIGNED NULL AFTER order_id');
    delivery_logistics_ensure_column($pdo, 'deliveryman', 'login_password', 'VARCHAR(255) NULL AFTER email');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_bags (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bag_code VARCHAR(40) NOT NULL,
            bag_name VARCHAR(120) NOT NULL,
            zone VARCHAR(180) NOT NULL DEFAULT '',
            order_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            deliveryman_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            return_submitted TINYINT(1) NOT NULL DEFAULT 0,
            created_by_admin_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_delivery_bags_code (bag_code),
            KEY idx_delivery_bags_deliveryman (deliveryman_id, status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    foreach ([
        'zone' => "VARCHAR(180) NOT NULL DEFAULT '' AFTER bag_name",
        'order_quantity' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER zone',
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'open' AFTER notes",
        'return_submitted' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
        'closed_at' => 'DATETIME NULL AFTER updated_at',
    ] as $column => $definition) {
        delivery_logistics_ensure_column($pdo, 'delivery_bags', $column, $definition);
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_quantity (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bag_id BIGINT UNSIGNED NOT NULL,
            shipment_type VARCHAR(12) NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            issue_id BIGINT UNSIGNED NULL,
            shipment_number VARCHAR(32) NOT NULL,
            customer_name VARCHAR(180) NOT NULL DEFAULT '',
            customer_phone VARCHAR(40) NOT NULL DEFAULT '',
            address TEXT NOT NULL,
            expected_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'in_bag',
            cash_received DECIMAL(12,2) NULL,
            cash_receipt_no VARCHAR(40) NULL,
            delivered_at DATETIME NULL,
            returned_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_quantity_bag (bag_id, status),
            KEY idx_order_quantity_order (order_id, status),
            KEY idx_order_quantity_issue (issue_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_bag_cash_collections (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bag_id BIGINT UNSIGNED NOT NULL,
            deliveryman_id BIGINT UNSIGNED NOT NULL,
            collector_id BIGINT UNSIGNED NULL,
            collector_name VARCHAR(180) NULL,
            submitted_amount DECIMAL(12,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            rider_note TEXT NULL,
            manager_note TEXT NULL,
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_by_admin_id BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_bag_cash_status (status, submitted_at),
            KEY idx_bag_cash_deliveryman (deliveryman_id, bag_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cash_collectors (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(180) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    delivery_logistics_ensure_column($pdo, 'delivery_bag_cash_collections', 'collector_id', 'BIGINT UNSIGNED NULL AFTER deliveryman_id');
    delivery_logistics_ensure_column($pdo, 'delivery_bag_cash_collections', 'collector_name', 'VARCHAR(180) NULL AFTER collector_id');
}

function delivery_receipt_number(int $manifest_item_id): string
{
    return 'RCPT-' . date('ymd') . '-' . str_pad((string)$manifest_item_id, 6, '0', STR_PAD_LEFT);
}
