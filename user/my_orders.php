<?php
require_once('../auth/config.php');
require_once __DIR__ . '/../core/order_status.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
ensure_order_status_history_table($pdo);

$status_filter = $_GET['status'] ?? 'all';
$status_filter = is_string($status_filter) ? strtolower(trim($status_filter)) : 'all';
$allowed_filters = ['all', 'processing', 'confirmed', 'delivered', 'cancelled'];
if (!in_array($status_filter, $allowed_filters, true)) {
    $status_filter = 'all';
}

$query = 'SELECT * FROM orders WHERE user_id = :user_id';
$params = [':user_id' => $user_id];
if ($status_filter === 'processing') {
    $query .= " AND LOWER(order_status) IN ('processing', 'pending')";
} elseif ($status_filter === 'delivered') {
    $query .= " AND LOWER(order_status) IN ('delivered', 'completed')";
} elseif ($status_filter === 'cancelled') {
    $query .= " AND LOWER(order_status) IN ('cancelled', 'canceled')";
} elseif ($status_filter === 'confirmed') {
    $query .= " AND LOWER(order_status) = 'confirmed'";
}
$query .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
<body class="bg-light py-3 py-md-5">

<div class="container">
    <div class="d-flex align-items-center gap-2 text-muted mb-3">
        <a href="../index.php" class="link-secondary text-decoration-none">Home</a>
        <i class="bi bi-chevron-right small"></i>
        <span class="text-dark fw-semibold">Orders</span>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-box-seam text-success me-2"></i>My Orders</h4>
        <a href="../index.php" class="btn btn-outline-secondary btn-sm">Continue Shopping</a>
    </div>

    <nav class="mb-4 border-bottom">
        <div class="nav nav-pills gap-2 pb-2" aria-label="Filter orders by status">
            <?php foreach (['all' => 'All', 'processing' => 'Processing', 'confirmed' => 'Confirmed', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $filter => $label): ?>
                <a class="nav-link <?= $status_filter === $filter ? 'active' : 'text-dark'; ?>"
                    href="my_orders.php?status=<?= urlencode($filter); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </div>
    </nav>

    <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <h5 class="mt-3 text-secondary">No orders found.</h5>
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

                $order_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
                $status_color = match ($order_status) {
                    'delivered' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    'confirmed' => 'bg-primary',
                    default => 'bg-warning text-dark',
                };
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <div>
                                <a class="fw-bold text-dark text-decoration-none" href="order_details.php?id=<?= (int)$order['id']; ?>">Order ID: <?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></a>
                                <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $status_color; ?> px-3 py-2 mb-1"><?= htmlspecialchars(order_status_label($order_status), ENT_QUOTES, 'UTF-8'); ?></span>
                                <h6 class="fw-bold text-success mb-0">Total: ৳<?= number_format((float)$order['grand_total'], 0); ?></h6>
                            </div>
                        </div>

                        <!-- Product Items List -->
                        <div class="row g-2">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="d-flex align-items-center p-2 border rounded bg-white">
                                        <?php if (!empty($item['main_img'])): ?>
                                            <img src="../uploads/<?= rawurlencode(basename((string)$item['main_img'])); ?>" alt="" class="rounded border me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                        <?php endif; ?>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 text-truncate fs-6"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <small class="text-muted">Size: <?= htmlspecialchars($item['size'] ?: 'N/A'); ?> | Qty: <?= $item['quantity']; ?></small>
                                            <div class="fw-bold text-dark" style="font-size: 0.8rem;">৳<?= number_format((float)$item['total_price'], 0); ?></div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-3">
                                            <a href="order_details.php?id=<?= (int)$order['id']; ?>" class="btn btn-outline-success btn-sm">
                                                Track order <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
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