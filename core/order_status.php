<?php

function ensure_order_status_history_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_status_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_status_history_order (order_id, created_at, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
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

function order_status_label(string $status): string
{
    return match (normalize_order_status($status)) {
        'placed' => 'Order Placed',
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'picked' => 'Picked',
        'packed' => 'Packed',
        'delivering' => 'Delivering',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => ucfirst(normalize_order_status($status)),
    };
}

function get_order_status_timeline(PDO $pdo, array $order): array
{
    $stmt = $pdo->prepare(
        'SELECT status, created_at FROM order_status_history WHERE order_id = :order_id ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute([':order_id' => (int)$order['id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($events)) {
        $events[] = [
            'status' => 'placed',
            'created_at' => $order['created_at'] ?? null,
        ];
    }

    $current_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
    $last_status = normalize_order_status((string)end($events)['status']);
    if ($last_status !== $current_status) {
        $events[] = [
            'status' => $current_status,
            'created_at' => null,
        ];
    }

    return $events;
}
