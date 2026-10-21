<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/order_status.php';
require_once __DIR__ . '/order_admin.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    http_response_code(empty($_SESSION['user_id']) ? 401 : 405);
    echo json_encode(['status' => 'error', 'message' => 'Please sign in and submit a valid request.']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
$expected_csrf = $_SESSION['order_request_csrf'] ?? '';
$order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$request_type = $_POST['request_type'] ?? '';
$message = trim((string)($_POST['message'] ?? ''));
if (!is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf) ||
    $order_id === false || $order_id === null || $order_id <= 0 ||
    !is_string($request_type) || !in_array($request_type, ['add_item', 'update', 'cancel'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order request. Refresh the page and try again.']);
    exit;
}

$product_id = null;
$size = '';
$quantity = null;
if ($request_type === 'add_item') {
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $size = trim((string)($_POST['size'] ?? ''));
    if ($product_id === false || $product_id === null || $product_id <= 0 ||
        $quantity === false || $quantity === null || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Choose an item and a valid quantity.']);
        exit;
    }
    $product_stmt = $pdo->prepare('SELECT id, name, product_code, size FROM products WHERE id = :id AND stock > 0');
    $product_stmt->execute([':id' => $product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This product is currently out of stock.']);
        exit;
    }
    $available_sizes = array_values(array_filter(array_map('trim', explode(',', (string)$product['size']))));
    if ($available_sizes && !in_array($size, $available_sizes, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Choose an available size for this product.']);
        exit;
    }
    $message = 'Please add ' . $product['name'] . ' (' . $product['product_code'] . ')'
        . ($size !== '' ? ' - ' . $size : '') . ', quantity ' . (int)$quantity . '.';
} elseif ($request_type === 'update' && $message === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tell us what you would like to update.']);
    exit;
} elseif ($request_type === 'cancel' && $message === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please provide a reason for cancelling this order.']);
    exit;
}

try {
    ensure_order_requests_table($pdo);
    $order_stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = :id AND user_id = :user_id');
    $order_stmt->execute([':id' => $order_id, ':user_id' => (int)$_SESSION['user_id']]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    if (in_array(normalize_order_status((string)$order['order_status']), ['cancelled', 'delivered'], true)) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Requests are not available for cancelled or delivered orders.']);
        exit;
    }
    if ($request_type === 'add_item' &&
        !in_array(normalize_order_status((string)$order['order_status']), ['processing', 'confirmed', 'picked', 'packed'], true)) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Items can only be requested before delivery starts.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO order_requests (order_id, user_id, request_type, product_id, size, quantity, message)
        VALUES (:order_id, :user_id, :request_type, :product_id, :size, :quantity, :message)
    ");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => (int)$_SESSION['user_id'],
        ':request_type' => $request_type,
        ':product_id' => $product_id,
        ':size' => $size,
        ':quantity' => $quantity,
        ':message' => $message,
    ]);
    $_SESSION['order_request_flash'] = 'Your request has been sent to the admin for review.';
    echo json_encode(['status' => 'success', 'message' => $_SESSION['order_request_flash']]);
} catch (Throwable $e) {
    error_log('Order request could not be created: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Your request could not be submitted. Please try again.']);
}
