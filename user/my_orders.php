<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_once __DIR__ . '/../core/user_profile_picture.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
ensure_order_status_history_table($pdo);
ensure_user_profile_pictures_table($pdo);

$user_stmt = $pdo->prepare('SELECT customar_name, phone FROM users WHERE id = :user_id');
$user_stmt->execute([':user_id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC) ?: ['customar_name' => 'User', 'phone' => ''];
$user['profile_picture'] = get_user_profile_picture($pdo, $user_id);

$nav_items = [
    ['label' => 'My Profile', 'icon' => 'person-vcard', 'href' => 'profile.php'],
    ['label' => 'My Orders', 'icon' => 'box-seam', 'href' => 'my_orders.php', 'active' => true],
    ['label' => 'Transaction History', 'icon' => 'receipt', 'href' => 'transactions.php'],
    ['label' => 'Delivery Address', 'icon' => 'geo-alt', 'href' => 'addresses.php'],
];

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .account-nav .nav-link { color: #344054; border: 1px solid #e5e7eb; border-radius: .65rem; margin-bottom: .45rem; }
        .account-nav .nav-link.active { color: #087f79; background: #eff8f7; border-color: #e5efee; }
        .account-nav .nav-link:hover { background: #f6f8fa; }
        .nav-pills .nav-link { border-radius: .5rem; font-size: .88rem; font-weight: 500; }
        .nav-pills .nav-link.active { background-color: #087f79; color: #fff; }
        .order-card { border-color: #e5e7eb; }
        .btn-outline-teal {
            color: #087f79;
            border: 1px solid #087f79;
            background-color: transparent;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .btn-outline-teal:hover {
            color: #ffffff;
            background-color: #087f79;
            border-color: #087f79;
        }
    </style>
</head>
<body class="bg-light py-3 py-md-4">

<main class="container">
    <div class="d-flex align-items-center gap-2 text-muted small mb-3">
        <a href="../index.php" class="link-secondary text-decoration-none">Home</a>
        <i class="bi bi-chevron-right"></i><span>My Orders</span>
    </div>

    <div class="row g-3 g-lg-4">
        <!-- Sidebar Navigation -->
        <aside class="col-12 col-lg-3">
            <div class="card border shadow-sm mb-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="rounded-circle border object-fit-cover flex-shrink-0" style="width:64px;height:64px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" style="width:64px;height:64px;">
                            <?= htmlspecialchars(strtoupper(mb_substr((string)($user['customar_name'] ?? 'U'), 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-width-0">
                        <div class="fw-bold text-truncate"><?= htmlspecialchars($user['customar_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
            <nav class="account-nav d-grid" aria-label="Account navigation">
                <?php foreach ($nav_items as $item): ?>
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="nav-link px-3 py-2 <?= !empty($item['active']) ? 'active' : ''; ?>">
                        <i class="bi bi-<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        <i class="bi bi-chevron-right float-end mt-1 small"></i>
                    </a>
                <?php endforeach; ?>
                <a href="../auth/logout.php" class="nav-link px-3 py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </nav>
        </aside>

        <!-- Main Orders List -->
        <section class="col-12 col-lg-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h1 class="h4 fw-bold mb-0"><i class="bi bi-box-seam text-success me-2"></i>My Orders</h1>
                <a href="../index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart me-1"></i>Continue Shopping</a>
            </div>

            <!-- Filter Status Pills -->
            <nav class="mb-3">
                <div class="nav nav-pills gap-2 pb-1 flex-nowrap overflow-x-auto" aria-label="Filter orders by status">
                    <?php foreach (['all' => 'All', 'processing' => 'Processing', 'confirmed' => 'Confirmed', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $filter => $label): ?>
                        <a class="nav-link text-nowrap <?= $status_filter === $filter ? 'active' : 'text-dark border bg-white'; ?>"
                            href="my_orders.php?status=<?= urlencode($filter); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <?php if (empty($orders)): ?>
                <div class="card border shadow-sm p-5 text-center">
                    <i class="bi bi-bag-x fs-1 text-muted"></i>
                    <h5 class="mt-3 text-secondary">No orders found.</h5>
                    <a href="../index.php" class="btn btn-success align-self-center mt-2">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($orders as $order): 
                        $order_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
                        $status_badge_class = match ($order_status) {
                            'delivered'  => 'bg-success-subtle text-success border border-success-subtle',
                            'delivering' => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'packed', 'picked' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                            'confirmed'  => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'cancelled'  => 'bg-danger-subtle text-danger border border-danger-subtle',
                            default      => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                        };

                        $is_delivered = ($order_status === 'delivered');
                        $is_cod = strtoupper((string)($order['payment_method'] ?? 'COD')) === 'COD';
                        $payment_status = $is_delivered ? 'Paid' : ($is_cod ? 'COD' : 'Paid');
                        $payment_class = $is_delivered ? 'text-success' : ($is_cod ? 'text-muted' : 'text-success');

                        $delivery_label = !empty($order['shipping_area']) && !is_numeric($order['shipping_area'])
                            ? htmlspecialchars((string)$order['shipping_area'], ENT_QUOTES, 'UTF-8')
                            : 'Regular Delivery';
                    ?>
                        <div class="card border rounded-3 shadow-sm bg-white order-card">
                            <!-- Top Row: Order ID & Delivery Pill -->
                            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-secondary fw-normal">Order ID:</span>
                                    <span class="fw-bold text-dark">#<?= htmlspecialchars($order['order_number'] ?? (string)$order['id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <button type="button" class="btn btn-link text-muted p-0 ms-1 copy-order-btn" data-order-number="<?= htmlspecialchars($order['order_number'] ?? (string)$order['id'], ENT_QUOTES, 'UTF-8'); ?>" title="Copy Order ID" style="line-height:1;text-decoration:none;">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                                <div>
                                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-2 d-inline-flex align-items-center gap-1" style="font-weight: 500; font-size: 0.82rem;">
                                        <i class="bi bi-bicycle text-success"></i> <?= $delivery_label; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Middle Row: Customer Address & Date / Amount -->
                            <div class="p-3 border-bottom">
                                <div class="row align-items-center g-3">
                                    <div class="col-12 col-md-7 d-flex gap-3 align-items-start">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; border: 1px solid #e5e7eb; color: #64748b;">
                                            <i class="bi bi-house-door fs-5"></i>
                                        </div>
                                        <div class="min-width-0">
                                            <div class="fw-semibold text-secondary mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($order['fullname'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-muted small mb-1"><?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-muted small text-break"><?= htmlspecialchars($order['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5 text-md-end">
                                        <div class="text-dark small mb-2">
                                            Date: <?= !empty($order['created_at']) ? date('M d, Y, h:i A', strtotime($order['created_at'])) : 'N/A'; ?>
                                        </div>
                                        <div class="fs-6 fw-bold text-dark">
                                            ৳<?= number_format((float)($order['grand_total'] ?? 0), 0); ?>
                                            <span class="<?= $payment_class; ?> fw-normal small">(<?= $payment_status; ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Row: Status Badge & View Button -->
                            <div class="px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-secondary small fw-medium">Status:</span>
                                    <span class="badge <?= $status_badge_class; ?> px-2 py-1 fw-medium" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars(order_status_label($order_status), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div>
                                    <a href="order_details.php?id=<?= (int)$order['id']; ?>" class="btn btn-outline-teal btn-sm px-4 py-1 fw-medium">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
document.querySelectorAll('.copy-order-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const orderNum = btn.dataset.orderNumber;
        if (!orderNum) return;
        const copySuccess = () => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-check-lg text-success';
                setTimeout(() => { icon.className = 'bi bi-copy'; }, 1500);
            }
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(orderNum).then(copySuccess).catch(() => {
                fallbackCopy(orderNum, copySuccess);
            });
        } else {
            fallbackCopy(orderNum, copySuccess);
        }
    });
});

function fallbackCopy(text, callback) {
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    try {
        document.execCommand('copy');
        callback();
    } catch (e) {
        console.error('Copy failed', e);
    }
    document.body.removeChild(input);
}
</script>

</body>
</html>
