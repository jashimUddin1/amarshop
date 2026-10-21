<?php
require_once '../auth/config.php';
require_once __DIR__ . '/../../core/order_status.php';
require_once __DIR__ . '/../../core/order_admin.php';
require_admin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$request_id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
$decision = $_POST['decision'] ?? '';
$admin_note = trim((string)($_POST['admin_note'] ?? ''));
$csrf = $_POST['csrf_token'] ?? '';
$expected = $_SESSION['order_status_csrf'] ?? '';
if ($request_id === false || $request_id === null || $request_id <= 0 ||
    !is_string($decision) || !in_array($decision, ['approve', 'reject'], true) ||
    !is_string($csrf) || !is_string($expected) || $expected === '' || !hash_equals($expected, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request decision.']);
    exit;
}

try {
    ensure_order_requests_table($pdo);
    ensure_order_status_history_table($pdo);
    $pdo->beginTransaction();
    $request_stmt = $pdo->prepare('SELECT * FROM order_requests WHERE id = :id FOR UPDATE');
    $request_stmt->execute([':id' => $request_id]);
    $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$request || $request['status'] !== 'pending') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'This request has already been resolved or does not exist.']);
        exit;
    }

    $order_stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
    $order_stmt->execute([':id' => (int)$request['order_id']]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || in_array(normalize_order_status((string)$order['order_status']), ['cancelled', 'delivered'], true)) {
        throw new RuntimeException('This order is closed, so the request cannot be approved.');
    }

    if ($decision === 'approve' && $request['request_type'] === 'add_item') {
        if (!in_array(normalize_order_status((string)$order['order_status']), ['processing', 'confirmed', 'picked', 'packed'], true)) {
            throw new RuntimeException('Items can only be added before delivery starts.');
        }
        $product_stmt = $pdo->prepare('SELECT id, product_code, size, stock, ROUND(COALESCE(actual_price, sell_price)) AS price FROM products WHERE id = :id FOR UPDATE');
        $product_stmt->execute([':id' => (int)$request['product_id']]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        $quantity = (int)$request['quantity'];
        if (!$product || (int)$product['stock'] < $quantity) {
            throw new RuntimeException('The requested product is no longer available in the requested quantity.');
        }
        $available_sizes = array_values(array_filter(array_map('trim', explode(',', (string)$product['size']))));
        if ($available_sizes && !in_array((string)$request['size'], $available_sizes, true)) {
            throw new RuntimeException('The requested size is no longer available.');
        }
        $stock_stmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');
        $stock_stmt->execute([':quantity' => $quantity, ':id' => (int)$product['id'], ':minimum' => $quantity]);
        if ($stock_stmt->rowCount() !== 1) {
            throw new RuntimeException('Product stock changed; refresh and review this request again.');
        }
        $price = (int)$product['price'];
        $insert_stmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, product_code, size, quantity, price, total_price)
            VALUES (:order_id, :product_id, :product_code, :size, :quantity, :price, :total_price)
        ');
        $insert_stmt->execute([
            ':order_id' => (int)$order['id'],
            ':product_id' => (int)$product['id'],
            ':product_code' => $product['product_code'],
            ':size' => $request['size'],
            ':quantity' => $quantity,
            ':price' => $price,
            ':total_price' => $price * $quantity,
        ]);
        $subtotal_stmt = $pdo->prepare('SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = :id');
        $subtotal_stmt->execute([':id' => (int)$order['id']]);
        $subtotal = (int)round((float)$subtotal_stmt->fetchColumn());
        $total_stmt = $pdo->prepare('UPDATE orders SET subtotal = :subtotal, grand_total = :grand_total WHERE id = :id');
        $total_stmt->execute([
            ':subtotal' => $subtotal,
            ':grand_total' => $subtotal + (int)round((float)$order['shipping_charge']),
            ':id' => (int)$order['id'],
        ]);
    } elseif ($decision === 'approve' && $request['request_type'] === 'cancel') {
        if (!in_array(normalize_order_status((string)$order['order_status']), ['processing', 'confirmed', 'picked', 'packed'], true)) {
            throw new RuntimeException('Cancellation cannot be approved after delivery has started.');
        }
        $items_stmt = $pdo->prepare('SELECT product_id, SUM(quantity) AS quantity FROM order_items WHERE order_id = :id GROUP BY product_id');
        $items_stmt->execute([':id' => (int)$order['id']]);
        $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');
        foreach ($items_stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            if ($item['product_id'] !== null) {
                $restock_stmt->execute([':quantity' => (int)$item['quantity'], ':product_id' => (int)$item['product_id']]);
            }
        }
        $update_request_order = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = :id");
        $update_request_order->execute([':id' => (int)$order['id']]);
        $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (:order_id, 'cancelled')")->execute([':order_id' => (int)$order['id']]);
    }

    $new_status = $decision === 'approve' ? 'approved' : 'rejected';
    $update_stmt = $pdo->prepare('UPDATE order_requests SET status = :status, admin_note = :note, resolved_at = NOW() WHERE id = :id');
    $update_stmt->execute([':status' => $new_status, ':note' => $admin_note, ':id' => $request_id]);
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Request ' . $new_status . '.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order request resolution failed: ' . $e->getMessage());
    http_response_code($e instanceof RuntimeException ? 409 : 500);
    echo json_encode(['status' => 'error', 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Request could not be resolved. Please try again.']);
}
