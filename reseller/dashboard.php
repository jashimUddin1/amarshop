<?php
require_once __DIR__ . '/includes/bootstrap.php';

$stats_stmt = $pdo->prepare("
    SELECT COUNT(*) AS order_count,
        SUM(CASE WHEN LOWER(order_status) IN ('delivered', 'completed') THEN 1 ELSE 0 END) AS delivered_count,
        COALESCE(SUM(CASE WHEN LOWER(order_status) IN ('delivered', 'completed') THEN grand_total ELSE 0 END), 0) AS delivered_sales,
        COALESCE(SUM(CASE WHEN LOWER(order_status) IN ('delivered', 'completed') AND DATE(created_at) = CURDATE() THEN grand_total ELSE 0 END), 0) AS today_sales,
        COALESCE(SUM(CASE WHEN LOWER(order_status) IN ('delivered', 'completed') AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN grand_total ELSE 0 END), 0) AS month_sales
    FROM orders WHERE reseller_id = :reseller_id
");
$stats_stmt->execute([':reseller_id' => $reseller_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$commission_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount), 0) AS lifetime,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN commission_amount ELSE 0 END), 0) AS today,
        COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN commission_amount ELSE 0 END), 0) AS month
    FROM reseller_commissions WHERE reseller_id = :reseller_id
");
$commission_stmt->execute([':reseller_id' => $reseller_id]);
$commission_stats = $commission_stmt->fetch(PDO::FETCH_ASSOC);
$average_commission = (int)$stats['delivered_count'] > 0
    ? (float)$commission_stats['lifetime'] / (int)$stats['delivered_count']
    : 0;
$recent_stmt = $pdo->prepare("
    SELECT o.order_number, o.fullname, o.grand_total, o.order_status, o.created_at, c.commission_amount
    FROM orders o
    LEFT JOIN reseller_commissions c ON c.order_id = o.id
    WHERE o.reseller_id = :reseller_id
    ORDER BY o.id DESC LIMIT 6
");
$recent_stmt->execute([':reseller_id' => $reseller_id]);
$recent_sales = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
$referral_url = BASE_URL . '/index.php?ref=' . rawurlencode((string)$reseller['referral_code']);

$page_title = 'Dashboard';
$current_page = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 fw-bold mb-1">Dashboard</h1><p class="text-muted mb-0">Your reseller sales and commission overview.</p></div>
    <a class="btn btn-success" href="home.php"><i class="bi bi-shop me-1"></i>Browse products</a>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Delivered orders', number_format((int)$stats['delivered_count']), 'box-seam'],
        ['Sales today', reseller_money($stats['today_sales']), 'sun'],
        ['Commission today', reseller_money($commission_stats['today']), 'cash-coin'],
        ['Commission this month', reseller_money($commission_stats['month']), 'calendar-month'],
        ['Lifetime commission', reseller_money($commission_stats['lifetime']), 'cash-stack'],
        ['Average commission / order', reseller_money($average_commission), 'graph-up'],
        ['Sales this month', reseller_money($stats['month_sales']), 'cart-check'],
        ['Lifetime sales', reseller_money($stats['delivered_sales']), 'graph-up-arrow'],
    ] as [$label, $value, $icon]): ?>
        <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="rounded-circle bg-success-subtle text-success p-3"><i class="bi bi-<?= reseller_h($icon); ?> fs-4"></i></span>
            <div><div class="small text-muted"><?= reseller_h($label); ?></div><div class="h5 fw-bold mb-0"><?= reseller_h($value); ?></div></div>
        </div></div></div>
    <?php endforeach; ?>
</div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5 fw-bold">Your referral link</h2>
    <p class="text-muted small">Share this link. Customer orders made through it will be attributed to your reseller account.</p>
    <div class="input-group"><input class="form-control" id="referralLink" readonly value="<?= reseller_h($referral_url); ?>"><button class="btn btn-outline-success" type="button" id="copyReferral"><i class="bi bi-copy me-1"></i>Copy</button></div>
</div></div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Recent sales</div><div class="table-responsive"><table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Date</th><th>Status</th><th>Commission</th></tr></thead><tbody>
    <?php foreach ($recent_sales as $sale): ?><tr>
        <td>#<?= reseller_h($sale['order_number']); ?></td><td><?= reseller_h($sale['fullname']); ?></td>
        <td><?= date('d M Y', strtotime($sale['created_at'])); ?></td>
        <td><span class="badge text-bg-secondary"><?= reseller_h(ucfirst((string)$sale['order_status'])); ?></span></td>
        <td><?= $sale['commission_amount'] === null ? 'After delivery' : reseller_money($sale['commission_amount']); ?></td>
    </tr><?php endforeach; ?>
    <?php if (!$recent_sales): ?><tr><td colspan="5" class="text-center text-muted py-4">No sales yet.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<script>
document.getElementById('copyReferral')?.addEventListener('click', async event => {
    const input = document.getElementById('referralLink');
    const button = event.currentTarget;
    try {
        await navigator.clipboard.writeText(input.value);
        button.textContent = 'Copied';
    } catch (error) {
        input.select();
        document.execCommand('copy');
        button.textContent = 'Copied';
    }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
