<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../../core/order_status.php';
require_once __DIR__ . '/../../core/order_admin.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

ensure_order_status_history_table($pdo);
if (($_POST['status'] ?? '') === 'delivering') {
    ensure_order_delivery_assignments_table($pdo);
}

$sale_id = $_POST['sale_id'] ?? '';
$requested_status = $_POST['status'] ?? '';
$submitted_csrf_token = $_POST['csrf_token'] ?? '';
$expected_csrf_token = $_SESSION['order_status_csrf'] ?? '';
$valid_statuses = ['processing', 'confirmed', 'picked', 'packed', 'delivering', 'delivered', 'cancelled'];

if (!is_string($sale_id) || filter_var($sale_id, FILTER_VALIDATE_INT) === false || (int)$sale_id <= 0 ||
    !is_string($requested_status) || !in_array($requested_status, $valid_statuses, true) ||
    !is_string($submitted_csrf_token) || !is_string($expected_csrf_token) || $expected_csrf_token === '' ||
    !hash_equals($expected_csrf_token, $submitted_csrf_token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order or status.']);
    exit;
}

$transitions = [
    'processing' => ['confirmed', 'cancelled'],
    'confirmed' => ['processing', 'picked', 'cancelled'],
    'picked' => ['confirmed', 'packed', 'cancelled'],
    'packed' => ['picked', 'delivering', 'cancelled'],
    'delivering' => ['delivered'],
    'delivered' => [],
    'cancelled' => [],
];

try {
    $pdo->beginTransaction();
    $order_stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = :id FOR UPDATE');
    $order_stmt->execute([':id' => (int)$sale_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }

    $current_status = normalize_order_status((string)$order['order_status']);
    if ($current_status === $requested_status) {
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Order status is already up to date.']);
        exit;
    }

    if (!in_array($requested_status, $transitions[$current_status] ?? [], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This order cannot move from ' . $current_status . ' to ' . $requested_status . '.']);
        exit;
    }

    if ($requested_status === 'delivering') {
        $assignment_stmt = $pdo->prepare('SELECT 1 FROM order_delivery_assignments WHERE order_id = :order_id');
        $assignment_stmt->execute([':order_id' => (int)$sale_id]);
        if (!$assignment_stmt->fetchColumn()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Assign a rider or courier before marking this order as delivering.']);
            exit;
        }
    }

    if ($requested_status === 'cancelled') {
        $items_stmt = $pdo->prepare('SELECT product_id, SUM(quantity) AS quantity FROM order_items WHERE order_id = :order_id GROUP BY product_id');
        $items_stmt->execute([':order_id' => (int)$sale_id]);
        $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');

        foreach ($items_stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            if ($item['product_id'] !== null) {
                $restock_stmt->execute([
                    ':quantity' => (int)$item['quantity'],
                    ':product_id' => (int)$item['product_id'],
                ]);
            }
        }
    }

    $update_stmt = $pdo->prepare('UPDATE orders SET order_status = :order_status WHERE id = :id');
    $update_stmt->execute([
        ':order_status' => $requested_status,
        ':id' => (int)$sale_id,
    ]);
    $history_stmt = $pdo->prepare(
        'INSERT INTO order_status_history (order_id, status) VALUES (:order_id, :status)'
    );
    $history_stmt->execute([
        ':order_id' => (int)$sale_id,
        ':status' => $requested_status,
    ]);
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Order status updated to ' . order_status_label($requested_status) . '.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order status update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Order status could not be updated. Please try again.']);
}