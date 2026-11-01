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

$request_id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
$message = trim((string)($_POST['message'] ?? ''));
$csrf = $_POST['csrf_token'] ?? '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($request_id === false || $request_id === null || $request_id <= 0 ||
    $message === '' || strlen($message) > 3000 ||
    !is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' ||
    !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Enter a reply and try again.']);
    exit;
}

try {
    ensure_order_requests_table($pdo);
    ensure_order_request_messages_table($pdo);
    $pdo->beginTransaction();
    $request_stmt = $pdo->prepare("SELECT id FROM order_requests WHERE id = :id AND status = 'pending' LIMIT 1 FOR UPDATE");
    $request_stmt->execute([':id' => $request_id]);
    if (!$request_stmt->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'This pending request was not found.']);
        exit;
    }

    $message_stmt = $pdo->prepare("
        INSERT INTO order_request_messages (request_id, sender_type, sender_id, message)
        VALUES (:request_id, 'admin', :sender_id, :message)
    ");
    $message_stmt->execute([
        ':request_id' => $request_id,
        ':sender_id' => (int)$_SESSION['user_id'],
        ':message' => $message,
    ]);
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reply sent.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin order request reply could not be saved: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Reply could not be sent. Please try again.']);
}
