<?php
require_once __DIR__ . '/includes/bootstrap.php';

$stats_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount), 0) AS lifetime,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN commission_amount ELSE 0 END), 0) AS today,
        COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN commission_amount ELSE 0 END), 0) AS month
    FROM reseller_commissions WHERE reseller_id = :reseller_id
");
$stats_stmt->execute([':reseller_id' => $reseller_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("
    SELECT c.order_amount, c.commission_amount, c.created_at, o.order_number
    FROM reseller_commissions c JOIN orders o ON o.id = c.order_id
    WHERE c.reseller_id = :reseller_id
    ORDER BY c.id DESC LIMIT 100
");
$stmt->execute([':reseller_id' => $reseller_id]);
$commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Commission';
$current_page = 'commission';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 fw-bold mb-4">Commission</h1>
<div class="row g-3 mb-4">
    <?php foreach (['Today' => $stats['today'], 'This month' => $stats['month'], 'Lifetime' => $stats['lifetime']] as $label => $amount): ?>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted"><?= reseller_h($label); ?></div><div class="h4 fw-bold mb-0"><?= reseller_money($amount); ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Delivered order commissions</div><div class="table-responsive"><table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Order</th><th>Order amount</th><th>Commission</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($commissions as $commission): ?><tr>
        <td>#<?= reseller_h($commission['order_number']); ?></td><td><?= reseller_money($commission['order_amount']); ?></td>
        <td class="fw-semibold text-success"><?= reseller_money($commission['commission_amount']); ?></td>
        <td><?= date('d M Y, h:i A', strtotime($commission['created_at'])); ?></td>
    </tr><?php endforeach; ?>
    <?php if (!$commissions): ?><tr><td colspan="4" class="text-center text-muted py-4">Commission will appear after a reseller-attributed order is delivered.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
