<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../../core/order_status.php';
require_once __DIR__ . '/../../core/order_admin.php';
require_once __DIR__ . '/../../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);
ensure_order_status_history_table($pdo);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$raw_ids = $_POST['order_ids'] ?? '';
$source_status = $_POST['from_status'] ?? '';
$requested_status = $_POST['status'] ?? '';
$submitted_csrf_token = $_POST['csrf_token'] ?? '';
$expected_csrf_token = $_SESSION['order_status_csrf'] ?? '';
$bulk_transitions = [
    'processing' => ['confirmed'],
    'confirmed' => ['processing', 'picked'],
    'picked' => ['confirmed', 'packed'],
    'packed' => ['picked', 'packed_done'],
];

if (!is_string($raw_ids) || strlen($raw_ids) > 1000000 ||
    preg_match('/\A[1-9][0-9]*(?:,[1-9][0-9]*)*\z/', $raw_ids) !== 1 ||
    !is_string($source_status) || !is_string($requested_status) ||
    !in_array($requested_status, $bulk_transitions[$source_status] ?? [], true) ||
    !is_string($submitted_csrf_token) || !is_string($expected_csrf_token) ||
    $expected_csrf_token === '' || !hash_equals($expected_csrf_token, $submitted_csrf_token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Select valid orders and try again.']);
    exit;
}

$order_ids = [];
foreach (explode(',', $raw_ids) as $raw_id) {
    $order_id = filter_var($raw_id, FILTER_VALIDATE_INT);
    if ($order_id === false || $order_id === null || $order_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'The selected order list is invalid.']);
        exit;
    }
    $order_ids[$order_id] = $order_id;
}
$order_ids = array_values($order_ids);
if (!$order_ids) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Select at least one order.']);
    exit;
}

$admin_id = (int)($_SESSION['admin_user_id'] ?? $_SESSION['user_id']);
$admin_name = trim((string)($_SESSION['user_name'] ?? ''));
if ($admin_name === '') {
    $admin_name = 'Admin #' . $admin_id;
}

try {
    $pdo->beginTransaction();
    $order_stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = :id FOR UPDATE');
    $active_bag_stmt = $pdo->prepare("
        SELECT 1 FROM order_quantity q
        JOIN delivery_bags b ON b.id = q.bag_id
        WHERE q.shipment_type = 'order' AND q.order_id = :order_id
            AND q.status IN ('in_bag', 'delivering') AND b.status <> 'closed'
        LIMIT 1
    ");
    $update_stmt = $pdo->prepare('UPDATE orders SET order_status = :status WHERE id = :id');
    $history_stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, admin_id, admin_name)
        VALUES (:order_id, :status, :admin_id, :admin_name)
    ");

    foreach ($order_ids as $order_id) {
        $order_stmt->execute([':id' => $order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new DomainException('Order #' . $order_id . ' was not found. No orders were changed.');
        }
        $current_status = normalize_order_status((string)$order['order_status']);
        if ($current_status !== $source_status) {
            throw new DomainException('Order #' . $order_id . ' is no longer in the expected stage. Refresh and try again. No orders were changed.');
        }

        $active_bag_stmt->execute([':order_id' => $order_id]);
        if ($active_bag_stmt->fetchColumn()) {
            throw new DomainException('Order #' . $order_id . ' is controlled by an active delivery bag. No orders were changed.');
        }

        $update_stmt->execute([':status' => $requested_status, ':id' => $order_id]);
        $history_stmt->execute([
            ':order_id' => $order_id,
            ':status' => $requested_status,
            ':admin_id' => $admin_id,
            ':admin_name' => $admin_name,
        ]);
    }

    $pdo->commit();
    echo json_encode([
        'status' => 'success',
        'message' => count($order_ids) . ' order(s) moved to ' . order_status_label($requested_status) . '.',
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Bulk order status update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Selected orders could not be updated. Please try again.']);
}
