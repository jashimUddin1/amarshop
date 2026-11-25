<?php
require_once __DIR__ . '/auth.php';
$deliveryman = require_deliveryman();
$item_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($item_id === false || $item_id === null || $item_id <= 0) {
    http_response_code(400);
    exit('Invalid receipt.');
}
$stmt = $pdo->prepare("
    SELECT q.*, b.bag_code, o.order_number
    FROM order_quantity q
    JOIN delivery_bags b ON b.id = q.bag_id
    LEFT JOIN orders o ON o.id = q.order_id
    WHERE q.id = :id AND q.shipment_type = 'order' AND q.status = 'delivered'
        AND q.cash_receipt_no IS NOT NULL AND b.deliveryman_id = :rider
");
$stmt->execute([':id' => $item_id, ':rider' => (int)$deliveryman['id']]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$receipt) {
    http_response_code(404);
    exit('Receipt not found.');
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cash receipt <?= htmlspecialchars($receipt['cash_receipt_no'], ENT_QUOTES, 'UTF-8'); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><article class="card mx-auto shadow-sm" style="max-width:520px"><div class="card-body p-4">
<div class="d-flex justify-content-between align-items-start"><div><h1 class="h4">AmarShop</h1><p class="text-muted mb-0">Cash collection receipt</p></div><strong><?= htmlspecialchars($receipt['cash_receipt_no'], ENT_QUOTES, 'UTF-8'); ?></strong></div><hr>
<p>Shipment: <strong>#<?= htmlspecialchars($receipt['shipment_number'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
<p>Customer: <?= htmlspecialchars($receipt['customer_name'], ENT_QUOTES, 'UTF-8'); ?><br>Phone: <?= htmlspecialchars($receipt['customer_phone'], ENT_QUOTES, 'UTF-8'); ?></p>
<p>Received by: <?= htmlspecialchars($deliveryman['customar_name'], ENT_QUOTES, 'UTF-8'); ?><br>Date: <?= !empty($receipt['delivered_at']) ? date('d M Y, h:i A', strtotime($receipt['delivered_at'])) : ''; ?></p>
<div class="d-flex justify-content-between border-top pt-3 fs-5"><strong>Cash received</strong><strong>৳<?= number_format((float)$receipt['cash_received'], 2); ?></strong></div>
<p class="small text-muted mt-3 mb-0">Order #<?= htmlspecialchars(order_shipment_number((string)$receipt['order_number']), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($receipt['bag_code'], ENT_QUOTES, 'UTF-8'); ?></p>
<button class="btn btn-success mt-4" onclick="window.print()">Print receipt</button> <a class="btn btn-outline-secondary mt-4" href="index.php?tab=delivered">Back</a>
</div></article></main></body></html>
