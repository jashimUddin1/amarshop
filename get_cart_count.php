<?php
require_once('auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'success', 'cart_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // ইউজারের কার্টে থাকা মোট আইটেমের সংখ্যা (রিসেন্ট রো) কাউন্ট করা
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $cart_count = $result['total'] ? (int)$result['total'] : 0;

    echo json_encode(['status' => 'success', 'cart_count' => $cart_count]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'cart_count' => 0]);
}