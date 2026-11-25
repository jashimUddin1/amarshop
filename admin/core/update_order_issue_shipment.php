<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../../core/order_admin.php';
require_once __DIR__ . '/../../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);

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
$requested_status = is_string($_POST['shipment_status'] ?? null) ? $_POST['shipment_status'] : '';
$csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
$transitions = [
    'pending' => ['packed', 'delivering', 'cancelled'],
    'packed' => ['pending', 'delivering', 'cancelled'],
    'delivering' => ['delivered'],
    'delivered' => [],
];
if ($issue_id === false || $issue_id === null || $issue_id <= 0 ||
    !in_array($requested_status, ['packed', 'delivering', 'delivered', 'cancelled'], true) ||
    !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Choose a valid shipment status.']);
    exit;
}

try {
    ensure_order_issues_table($pdo);
    $pdo->beginTransaction();
    $issue_stmt = $pdo->prepare('SELECT id, status, shipment_status FROM order_issues WHERE id = :id FOR UPDATE');
    $issue_stmt->execute([':id' => $issue_id]);
    $issue = $issue_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$issue || $issue['status'] === 'rejected' || $issue['shipment_status'] === 'cancelled') {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Issue shipment not found or rejected.']);
        exit;
    }
    $bag_item_stmt = $pdo->prepare("
        SELECT 1 FROM order_quantity q
        JOIN delivery_bags b ON b.id = q.bag_id
        WHERE q.shipment_type = 'issue' AND q.issue_id = :issue_id
            AND q.status IN ('in_bag', 'delivering') AND b.status <> 'closed'
        LIMIT 1
        FOR UPDATE
    ");
    $bag_item_stmt->execute([':issue_id' => $issue_id]);
    if ($bag_item_stmt->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This shipment is controlled by its active delivery bag and cannot be changed here.']);
        exit;
    }
    $current_status = (string)$issue['shipment_status'];
    if (!in_array($requested_status, $transitions[$current_status] ?? [], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This shipment cannot move to the selected status.']);
        exit;
    }
    if ($requested_status === 'delivering') {
        $assignment_stmt = $pdo->prepare("
            SELECT 1
            FROM order_issue_delivery_assignments a
            JOIN deliveryman d ON d.id = a.deliveryman_id AND d.status = 'active'
            WHERE a.issue_id = :issue_id
        ");
        $assignment_stmt->execute([':issue_id' => $issue_id]);
        if (!$assignment_stmt->fetchColumn()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Assign a deliveryman before marking this shipment as delivering.']);
            exit;
        }
    }
    if ($requested_status === 'cancelled') {
        $outgoing_items_stmt = $pdo->prepare("
            SELECT product_id, SUM(quantity) AS quantity
            FROM order_issue_items
            WHERE issue_id = :issue_id AND direction = 'give' AND product_id IS NOT NULL
            GROUP BY product_id
        ");
        $outgoing_items_stmt->execute([':issue_id' => $issue_id]);
        $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');
        foreach ($outgoing_items_stmt->fetchAll(PDO::FETCH_ASSOC) as $outgoing_item) {
            $restock_stmt->execute([
                ':quantity' => (int)$outgoing_item['quantity'],
                ':product_id' => (int)$outgoing_item['product_id'],
            ]);
        }
    }
    if ($requested_status === 'delivered') {
        $returned_items_stmt = $pdo->prepare("
            SELECT product_id, SUM(quantity) AS quantity
            FROM order_issue_items
            WHERE issue_id = :issue_id AND direction = 'take' AND product_id IS NOT NULL
            GROUP BY product_id
        ");
        $returned_items_stmt->execute([':issue_id' => $issue_id]);
        $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');
        foreach ($returned_items_stmt->fetchAll(PDO::FETCH_ASSOC) as $returned_item) {
            $restock_stmt->execute([
                ':quantity' => (int)$returned_item['quantity'],
                ':product_id' => (int)$returned_item['product_id'],
            ]);
        }
    }
    if ($requested_status === 'cancelled') {
        $update_stmt = $pdo->prepare("
            UPDATE order_issues
            SET shipment_status = 'cancelled', status = 'rejected', resolved_at = NOW()
            WHERE id = :id
        ");
        $update_stmt->execute([':id' => $issue_id]);
    } else {
        $update_stmt = $pdo->prepare('UPDATE order_issues SET shipment_status = :status WHERE id = :id');
        $update_stmt->execute([':status' => $requested_status, ':id' => $issue_id]);
    }
    $history_stmt = $pdo->prepare('INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:issue_id, :status)');
    $history_stmt->execute([':issue_id' => $issue_id, ':status' => $requested_status]);
    $pdo->commit();
    $success_message = $requested_status === 'cancelled'
        ? 'Issue shipment cancelled.'
        : 'Issue shipment updated to ' . ucfirst($requested_status) . '.';
    echo json_encode(['status' => 'success', 'message' => $success_message]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Issue shipment status update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Issue shipment status could not be updated.']);
}
