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

function ensure_order_request_messages_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_request_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT UNSIGNED NOT NULL,
            sender_type VARCHAR(16) NOT NULL,
            sender_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_request_messages_request (request_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function ensure_order_issues_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_issues (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            issue_type VARCHAR(24) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            shipment_number VARCHAR(32) NULL,
            shipment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_order_issues_status_created (status, created_at),
            KEY idx_order_issues_order (order_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $creator_column = $pdo->query("SHOW COLUMNS FROM order_issues LIKE 'created_by_admin_id'");
    if (!$creator_column->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec('ALTER TABLE order_issues ADD COLUMN created_by_admin_id BIGINT UNSIGNED NULL AFTER user_id');
    }
    foreach ([
        'shipment_number' => "ALTER TABLE order_issues ADD COLUMN shipment_number VARCHAR(32) NULL AFTER issue_type",
        'shipment_status' => "ALTER TABLE order_issues ADD COLUMN shipment_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER shipment_number",
    ] as $column => $alter_sql) {
        $column_stmt = $pdo->query('SHOW COLUMNS FROM order_issues LIKE ' . $pdo->quote($column));
        $column_data = $column_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$column_data) {
            $pdo->exec($alter_sql);
        } elseif ($column === 'shipment_number' &&
            preg_match('/^varchar\((\d+)\)$/i', (string)($column_data['Type'] ?? ''), $type_match) &&
            (int)$type_match[1] < 32) {
            $pdo->exec('ALTER TABLE order_issues MODIFY COLUMN shipment_number VARCHAR(32) NULL');
        }
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_issue_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            issue_id BIGINT UNSIGNED NOT NULL,
            direction VARCHAR(8) NOT NULL,
            source_order_item_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NULL,
            product_name VARCHAR(255) NOT NULL,
            product_code VARCHAR(120) NOT NULL DEFAULT '',
            size VARCHAR(100) NOT NULL DEFAULT '',
            quantity INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY idx_order_issue_items_issue (issue_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_issue_delivery_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            issue_id BIGINT UNSIGNED NOT NULL,
            deliveryman_id BIGINT UNSIGNED NULL,
            delivery_type VARCHAR(20) NOT NULL,
            provider_name VARCHAR(120) NOT NULL,
            contact_name VARCHAR(120) NOT NULL DEFAULT '',
            contact_phone VARCHAR(40) NOT NULL DEFAULT '',
            tracking_number VARCHAR(120) NOT NULL DEFAULT '',
            notes TEXT NULL,
            assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_order_issue_delivery_assignment (issue_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $assigned_deliveryman_column = $pdo->query("SHOW COLUMNS FROM order_issue_delivery_assignments LIKE 'deliveryman_id'");
    if (!$assigned_deliveryman_column->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec('ALTER TABLE order_issue_delivery_assignments ADD COLUMN deliveryman_id BIGINT UNSIGNED NULL AFTER issue_id');
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_issue_shipment_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            issue_id BIGINT UNSIGNED NOT NULL,
            shipment_status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_issue_shipment_history (issue_id, created_at, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function issue_shipment_suffix(int $sequence): string
{
    $value = $sequence + 1;
    $suffix = '';
    while ($value > 0) {
        $value--;
        $suffix = chr(65 + ($value % 26)) . $suffix;
        $value = intdiv($value, 26);
    }
    return $suffix;
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
