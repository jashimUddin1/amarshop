<?php // add_to_cart.php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $size       = isset($_POST['size']) ? trim($_POST['size']) : '';
    $quantity   = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity']);
        exit;
    }

    // Session Cart Array তৈরি (যদি না থাকে)
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Cart Key unique করার জন্য (প্রোডাক্ট আইডি + সাইজ মিলিয়ে unique key)
    $cart_key = $product_id . ($size ? '-' . $size : '');

    if (isset($_SESSION['cart'][$cart_key])) {
        // প্রোডাক্ট আগের থেকেই থাকলে শুধু Quantity যোগ হবে (সর্বোচ্চ ৬ পর্যন্ত)
        $_SESSION['cart'][$cart_key]['quantity'] = min(6, $_SESSION['cart'][$cart_key]['quantity'] + $quantity);
    } else {
        // নতুন প্রোডাক্ট যোগ করা
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'size'       => $size,
            'quantity'   => $quantity
        ];
    }

    // কার্টে মোট আইটেম সংখ্যা হিসাব করা
    $total_items = array_sum(array_column($_SESSION['cart'], 'quantity'));

    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to cart!',
        'cart_count' => $total_items
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);