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