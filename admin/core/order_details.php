<?php
require_once '../auth/config.php';
require_admin();

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger mb-0">Invalid Request</div>';
    exit();
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute([':id' => $id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="alert alert-warning mb-0">Order not found.</div>';
    exit();
}

// Fetch Order Items if table exists
$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
$items_stmt->execute([':id' => $id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <h6>Customer Details</h6>
    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']); ?></p>
    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
    <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>
  </div>
  <div class="col-md-6 text-md-end">
    <h6>Order Overview</h6>
    <p class="mb-1"><strong>Order ID:</strong> #<?= htmlspecialchars($order['order_number']); ?></p>
    <p class="mb-1"><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-primary"><?= ucfirst($order['status']); ?></span></p>
  </div>
</div>

<h6 class="border-bottom pb-2 mb-2">Purchased Items</h6>
<div class="table-responsive">
  <table class="table table-sm table-bordered mb-0">
    <thead class="table-light">
      <tr>
        <th>Product</th>
        <th class="text-center">Qty</th>
        <th class="text-end">Price</th>
        <th class="text-end">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name']); ?></td>
            <td class="text-center"><?= $item['quantity']; ?></td>
            <td class="text-end">৳<?= number_format($item['price'], 2); ?></td>
            <td class="text-end">৳<?= number_format($item['quantity'] * $item['price'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4" class="text-center text-muted">No itemized details recorded.</td>
        </tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" class="text-end">Grand Total:</th>
        <th class="text-end fw-bold">৳<?= number_format($order['total_amount'], 2); ?></th>
      </tr>
    </tfoot>
  </table>
</div>