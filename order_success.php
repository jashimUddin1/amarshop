<?php
require_once('auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$order_number = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

if (empty($order_number)) {
    header('Location: index.php');
    exit;
}

// Fetch order info
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id");
$stmt->execute([':order_number' => $order_number, ':user_id' => $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm text-center p-4 p-md-5">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill display-1"></i>
                </div>
                
                <h2 class="fw-bold text-dark">Thank You For Your Order!</h2>
                <p class="text-muted">Your order has been placed successfully and is currently processing.</p>

                <div class="bg-light rounded p-3 my-3 text-start border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order ID:</span>
                        <span class="fw-bold text-success"><?= htmlspecialchars($order['order_number']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Method:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Deliver To:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($order['fullname']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Amount:</span>
                        <span class="fw-bold text-dark">৳<?= number_format((float)$order['grand_total'], 0); ?></span>
                    </div>
                </div>

                <p class="small text-muted mb-4">We will call you on <strong><?= htmlspecialchars($order['phone']); ?></strong> to confirm delivery details.</p>

                <div class="d-grid gap-2">
                    <a href="user/order_details.php?id=<?= (int)$order['id']; ?>" class="btn btn-outline-success fw-semibold py-2">Track This Order</a>
                    <a href="index.php" class="btn btn-success fw-semibold py-2">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>