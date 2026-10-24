<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../../core/order_status.php';
require_admin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';
$expected_csrf = $_SESSION['order_status_csrf'] ?? '';
if ($order_id === false || $order_id === null || $order_id <= 0 ||
    !is_string($action) || !is_string($csrf) || !is_string($expected_csrf) ||
    $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order edit request.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $order_stmt = $pdo->prepare('SELECT order_status, shipping_charge FROM orders WHERE id = :id FOR UPDATE');
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    $status = normalize_order_status((string)$order['order_status']);
    if (in_array($status, ['cancelled', 'delivered'], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Cancelled or delivered orders cannot be edited.']);
        exit;
    }
    if (in_array($action, ['add_item', 'update_item', 'remove_item'], true) &&
        !in_array($status, ['processing', 'confirmed', 'picked', 'packed'], true)) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Order items can only be changed before delivery starts.']);
        exit;
    }

    if ($action === 'customer') {
        $name = trim((string)($_POST['fullname'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $payment_method = trim((string)($_POST['payment_method'] ?? 'COD'));
        $shipping_charge = filter_var($_POST['shipping_charge'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($name === '' || $phone === '' || $address === '' ||
            ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) ||
            $payment_method === '' || $shipping_charge === false || $shipping_charge < 0) {
            throw new InvalidArgumentException('Name, phone, address, and a valid delivery charge are required.');
        }
        $shipping_charge = (int)round((float)$shipping_charge);
        $stmt = $pdo->prepare("
            UPDATE orders
            SET fullname = :fullname, phone = :phone, email = :email, address = :address,
                payment_method = :payment_method, shipping_charge = :shipping_charge,
                grand_total = subtotal + :total_shipping
            WHERE id = :id
        ");
        $stmt->execute([
            ':fullname' => $name, ':phone' => $phone, ':email' => $email, ':address' => $address,
            ':payment_method' => $payment_method, ':shipping_charge' => $shipping_charge,
            ':total_shipping' => $shipping_charge, ':id' => $order_id,
        ]);
    } elseif ($action === 'add_item') {
        $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($product_id === false || $product_id === null || $product_id <= 0 || $quantity === false || $quantity === null || $quantity < 1) {
            throw new InvalidArgumentException('Choose a product and a quantity greater than zero.');
        }
        $product_stmt = $pdo->prepare('SELECT id, product_code, size, COALESCE(actual_price, sell_price) AS price, stock FROM products WHERE id = :id FOR UPDATE');
        $product_stmt->execute([':id' => $product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product || (int)$product['stock'] < $quantity) {
            throw new InvalidArgumentException('Product is unavailable or there is not enough stock.');
        }
        $stock_update = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');
        $stock_update->execute([':quantity' => $quantity, ':id' => $product_id, ':minimum' => $quantity]);
        if ($stock_update->rowCount() !== 1) {
            throw new RuntimeException('Product stock changed. Refresh the order and try again.');
        }
        $size_parts = array_values(array_filter(array_map('trim', explode(',', (string)$product['size']))));
        $item_size = trim((string)($_POST['size'] ?? ''));
        if ($size_parts && $item_size !== '' && !in_array($item_size, $size_parts, true)) {
            throw new InvalidArgumentException('The selected product size is not available.');
        }
        if (!$size_parts || $item_size === '') {
            $item_size = $size_parts[0] ?? '';
        }
        $insert = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_code, size, quantity, price, total_price)
            VALUES (:order_id, :product_id, :product_code, :size, :quantity, :price, :total_price)
        ");
        $unit_price = (int)round((float)$product['price']);
        $insert->execute([
            ':order_id' => $order_id, ':product_id' => $product_id,
            ':product_code' => $product['product_code'], ':size' => $item_size,
            ':quantity' => $quantity, ':price' => $unit_price,
            ':total_price' => $unit_price * $quantity,
        ]);
    } elseif ($action === 'update_item' || $action === 'remove_item') {
        $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        if ($item_id === false || $item_id === null || $item_id <= 0) {
            throw new InvalidArgumentException('Invalid order item.');
        }
        $item_stmt = $pdo->prepare('SELECT id, product_id, quantity, price FROM order_items WHERE id = :id AND order_id = :order_id FOR UPDATE');
        $item_stmt->execute([':id' => $item_id, ':order_id' => $order_id]);
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new InvalidArgumentException('Order item not found.');
        }
        $product_id = (int)$item['product_id'];
        if ($action === 'remove_item') {
            $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = :order_id');
            $count_stmt->execute([':order_id' => $order_id]);
            if ((int)$count_stmt->fetchColumn() <= 1) {
                throw new InvalidArgumentException('An order must contain at least one item. Add another product before removing this one.');
            }
            $delete = $pdo->prepare('DELETE FROM order_items WHERE id = :id AND order_id = :order_id');
            $delete->execute([':id' => $item_id, ':order_id' => $order_id]);
            if ($product_id > 0) {
                $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id')->execute([
                    ':quantity' => (int)$item['quantity'], ':id' => $product_id,
                ]);
            }
        } else {
            $new_quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($new_quantity === false || $new_quantity === null || $new_quantity < 1) {
                throw new InvalidArgumentException('Quantity must be at least one. Use Remove to delete an item.');
            }
            $difference = $new_quantity - (int)$item['quantity'];
            if ($difference > 0 && $product_id > 0) {
                $stock_update = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');
                $stock_update->execute([':quantity' => $difference, ':id' => $product_id, ':minimum' => $difference]);
                if ($stock_update->rowCount() !== 1) {
                    throw new InvalidArgumentException('There is not enough stock for that quantity.');
                }
            } elseif ($difference < 0 && $product_id > 0) {
                $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id')->execute([
                    ':quantity' => abs($difference), ':id' => $product_id,
                ]);
            }
            $update = $pdo->prepare('UPDATE order_items SET quantity = :quantity, total_price = :total WHERE id = :id AND order_id = :order_id');
            $update->execute([
                ':quantity' => $new_quantity,
                ':total' => (int)round((float)$item['price']) * $new_quantity,
                ':id' => $item_id, ':order_id' => $order_id,
            ]);
        }
    } else {
        throw new InvalidArgumentException('Unsupported order edit action.');
    }

    $totals_stmt = $pdo->prepare('SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = :order_id');
    $totals_stmt->execute([':order_id' => $order_id]);
    $subtotal = (float)$totals_stmt->fetchColumn();
    $totals_update = $pdo->prepare('UPDATE orders SET subtotal = :subtotal, grand_total = :grand_total WHERE id = :id');
    $totals_update->execute([
        ':subtotal' => $subtotal,
        ':grand_total' => $subtotal + (float)$order['shipping_charge'],
        ':id' => $order_id,
    ]);
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Order updated successfully.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof InvalidArgumentException) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    error_log('Order edit failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Order could not be updated. Please try again.']);
}
