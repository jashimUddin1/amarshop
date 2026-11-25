<?php

function ensure_order_status_history_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_status_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(32) NOT NULL,
            admin_id BIGINT UNSIGNED NULL,
            admin_name VARCHAR(191) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_status_history_order (order_id, created_at, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    foreach ([
        'admin_id' => 'BIGINT UNSIGNED NULL AFTER status',
        'admin_name' => 'VARCHAR(191) NULL AFTER admin_id',
    ] as $column => $definition) {
        $column_stmt = $pdo->query('SHOW COLUMNS FROM order_status_history LIKE ' . $pdo->quote($column));
        if (!$column_stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec('ALTER TABLE order_status_history ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}

function normalize_order_status(string $status): string
{
    return match (strtolower(trim($status))) {
        'pending' => 'processing',
        'shipped' => 'delivering',
        'completed' => 'delivered',
        default => strtolower(trim($status)),
    };
}

function order_shipment_number(string $order_number, string $suffix = 'A'): string
{
    $base = preg_replace('/[A-Z]+$/i', '', trim($order_number));
    if ($base === '' || !ctype_digit($base)) {
        return trim($order_number) . strtoupper($suffix);
    }
    return str_pad($base, 3, '0', STR_PAD_LEFT) . strtoupper($suffix);
}

function order_status_label(string $status): string
{
    return match (normalize_order_status($status)) {
        'placed' => 'Order Placed',
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'picked' => 'Picked',
        'packed' => 'Packed',
        'packed_done' => 'Pack Done / Ready to Delivery',
        'inbag' => 'In Bag',
        'delivering' => 'Delivering',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => ucfirst(normalize_order_status($status)),
    };
}

function order_shipping_area_label(string $shipping_area): string
{
    $shipping_area = trim($shipping_area);
    $normalized_area = strtolower($shipping_area);

    if ($normalized_area === 'inside_dhaka') {
        return 'Inside Dhaka';
    }
    if ($normalized_area === 'outside_dhaka') {
        return 'Outside Dhaka';
    }
    if (is_numeric($shipping_area)) {
        return match ((float)$shipping_area) {
            60.0 => 'Inside Dhaka',
            120.0 => 'Outside Dhaka',
            default => '',
        };
    }

    return $shipping_area;
}

function get_order_status_timeline(PDO $pdo, array $order): array
{
    $stmt = $pdo->prepare(
        'SELECT status, created_at, admin_name FROM order_status_history WHERE order_id = :order_id ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute([':order_id' => (int)$order['id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($events)) {
        $events[] = [
            'status' => 'placed',
            'created_at' => $order['created_at'] ?? null,
            'admin_name' => null,
        ];
    }

    $current_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
    $last_status = normalize_order_status((string)end($events)['status']);
    if ($last_status !== $current_status) {
        $events[] = [
            'status' => $current_status,
            'created_at' => null,
            'admin_name' => null,
        ];
    }

    return $events;
}
