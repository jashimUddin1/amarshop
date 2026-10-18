<?php
require_once('auth/config.php');

$search_query = trim($_GET['search'] ?? '');
$selected_category = $_GET['category'] ?? '';
$selected_collection = $_GET['collection'] ?? '';

// স্টক ০ সহ সব প্রোডাক্ট ফেচ করার জন্য WHERE 1=1 দেওয়া হয়েছে
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($search_query !== '') {
    $sql .= " AND (name LIKE :search OR product_code LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if ($selected_category !== '') {
    $sql .= " AND category_name = :category";
    $params[':category'] = $selected_category;
}

if ($selected_collection !== '') {
    $sql .= " AND collection_name = :collection";
    $params[':collection'] = $selected_collection;
}

$sql .= " ORDER BY id DESC LIMIT 12";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_SESSION['user_id']) && !empty($products)) {
        $cart_stmt = $pdo->prepare("SELECT product_id, size, quantity FROM cart WHERE user_id = :user_id");
        $cart_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $cart_items = [];
        while ($cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
            $cart_items[(int)$cart_item['product_id']] = $cart_item;
        }

        foreach ($products as &$product) {
            $cart_item = $cart_items[(int)$product['id']] ?? null;
            $product['cart_size'] = $cart_item['size'] ?? null;
            $product['cart_quantity'] = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : null;
        }
        unset($product);
    }
    
    echo json_encode([
        'status' => 'success',
        'products' => $products
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'products' => []
    ]);
}