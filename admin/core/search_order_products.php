<?php
require_once '../auth/config.php';
require_admin();

header('Content-Type: application/json');
$search = $_GET['q'] ?? '';
if (!is_string($search)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid search query.']);
    exit;
}
$search = trim($search);
if (mb_strlen($search) < 2) {
    echo json_encode(['status' => 'success', 'products' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, product_code, name, size, main_img,
               ROUND(COALESCE(actual_price, sell_price)) AS price, stock
        FROM products
        WHERE stock > 0 AND (name LIKE :name_search OR product_code LIKE :code_search)
        ORDER BY name
        LIMIT 15
    ");
    $term = '%' . $search . '%';
    $stmt->execute([':name_search' => $term, ':code_search' => $term]);
    echo json_encode(['status' => 'success', 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('Order product search failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Product search failed. Please try again.']);
}
