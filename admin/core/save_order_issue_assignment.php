<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../../core/order_admin.php';
require_admin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$parse_integer = static function (mixed $value): int|false|null {
    if (!is_string($value) && !is_int($value)) {
        return false;
    }
    return filter_var($value, FILTER_VALIDATE_INT);
};
$issue_id = $parse_integer($_POST['issue_id'] ?? null);
$deliveryman_id = $parse_integer($_POST['deliveryman_id'] ?? null);
$tracking_number = is_string($_POST['tracking_number'] ?? null) ? trim($_POST['tracking_number']) : '';
$notes = is_string($_POST['notes'] ?? null) ? trim($_POST['notes']) : '';
$csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($issue_id === false || $issue_id === null || $issue_id <= 0 ||
    $deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0 ||
    strlen($tracking_number) > 120 || strlen($notes) > 2000 ||
    !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Choose an active deliveryman and enter valid tracking information.']);
    exit;
}

try {
    ensure_order_issues_table($pdo);
    $pdo->beginTransaction();
    $deliveryman_stmt = $pdo->prepare("SELECT id, customar_name, phone FROM deliveryman WHERE id = :id AND status = 'active'");
    $deliveryman_stmt->execute([':id' => $deliveryman_id]);
    $deliveryman = $deliveryman_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$deliveryman) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'The selected deliveryman is not active.']);
        exit;
    }

    $issue_stmt = $pdo->prepare("SELECT id, status, shipment_status FROM order_issues WHERE id = :id FOR UPDATE");
    $issue_stmt->execute([':id' => $issue_id]);
    $issue = $issue_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$issue || $issue['status'] === 'rejected' || !in_array($issue['shipment_status'], ['pending', 'packed'], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This issue shipment cannot be assigned.']);
        exit;
    }

    $assignment_stmt = $pdo->prepare("
        INSERT INTO order_issue_delivery_assignments
            (issue_id, deliveryman_id, delivery_type, provider_name, contact_name, contact_phone, tracking_number, notes)
        VALUES
            (:issue_id, :deliveryman_id, 'rider', :provider_name, :contact_name, :contact_phone, :tracking_number, :notes)
        ON DUPLICATE KEY UPDATE
            deliveryman_id = VALUES(deliveryman_id),
            provider_name = VALUES(provider_name),
            contact_name = VALUES(contact_name),
            contact_phone = VALUES(contact_phone),
            tracking_number = VALUES(tracking_number),
            notes = VALUES(notes),
            updated_at = CURRENT_TIMESTAMP
    ");
    $assignment_stmt->execute([
        ':issue_id' => $issue_id,
        ':deliveryman_id' => $deliveryman_id,
        ':provider_name' => $deliveryman['customar_name'],
        ':contact_name' => $deliveryman['customar_name'],
        ':contact_phone' => $deliveryman['phone'],
        ':tracking_number' => $tracking_number,
        ':notes' => $notes !== '' ? $notes : null,
    ]);
    if ($issue['shipment_status'] === 'pending') {
        $pdo->prepare("UPDATE order_issues SET shipment_status = 'packed' WHERE id = :id")
            ->execute([':id' => $issue_id]);
        $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:issue_id, 'packed')")
            ->execute([':issue_id' => $issue_id]);
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Issue shipment assigned to deliveryman.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Issue shipment assignment failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Issue shipment assignment could not be saved.']);
}
