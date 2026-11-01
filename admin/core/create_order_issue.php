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

$parse_integer = static function (mixed $value): int|false|null {
    if (!is_string($value) && !is_int($value)) {
        return false;
    }
    return filter_var($value, FILTER_VALIDATE_INT);
};
$order_id = $parse_integer($_POST['order_id'] ?? null);
$issue_type = is_string($_POST['issue_type'] ?? null) ? $_POST['issue_type'] : '';
$message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
$order_item_id = $parse_integer($_POST['order_item_id'] ?? null);
$take_quantity = $parse_integer($_POST['take_quantity'] ?? null);
$give_product_id = $parse_integer($_POST['give_product_id'] ?? null);
$give_quantity = $parse_integer($_POST['give_quantity'] ?? null);
$give_size = is_string($_POST['give_size'] ?? null) ? trim($_POST['give_size']) : '';
$csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($order_id === false || $order_id === null || $order_id <= 0 ||
    !in_array($issue_type, ['return', 'missing', 'replacement'], true) ||
    strlen($message) > 3000 ||
    !is_string($expected_csrf) || $expected_csrf === '' ||
    !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Choose an issue type and the required products.']);
    exit;
}

try {
    ensure_order_issues_table($pdo);
    $pdo->beginTransaction();
    $order_stmt = $pdo->prepare('SELECT id, user_id, order_status, order_number FROM orders WHERE id = :id FOR UPDATE');
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    if (normalize_order_status((string)$order['order_status']) !== 'delivered' || (int)$order['user_id'] <= 0) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'A valid customer issue can only be created for a delivered order.']);
        exit;
    }

    $order_item_stmt = $pdo->prepare("
        SELECT oi.id, oi.product_id, oi.product_code, oi.size, oi.quantity, p.name
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.id = :item_id AND oi.order_id = :order_id
        FOR UPDATE
    ");
    $order_item_stmt->execute([':item_id' => $order_item_id, ':order_id' => $order_id]);
    $order_item = $order_item_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order_item || (int)$order_item['product_id'] <= 0) {
        throw new InvalidArgumentException('Choose a product from this order.');
    }

    $previous_issue_quantity_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ii.quantity), 0)
        FROM order_issue_items ii
        JOIN order_issues i ON i.id = ii.issue_id
        WHERE ii.source_order_item_id = :source_item_id
            AND ii.direction = :direction
            AND i.status <> 'rejected'
            AND (:issue_type <> 'missing' OR i.issue_type = 'missing')
    ");
    $previous_issue_quantity_stmt->execute([
        ':source_item_id' => (int)$order_item['id'],
        ':direction' => $issue_type === 'missing' ? 'give' : 'take',
        ':issue_type' => $issue_type,
    ]);
    $previous_issue_quantity = (int)$previous_issue_quantity_stmt->fetchColumn();
    $order_quantity_remaining = max(0, (int)$order_item['quantity'] - $previous_issue_quantity);

    $take_qty = 0;
    $give_qty = 0;
    $outgoing_product = null;
    if ($issue_type === 'return') {
        $take_qty = $take_quantity;
        if ($take_qty === false || $take_qty < 1 || $take_qty > $order_quantity_remaining) {
            throw new InvalidArgumentException('Return quantity exceeds the remaining eligible quantity for this order item.');
        }
    } elseif ($issue_type === 'missing') {
        $give_qty = $give_quantity;
        if ($give_qty === false || $give_qty < 1 || $give_qty > $order_quantity_remaining) {
            throw new InvalidArgumentException('Missing-item quantity exceeds the remaining eligible quantity for this order item.');
        }
        $product_stmt = $pdo->prepare('SELECT id, name, product_code, size, stock FROM products WHERE id = :id FOR UPDATE');
        $product_stmt->execute([':id' => (int)$order_item['product_id']]);
        $outgoing_product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$outgoing_product || (int)$outgoing_product['stock'] < $give_qty) {
            throw new InvalidArgumentException('The missing product does not have enough stock to ship.');
        }
    } else {
        $take_qty = $take_quantity;
        $give_qty = $give_quantity;
        if ($take_qty === false || $take_qty < 1 || $take_qty > $order_quantity_remaining ||
            $give_product_id === false || $give_product_id === null || $give_product_id <= 0 ||
            $give_qty === false || $give_qty < 1) {
            throw new InvalidArgumentException('Choose both the product to collect and the replacement product to deliver.');
        }
        $product_stmt = $pdo->prepare('SELECT id, name, product_code, size, stock FROM products WHERE id = :id FOR UPDATE');
        $product_stmt->execute([':id' => $give_product_id]);
        $outgoing_product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$outgoing_product || (int)$outgoing_product['stock'] < $give_qty) {
            throw new InvalidArgumentException('The replacement product does not have enough stock.');
        }
        $sizes = array_values(array_filter(array_map('trim', explode(',', (string)$outgoing_product['size']))));
        if ($sizes && !in_array($give_size, $sizes, true)) {
            throw new InvalidArgumentException('Choose a valid size for the replacement product.');
        }
        if (!$sizes) {
            $give_size = '';
        }
    }

    if ($give_qty > 0) {
        $stock_update = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');
        $stock_update->execute([
            ':quantity' => $give_qty,
            ':id' => (int)$outgoing_product['id'],
            ':minimum' => $give_qty,
        ]);
        if ($stock_update->rowCount() !== 1) {
            throw new RuntimeException('Product stock changed. Refresh and retry the issue.');
        }
    }

    $number_stmt = $pdo->prepare('SELECT COUNT(*) FROM order_issues WHERE order_id = :order_id');
    $number_stmt->execute([':order_id' => $order_id]);
    $shipment_number = order_shipment_number((string)$order['order_number'], issue_shipment_suffix((int)$number_stmt->fetchColumn() + 1));
    $issue_stmt = $pdo->prepare("
        INSERT INTO order_issues (order_id, user_id, created_by_admin_id, issue_type, shipment_number, message)
        VALUES (:order_id, :user_id, :admin_id, :issue_type, :shipment_number, :message)
    ");
    $default_message = match ($issue_type) {
        'missing' => 'Deliver: ' . $outgoing_product['name'] . ' (' . $outgoing_product['product_code'] . '), quantity ' . $give_qty . '.',
        'return' => 'Collect: ' . ($order_item['name'] ?: $order_item['product_code']) . ' (' . $order_item['product_code'] . '), quantity ' . $take_qty . '.',
        default => 'Collect: ' . ($order_item['name'] ?: $order_item['product_code']) . ' (' . $order_item['product_code'] . '), quantity ' . $take_qty
            . '. Deliver: ' . $outgoing_product['name'] . ' (' . $outgoing_product['product_code'] . '), quantity ' . $give_qty . '.',
    };
    $issue_stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => (int)$order['user_id'],
        ':admin_id' => (int)$_SESSION['admin_user_id'],
        ':issue_type' => $issue_type,
        ':shipment_number' => $shipment_number,
        ':message' => $message !== '' ? $message : $default_message,
    ]);
    $issue_id = (int)$pdo->lastInsertId();
    $line_stmt = $pdo->prepare("
        INSERT INTO order_issue_items
            (issue_id, direction, source_order_item_id, product_id, product_name, product_code, size, quantity)
        VALUES (:issue_id, :direction, :source_item_id, :product_id, :product_name, :product_code, :size, :quantity)
    ");
    if ($take_qty > 0) {
        $line_stmt->execute([
            ':issue_id' => $issue_id,
            ':direction' => 'take',
            ':source_item_id' => (int)$order_item['id'],
            ':product_id' => (int)$order_item['product_id'],
            ':product_name' => (string)($order_item['name'] ?: $order_item['product_code']),
            ':product_code' => (string)$order_item['product_code'],
            ':size' => (string)$order_item['size'],
            ':quantity' => $take_qty,
        ]);
    }
    if ($give_qty > 0) {
        $line_stmt->execute([
            ':issue_id' => $issue_id,
            ':direction' => 'give',
            ':source_item_id' => $issue_type === 'missing' ? (int)$order_item['id'] : null,
            ':product_id' => (int)$outgoing_product['id'],
            ':product_name' => (string)$outgoing_product['name'],
            ':product_code' => (string)$outgoing_product['product_code'],
            ':size' => $issue_type === 'missing' ? (string)$order_item['size'] : $give_size,
            ':quantity' => $give_qty,
        ]);
    }
    $shipment_history_stmt = $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:issue_id, 'pending')");
    $shipment_history_stmt->execute([':issue_id' => $issue_id]);
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Issue shipment #' . $shipment_number . ' created.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin order issue creation failed: ' . $e->getMessage());
    $client_error = $e instanceof InvalidArgumentException;
    http_response_code($client_error ? 400 : 500);
    echo json_encode(['status' => 'error', 'message' => $client_error ? $e->getMessage() : 'Order issue could not be created. Please try again.']);
}
