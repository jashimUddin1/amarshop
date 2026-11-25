<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);
$deliveryman_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0) {
    http_response_code(400);
    exit('Invalid deliveryman ID.');
}
$rider_stmt = $pdo->prepare('SELECT id, customar_name, phone, email, status, created_at FROM deliveryman WHERE id = :id');
$rider_stmt->execute([':id' => $deliveryman_id]);
$rider = $rider_stmt->fetch(PDO::FETCH_ASSOC);
if (!$rider) {
    http_response_code(404);
    exit('Deliveryman not found.');
}
$bags_stmt = $pdo->prepare("
    SELECT b.*, COUNT(q.id) AS shipment_count,
        COALESCE(SUM(CASE WHEN q.status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_count,
        COALESCE(SUM(q.cash_received), 0) AS cash_collected
    FROM delivery_bags b LEFT JOIN order_quantity q ON q.bag_id = b.id
    WHERE b.deliveryman_id = :id
    GROUP BY b.id ORDER BY b.created_at DESC
");
$bags_stmt->execute([':id' => $deliveryman_id]);
$bags = $bags_stmt->fetchAll(PDO::FETCH_ASSOC);
$cash_stmt = $pdo->prepare("
    SELECT c.*, b.bag_name, b.bag_code
    FROM delivery_bag_cash_collections c JOIN delivery_bags b ON b.id = c.bag_id
    WHERE c.deliveryman_id = :id ORDER BY c.id DESC
");
$cash_stmt->execute([':id' => $deliveryman_id]);
$collections = $cash_stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Deliveryman Activity';
$current_page = 'deliverymen';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center px-2 py-3"><div><h1 class="h4 fw-bold mb-1"><?= htmlspecialchars($rider['customar_name'], ENT_QUOTES, 'UTF-8'); ?></h1><div class="text-muted"><?= htmlspecialchars($rider['phone'] . (!empty($rider['email']) ? ' · ' . $rider['email'] : ''), ENT_QUOTES, 'UTF-8'); ?></div></div><a class="btn btn-outline-secondary" href="deliverymen.php">Back to deliverymen</a></div>
<div class="overflow-y-auto px-2 pb-3">
    <div class="card shadow-sm mb-3"><div class="card-header fw-semibold">Bags and deliveries</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Bag</th><th>Zone</th><th>Status</th><th>Shipments</th><th>Delivered</th><th>COD collected</th><th></th></tr></thead><tbody>
        <?php foreach ($bags as $bag): ?><tr><td>#<?= str_pad((string)$bag['id'], 2, '0', STR_PAD_LEFT); ?> · <?= htmlspecialchars($bag['bag_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($bag['zone'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars(ucfirst($bag['status']) . ((int)$bag['return_submitted'] ? ' · submitted' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= (int)$bag['shipment_count']; ?></td><td><?= (int)$bag['delivered_count']; ?></td><td>৳<?= number_format((float)$bag['cash_collected'], 2); ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="bag_details.php?id=<?= (int)$bag['id']; ?>">View activity</a></td></tr><?php endforeach; ?>
        <?php if (!$bags): ?><tr><td colspan="7" class="text-center text-muted py-4">No bags assigned.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    <div class="card shadow-sm"><div class="card-header fw-semibold">Cash collection history</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Bag</th><th>Amount</th><th>Status</th><th>Submitted</th><th>Manager note</th></tr></thead><tbody>
        <?php foreach ($collections as $collection): ?><tr><td><?= htmlspecialchars($collection['bag_name'] . ' · ' . $collection['bag_code'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>৳<?= number_format((float)$collection['submitted_amount'], 2); ?></td><td><?= htmlspecialchars(ucfirst($collection['status']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= !empty($collection['submitted_at']) ? date('d M Y, h:i A', strtotime($collection['submitted_at'])) : ''; ?></td><td><?= htmlspecialchars($collection['manager_note'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
        <?php if (!$collections): ?><tr><td colspan="5" class="text-center text-muted py-4">No cash collection requests.</td></tr><?php endif; ?>
        </tbody></table></div></div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
