<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?status=warning&message=' . urlencode('Please login to place an order.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id         = $_SESSION['user_id'];
    $fullname        = trim($_POST['fullname'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $shipping_area   = $_POST['shipping_area'] ?? '';
    $shipping_rates  = ['inside_dhaka' => 60.00, 'outside_dhaka' => 120.00];
    $payment_method  = $_POST['payment_method'] ?? 'COD';

    if (
        empty($fullname) ||
        empty($phone) ||
        empty($address) ||
        !is_string($shipping_area) ||
        !array_key_exists($shipping_area, $shipping_rates)
    ) {
        header('Location: ../checkout.php?status=error&message=' . urlencode('Please fill in all required fields.'));
        exit;
    }
    $shipping_charge = $shipping_rates[$shipping_area];

    try {
        $pdo->beginTransaction();

        // ১. ইউজার কার্ট ডাটা ফেচ করা
        $cart_stmt = $pdo->prepare("
            SELECT c.product_id, c.product_code, c.size, c.quantity,
                   COALESCE(p.actual_price, p.sell_price) as price, p.stock
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = :user_id
            FOR UPDATE
        ");
        $cart_stmt->execute([':user_id' => $user_id]);
        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            $pdo->rollBack();
            header('Location: ../cart.php?status=error&message=' . urlencode('Your cart is empty!'));
            exit;
        }

        foreach ($cart_items as $item) {
            if ((int)$item['quantity'] <= 0 || (int)$item['stock'] < (int)$item['quantity']) {
                $pdo->rollBack();
                header('Location: ../cart.php?status=error&message=' . urlencode('One or more items are no longer available in the requested quantity.'));
                exit;
            }
        }

        // ২. সাবটোটাল ও গ্র্যান্ড টোটাল হিসাব
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }
        $grand_total = $subtotal + $shipping_charge;

        // ইউনিক অর্ডার নম্বর জেনারেট (Ex: ORD-102938)
        $order_number = 'ORD-' . strtoupper(substr(uniqid(), -6));

        $stock_stmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - :decrement
            WHERE id = :product_id AND stock >= :minimum_stock
        ");
        foreach ($cart_items as $item) {
            $stock_stmt->execute([
                ':decrement' => (int)$item['quantity'],
                ':product_id' => (int)$item['product_id'],
                ':minimum_stock' => (int)$item['quantity']
            ]);
            if ($stock_stmt->rowCount() !== 1) {
                throw new RuntimeException('Product stock changed while placing the order.');
            }
        }

        // ৩. `orders` টেবিলে মূল অর্ডার ডাটা ইনসার্ট
        $order_sql = "INSERT INTO orders (order_number, user_id, fullname, phone, email, address, shipping_area, subtotal, shipping_charge, grand_total, payment_method, created_at) 
                      VALUES (:order_number, :user_id, :fullname, :phone, :email, :address, :shipping_area, :subtotal, :shipping_charge, :grand_total, :payment_method, NOW())";
        
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([
            ':order_number'    => $order_number,
            ':user_id'         => $user_id,
            ':fullname'        => $fullname,
            ':phone'           => $phone,
            ':email'           => $email,
            ':address'         => $address,
            ':shipping_area'   => $shipping_charge,
            ':subtotal'        => $subtotal,
            ':shipping_charge' => $shipping_charge,
            ':grand_total'     => $grand_total,
            ':payment_method'  => $payment_method
        ]);

        $order_id = $pdo->lastInsertId();

        // ৪. `order_items` টেবিলে প্রোডাক্ট ডাটা ইনসার্ট
        $item_sql = "INSERT INTO order_items (order_id, product_id, product_code, size, quantity, price, total_price) 
                     VALUES (:order_id, :product_id, :product_code, :size, :quantity, :price, :total_price)";
        $item_stmt = $pdo->prepare($item_sql);

        foreach ($cart_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $item_stmt->execute([
                ':order_id'     => $order_id,
                ':product_id'   => $item['product_id'],
                ':product_code' => $item['product_code'],
                ':size'         => $item['size'],
                ':quantity'     => $item['quantity'],
                ':price'        => $item['price'],
                ':total_price'  => $item_total
            ]);
        }

        // ৫. কার্ট ক্লিয়ার করে দেওয়া
        $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $clear_cart->execute([':user_id' => $user_id]);

        // Commit DB changes
        $pdo->commit();

        // ৬. Success পেজে রিডাইরেক্ট
        header('Location: ../order_success.php?order_id=' . urlencode($order_number));
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Order placement failed: ' . $e->getMessage());
        header('Location: ../checkout.php?status=error&message=' . urlencode('Your order could not be placed. Please try again.'));
        exit;
    }
}