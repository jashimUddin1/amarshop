<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ইউজারের সব অর্ডার ফেচ
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC");
$stmt->execute([':user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-box-seam text-warning me-2"></i>My Orders</h4>
        <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3">Home</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <h5 class="mt-3 text-secondary">No orders found yet!</h5>
            <a href="../index.php" class="btn btn-success align-self-center mt-2">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): 
                // অর্ডারের ভেতরের আইটেমগুলো ফেচ
                $item_stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.main_img 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = :order_id
                ");
                $item_stmt->execute([':order_id' => $order['id']]);
                $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Status Badge Color
                $status_color = 'bg-warning text-dark';
                if ($order['order_status'] === 'Delivered') $status_color = 'bg-success';
                if ($order['order_status'] === 'Cancelled') $status_color = 'bg-danger';
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <div>
                                <span class="fw-bold text-dark">Order ID: <?= htmlspecialchars($order['order_number']); ?></span>
                                <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $status_color; ?> px-3 py-2 mb-1"><?= htmlspecialchars($order['order_status']); ?></span>
                                <h6 class="fw-bold text-success mb-0">Total: ৳<?= number_format($order['grand_total'], 2); ?></h6>
                            </div>
                        </div>

                        <!-- Product Items List -->
                        <div class="row g-2">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="d-flex align-items-center p-2 border rounded bg-white">
                                        <img src="../uploads/<?= $item['main_img']; ?>" class="rounded border me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 text-truncate fs-6"><?= htmlspecialchars($item['name']); ?></h6>
                                            <small class="text-muted">Size: <?= htmlspecialchars($item['size'] ?: 'N/A'); ?> | Qty: <?= $item['quantity']; ?></small>
                                            <div class="fw-bold text-dark" style="font-size: 0.8rem;">৳<?= number_format($item['total_price'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>