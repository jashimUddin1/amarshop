<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('POST is required.');
}
pos_verify_csrf();

$customer_name = trim((string)($_POST['customer_name'] ?? ''));
$customer_phone = trim((string)($_POST['customer_phone'] ?? ''));
$items_json = is_string($_POST['items'] ?? null) ? $_POST['items'] : '';
$discount = filter_var($_POST['discount_amount'] ?? null, FILTER_VALIDATE_FLOAT);
$cash_received = filter_var($_POST['cash_received'] ?? null, FILTER_VALIDATE_FLOAT);

if (strlen($customer_name) > 180 || strlen($customer_phone) > 40 ||
    $discount === false || $discount < 0 ||
    $cash_received === false || $cash_received < 0 ||
    $items_json === '' || strlen($items_json) > 50000) {
    pos_flash('error', 'Check the customer details, discount and cash received, then try again.');
    header('Location: sale.php');
    exit;
}

try {
    $submitted_items = json_decode($items_json, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $submitted_items = null;
}
if (!is_array($submitted_items) || count($submitted_items) === 0 || count($submitted_items) > 100) {
    pos_flash('error', 'A bill must include 1 to 100 product lines.');
    header('Location: sale.php');
    exit;
}

$quantities_by_product = [];
foreach ($submitted_items as $item) {
    if (!is_array($item) || !isset($item['product_id'], $item['quantity'])) {
        pos_flash('error', 'One of the selected products is invalid.');
        header('Location: sale.php');
        exit;
    }
    $product_id = filter_var($item['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
    if ($product_id === false || $product_id === null || $product_id <= 0 ||
        $quantity === false || $quantity === null || $quantity <= 0 || $quantity > 100000 ||
        isset($quantities_by_product[$product_id])) {
        pos_flash('error', 'Each product needs a unique line with a valid positive quantity.');
        header('Location: sale.php');
        exit;
    }
    $unit_price = null;
    if (array_key_exists('unit_price', $item)) {
        $unit_price = filter_var($item['unit_price'], FILTER_VALIDATE_FLOAT);
        if ($unit_price === false || !is_finite((float)$unit_price) ||
            $unit_price < 0 || $unit_price > 9999999999.99) {
            pos_flash('error', 'Each product needs a valid unit price.');
            header('Location: sale.php');
            exit;
        }
        $unit_price = round((float)$unit_price, 2);
    }
    $quantities_by_product[$product_id] = [
        'quantity' => $quantity,
        'unit_price' => $unit_price,
    ];
}

ksort($quantities_by_product, SORT_NUMERIC);

try {
    $pdo->beginTransaction();
    $select_product = $pdo->prepare("
        SELECT id, product_code, name, size, stock,
            COALESCE(actual_price, sell_price) AS price
        FROM products
        WHERE id = :id AND deleted_at IS NULL
        FOR UPDATE
    ");
    $update_stock = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');

    $items = [];
    $subtotal = 0.0;
    foreach ($quantities_by_product as $product_id => $submitted_item) {
        $quantity = $submitted_item['quantity'];
        $select_product->execute([':id' => $product_id]);
        $product = $select_product->fetch();
        if (!$product) {
            throw new InvalidArgumentException('A selected product is no longer available.');
        }
        if ((int)$product['stock'] < $quantity) {
            throw new InvalidArgumentException(
                'Not enough stock for ' . (string)$product['name'] . '. Available: ' . (int)$product['stock'] . '.'
            );
        }
        $unit_price = $submitted_item['unit_price'] ?? round((float)$product['price'], 2);
        if (!is_finite((float)$unit_price) || $unit_price < 0 || $unit_price > 9999999999.99) {
            throw new InvalidArgumentException('A selected product has an invalid selling price.');
        }
        $line_total = round($unit_price * $quantity, 2);
        $subtotal += $line_total;
        $items[] = [
            'product_id' => (int)$product['id'],
            'product_code' => (string)($product['product_code'] ?? ''),
            'product_name' => (string)$product['name'],
            'size' => (string)($product['size'] ?? ''),
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'line_total' => $line_total,
            'stock' => (int)$product['stock'],
        ];
    }

    $subtotal = round($subtotal, 2);
    $discount = round((float)$discount, 2);
    $grand_total = round($subtotal - $discount, 2);
    $cash_received = round((float)$cash_received, 2);
    if ($discount > $subtotal || $grand_total < 0 || $cash_received < $grand_total) {
        throw new InvalidArgumentException('Discount cannot exceed the subtotal, and cash received must cover the amount due.');
    }
    if ($subtotal > 9999999999.99) {
        throw new InvalidArgumentException('The bill total is above the supported maximum.');
    }

    $temporary_receipt_no = 'POS-TMP-' . bin2hex(random_bytes(12));
    $insert_sale = $pdo->prepare("
        INSERT INTO offline_pos_sales
            (receipt_no, cashier_id, customer_name, customer_phone, subtotal, discount_amount,
             grand_total, cash_received, change_amount, payment_method)
        VALUES
            (:receipt, :cashier, :customer_name, :customer_phone, :subtotal, :discount,
             :total, :cash_received, :change_amount, 'cash')
    ");
    $insert_sale->execute([
        ':receipt' => $temporary_receipt_no,
        ':cashier' => (int)$user['id'],
        ':customer_name' => $customer_name,
        ':customer_phone' => $customer_phone,
        ':subtotal' => $subtotal,
        ':discount' => $discount,
        ':total' => $grand_total,
        ':cash_received' => $cash_received,
        ':change_amount' => round($cash_received - $grand_total, 2),
    ]);
    $sale_id = (int)$pdo->lastInsertId();
    $receipt_no = 'POS-' . date('Ymd') . '-' . str_pad((string)$sale_id, 6, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE offline_pos_sales SET receipt_no = :receipt WHERE id = :id')
        ->execute([':receipt' => $receipt_no, ':id' => $sale_id]);

    $insert_item = $pdo->prepare("
        INSERT INTO offline_pos_sale_items
            (sale_id, product_id, product_code, product_name, size, quantity, unit_price, line_total)
        VALUES
            (:sale, :product, :code, :name, :size, :quantity, :price, :line_total)
    ");
    foreach ($items as $item) {
        $update_stock->execute([
            ':quantity' => $item['quantity'],
            ':id' => $item['product_id'],
            ':minimum' => $item['quantity'],
        ]);
        if ($update_stock->rowCount() !== 1) {
            throw new InvalidArgumentException('Stock changed during checkout. Refresh the bill and try again.');
        }
        $insert_item->execute([
            ':sale' => $sale_id,
            ':product' => $item['product_id'],
            ':code' => $item['product_code'],
            ':name' => $item['product_name'],
            ':size' => $item['size'],
            ':quantity' => $item['quantity'],
            ':price' => $item['unit_price'],
            ':line_total' => $item['line_total'],
        ]);
    }

    $pdo->commit();
    header('Location: receipt.php?id=' . $sale_id . '&print=1');
    exit;
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    pos_flash('error', $exception->getMessage());
    header('Location: sale.php');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Offline POS sale failed: ' . $exception->getMessage());
    pos_flash('error', 'The bill could not be completed. No stock was deducted; please try again.');
    header('Location: sale.php');
    exit;
}
