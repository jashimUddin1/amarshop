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

$order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$delivery_type = $_POST['delivery_type'] ?? '';
$provider_name = $_POST['provider_name'] ?? '';
$contact_name = $_POST['contact_name'] ?? '';
$contact_phone = $_POST['contact_phone'] ?? '';
$tracking_number = $_POST['tracking_number'] ?? '';
$notes = $_POST['notes'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($order_id === false || $order_id === null || $order_id <= 0 ||
    !is_string($delivery_type) || !in_array($delivery_type, ['rider', 'courier'], true) ||
    !is_string($provider_name) || !is_string($contact_name) || !is_string($contact_phone) ||
    !is_string($tracking_number) || !is_string($notes) ||
    !is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please provide valid delivery assignment details.']);
    exit;
}
$provider_name = trim($provider_name);
$contact_name = trim($contact_name);
$contact_phone = trim($contact_phone);
$tracking_number = trim($tracking_number);
$notes = trim($notes);
if ($provider_name === '' || strlen($provider_name) > 120 || strlen($contact_name) > 120 ||
    strlen($contact_phone) > 40 || strlen($tracking_number) > 120) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please check the provider, contact, phone, and tracking details.']);
    exit;
}

try {
    ensure_order_delivery_assignments_table($pdo);
    $pdo->beginTransaction();
    $order_stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = :id FOR UPDATE');
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    if (normalize_order_status((string)$order['order_status']) !== 'packed') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'A rider or courier can only be assigned when the order is packed.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO order_delivery_assignments
            (order_id, delivery_type, provider_name, contact_name, contact_phone, tracking_number, notes)
        VALUES
            (:order_id, :delivery_type, :provider_name, :contact_name, :contact_phone, :tracking_number, :notes)
        ON DUPLICATE KEY UPDATE
            delivery_type = VALUES(delivery_type),
            provider_name = VALUES(provider_name),
            contact_name = VALUES(contact_name),
            contact_phone = VALUES(contact_phone),
            tracking_number = VALUES(tracking_number),
            notes = VALUES(notes),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':order_id' => $order_id,
        ':delivery_type' => $delivery_type,
        ':provider_name' => $provider_name,
        ':contact_name' => $contact_name,
        ':contact_phone' => $contact_phone,
        ':tracking_number' => $tracking_number,
        ':notes' => $notes === '' ? null : $notes,
    ]);
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Delivery assignment saved.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Delivery assignment failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Delivery assignment could not be saved. Please try again.']);
}
