<?php
require_once __DIR__ . '/includes/bootstrap.php';

$stmt = $pdo->prepare("
    SELECT o.order_number, o.fullname, o.phone, o.grand_total, o.order_status, o.created_at,
        c.commission_amount
    FROM orders o
    LEFT JOIN reseller_commissions c ON c.order_id = o.id
    WHERE o.reseller_id = :reseller_id
    ORDER BY o.id DESC LIMIT 100
");
$stmt->execute([':reseller_id' => $reseller_id]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Sells';
$current_page = 'sells';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Sells</h1><p class="text-muted mb-0">Direct orders and referral-link sales.</p></div><a class="btn btn-success" href="home.php"><i class="bi bi-plus-lg me-1"></i>New order</a></div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Commission</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($sales as $sale): ?><tr>
        <td>#<?= reseller_h($sale['order_number']); ?></td>
        <td><?= reseller_h($sale['fullname']); ?><br><small class="text-muted"><?= reseller_h($sale['phone']); ?></small></td>
        <td><?= reseller_money($sale['grand_total']); ?></td>
        <td><span class="badge text-bg-secondary"><?= reseller_h(ucfirst((string)$sale['order_status'])); ?></span></td>
        <td><?= $sale['commission_amount'] === null ? 'After delivery' : reseller_money($sale['commission_amount']); ?></td>
        <td><?= date('d M Y, h:i A', strtotime($sale['created_at'])); ?></td>
    </tr><?php endforeach; ?>
    <?php if (!$sales): ?><tr><td colspan="6" class="text-center text-muted py-4">No sales found.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
