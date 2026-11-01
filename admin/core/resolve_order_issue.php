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

$issue_id_input = $_POST['issue_id'] ?? null;
$issue_id = is_string($issue_id_input) || is_int($issue_id_input)
    ? filter_var($issue_id_input, FILTER_VALIDATE_INT)
    : false;
$decision = is_string($_POST['decision'] ?? null) ? $_POST['decision'] : '';
$admin_note = is_string($_POST['admin_note'] ?? null) ? trim($_POST['admin_note']) : '';
$csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($issue_id === false || $issue_id === null || $issue_id <= 0 ||
    !in_array($decision, ['resolve', 'reject'], true) ||
    strlen($admin_note) > 3000 ||
    !is_string($expected_csrf) || $expected_csrf === '' ||
    !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid issue decision.']);
    exit;
}

try {
    ensure_order_issues_table($pdo);
    $pdo->beginTransaction();
    $issue_stmt = $pdo->prepare('SELECT * FROM order_issues WHERE id = :id FOR UPDATE');
    $issue_stmt->execute([':id' => $issue_id]);
    $issue = $issue_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$issue || $issue['status'] !== 'pending') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This issue has already been resolved or does not exist.']);
        exit;
    }
    if ($decision === 'reject' && in_array($issue['shipment_status'], ['delivering', 'delivered'], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'An issue shipment cannot be rejected after delivery has started.']);
        exit;
    }
    if ($decision === 'reject') {
        $outgoing_stmt = $pdo->prepare("
            SELECT product_id, SUM(quantity) AS quantity
            FROM order_issue_items
            WHERE issue_id = :issue_id AND direction = 'give' AND product_id IS NOT NULL
            GROUP BY product_id
        ");
        $outgoing_stmt->execute([':issue_id' => $issue_id]);
        $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');
        foreach ($outgoing_stmt->fetchAll(PDO::FETCH_ASSOC) as $outgoing_item) {
            $restock_stmt->execute([
                ':quantity' => (int)$outgoing_item['quantity'],
                ':product_id' => (int)$outgoing_item['product_id'],
            ]);
        }
    }
    $status = $decision === 'resolve' ? 'resolved' : 'rejected';
    $shipment_status = $decision === 'reject' ? 'cancelled' : $issue['shipment_status'];
    $stmt = $pdo->prepare("
        UPDATE order_issues
        SET status = :status, shipment_status = :shipment_status, admin_note = :admin_note, resolved_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':shipment_status' => $shipment_status,
        ':admin_note' => $admin_note !== '' ? $admin_note : null,
        ':id' => $issue_id,
    ]);
    if ($decision === 'reject' && $issue['shipment_status'] !== 'cancelled') {
        $history_stmt = $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:issue_id, 'cancelled')");
        $history_stmt->execute([':issue_id' => $issue_id]);
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Issue marked ' . $status . '.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order issue resolution failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Issue could not be updated. Please try again.']);
}
