<?php
require_once('../auth/config.php');
require_once __DIR__ . '/../core/order_status.php';
require_once __DIR__ . '/../core/user_profile_picture.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
ensure_user_profile_pictures_table($pdo);

$user_stmt = $pdo->prepare('SELECT customar_name, phone FROM users WHERE id = :user_id');
$user_stmt->execute([':user_id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('User profile not found.');
}
$user['profile_picture'] = get_user_profile_picture($pdo, (int)$user_id);

$stmt = $pdo->prepare("SELECT id, order_number, grand_total, payment_method, order_status, created_at FROM orders WHERE user_id = :user_id ORDER BY id DESC");
$stmt->execute([':user_id' => $user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nav_items = [
    ['label' => 'My Profile', 'icon' => 'person-vcard', 'href' => 'profile.php'],
    ['label' => 'My Orders', 'icon' => 'box-seam', 'href' => 'my_orders.php'],
    ['label' => 'Transaction History', 'icon' => 'receipt', 'href' => 'transactions.php', 'active' => true],
    ['label' => 'Delivery Address', 'icon' => 'geo-alt', 'href' => 'addresses.php'],
];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .account-nav .nav-link { color: #344054; border: 1px solid #e5e7eb; border-radius: .65rem; margin-bottom: .45rem; }
        .account-nav .nav-link.active { color: #087f79; background: #eff8f7; border-color: #e5efee; }
        .account-nav .nav-link:hover { background: #f6f8fa; }
    </style>
</head>
<body class="bg-light py-3 py-md-4">

<main class="container">
    <div class="d-flex align-items-center gap-2 text-muted small mb-3">
        <a href="../index.php" class="link-secondary text-decoration-none">Home</a>
        <i class="bi bi-chevron-right"></i><span>Transaction History</span>
    </div>
    <div class="row g-3 g-lg-4">
        <aside class="col-12 col-lg-3">
            <div class="card border shadow-sm mb-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <?php if ($user['profile_picture']): ?>
                        <img src="../uploads/<?= htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="rounded-circle border object-fit-cover flex-shrink-0" style="width:64px;height:64px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" style="width:64px;height:64px;">
                            <?= htmlspecialchars(strtoupper(mb_substr((string)$user['customar_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-width-0">
                        <div class="fw-bold text-truncate"><?= htmlspecialchars($user['customar_name'], ENT_QUOTES, 'UTF-8'); ?></div>
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
        <section class="col-12 col-lg-9">
            <h1 class="h4 fw-bold mb-3"><i class="bi bi-receipt text-info me-2"></i>Transaction History</h1>
            <?php if (empty($transactions)): ?>
                <div class="card border shadow-sm">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-credit-card-2-front fs-1 text-muted"></i>
                        <h2 class="h5 mt-3 text-secondary">No transaction records found</h2>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $tx): ?>
                                        <tr>
                                            <td class="fw-semibold"><a class="text-dark" href="order_details.php?id=<?= (int)$tx['id']; ?>"><?= htmlspecialchars($tx['order_number'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                            <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($tx['created_at'])); ?></small></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tx['payment_method'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td class="fw-bold text-success">৳<?= number_format((float)$tx['grand_total'], 0); ?></td>
                                            <td>
                                                <?php if (normalize_order_status((string)$tx['order_status']) === 'delivered'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success">Paid</span>
                                                <?php elseif (normalize_order_status((string)$tx['order_status']) === 'cancelled'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger">Failed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning">Pending COD</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

</body>
</html>