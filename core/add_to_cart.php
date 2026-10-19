<?php
require_once('../auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = $_SESSION['user_id'];
    $product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $product_code = isset($_POST['product_code']) ? trim($_POST['product_code']) : '';
    $size         = isset($_POST['size']) ? trim($_POST['size']) : null;
    $quantity     = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0 || $quantity > 6) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity. Quantity must be between 1 and 6.']);
        exit;
    }

    try {
        // একই product_code কার্টে ইতিমধ্যে আছে কি না চেক করা
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = :user_id AND product_code = :product_code");
        $stmt->execute([':user_id' => $user_id, ':product_code' => $product_code]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode(['status' => 'info', 'message' => 'This product is already in your cart. Please update quantity directly from cart options.']);
            exit;
        }

        // না থাকলে নতুন করে ইনসার্ট করা
        $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, product_code, size, quantity, created_at) VALUES (:user_id, :product_id, :product_code, :size, :quantity, NOW())");
        $insert_stmt->execute([
            ':user_id'      => $user_id,
            ':product_id'   => $product_id,
            ':product_code' => $product_code,
            ':size'         => $size,
            ':quantity'     => $quantity
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Product added to cart successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}