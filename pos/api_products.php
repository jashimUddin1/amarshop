<?php
require_once __DIR__ . '/common.php';
pos_require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (strlen($query) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Search text is too long.']);
    exit;
}

if ($query === '') {
    echo json_encode(['products' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, product_code, name, size, stock,
            COALESCE(actual_price, sell_price) AS price
        FROM products
        WHERE deleted_at IS NULL
            AND (product_code LIKE :code_query OR name LIKE :name_query)
        ORDER BY CASE WHEN product_code = :exact_code THEN 0 ELSE 1 END,
            name ASC, id ASC
        LIMIT 30
    ");
    $stmt->execute([
        ':code_query' => '%' . $query . '%',
        ':name_query' => '%' . $query . '%',
        ':exact_code' => $query,
    ]);
    $products = array_map(static function (array $product): array {
        return [
            'id' => (int)$product['id'],
            'product_code' => (string)$product['product_code'],
            'name' => (string)$product['name'],
            'size' => (string)($product['size'] ?? ''),
            'stock' => (int)$product['stock'],
            'price' => (float)$product['price'],
        ];
    }, $stmt->fetchAll());
    echo json_encode(['products' => $products], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('Offline POS product search failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Products could not be loaded. Please try again.']);
}
