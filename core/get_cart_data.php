<?php
require_once('../auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['items' => [], 'subtotal' => '0']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.size as selected_size, c.quantity, c.product_code,
           p.name, p.main_img, p.size as available_sizes,
           ROUND(COALESCE(p.actual_price, p.sell_price)) as price
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id 
    ORDER BY c.id DESC
");
$stmt->execute([':user_id' => $user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += ((int)$item['price'] * (int)$item['quantity']);
}

echo json_encode([
    'items'    => $items,
    'subtotal' => number_format($subtotal, 0)
]);