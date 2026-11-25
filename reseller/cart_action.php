<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new InvalidArgumentException('Use the cart form to add products.');
    }

    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($csrf_token, $token)) {
        http_response_code(403);
        throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
    }

    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $raw_price = filter_var($_POST['selling_price'] ?? null, FILTER_VALIDATE_FLOAT);
    $size = is_string($_POST['size'] ?? null) ? trim($_POST['size']) : '';
    if ($product_id === false || $product_id === null || $product_id <= 0 ||
        $quantity === false || $quantity === null || $quantity < 1 || $quantity > 1000 ||
        $raw_price === false || $raw_price < 0 || $raw_price > 99999999) {
        throw new InvalidArgumentException('Enter a valid product, quantity, and selling price.');
    }

    $stmt = $pdo->prepare("
        SELECT id, name, size, stock, reseller_price
        FROM products
        WHERE id = :id AND deleted_at IS NULL AND stock > 0 AND reseller_price IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new InvalidArgumentException('This product is no longer available.');
    }

    $available_sizes = array_values(array_filter(array_map('trim', explode(',', (string)($product['size'] ?? ''))), static function ($value) {
        return $value !== '';
    }));
    if (($available_sizes && !in_array($size, $available_sizes, true)) || (!$available_sizes && $size !== '')) {
        throw new InvalidArgumentException('Choose a valid product size.');
    }

    $selling_price = (int)round((float)$raw_price);
    $minimum_price = (int)ceil((float)$product['reseller_price']);
    if ($selling_price < $minimum_price) {
        throw new InvalidArgumentException('Selling price must be at least ' . reseller_money($product['reseller_price']) . '.');
    }

    $cart = $_SESSION['reseller_cart'] ?? [];
    $existing_total = 0;
    $cart_line_count = 0;
    foreach (($cart[$product_id] ?? []) as $cart_item) {
        if (is_array($cart_item)) {
            $existing_total += max(0, (int)($cart_item['quantity'] ?? 0));
        }
    }
    foreach ($cart as $variants) {
        if (is_array($variants)) {
            foreach ($variants as $cart_item) {
                if (is_array($cart_item)) {
                    $cart_line_count++;
                }
            }
        }
    }
    if (!isset($cart[$product_id][$size]) && $cart_line_count >= 100) {
        throw new InvalidArgumentException('A reseller order can contain up to 100 product sizes.');
    }
    $stock = max(0, (int)$product['stock']);
    if ($existing_total + $quantity > $stock) {
        throw new InvalidArgumentException('Only ' . max(0, $stock - $existing_total) . ' more of this product can be added.');
    }

    $existing_quantity = max(0, (int)($cart[$product_id][$size]['quantity'] ?? 0));
    $_SESSION['reseller_cart'][$product_id][$size] = [
        'quantity' => $existing_quantity + $quantity,
        'selling_price' => $selling_price,
    ];

    echo json_encode(['success' => true, 'cart_count' => reseller_cart_count()]);
} catch (InvalidArgumentException $e) {
    if (http_response_code() < 400) {
        http_response_code(422);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Reseller cart update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'The cart could not be updated. Please try again.']);
}
