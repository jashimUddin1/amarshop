<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();

$sale_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($sale_id === false || $sale_id === null || $sale_id <= 0) {
    http_response_code(400);
    exit('Invalid receipt.');
}
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name AS cashier_name
    FROM offline_pos_sales s
    LEFT JOIN offline_pos_users u ON u.id = s.cashier_id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $sale_id]);
$sale = $stmt->fetch();
if (!$sale) {
    http_response_code(404);
    exit('Receipt not found.');
}
$items_stmt = $pdo->prepare('
    SELECT product_code, product_name, size, quantity, unit_price, line_total
    FROM offline_pos_sale_items
    WHERE sale_id = :sale
    ORDER BY id ASC
');
$items_stmt->execute([':sale' => $sale_id]);
$items = $items_stmt->fetchAll();
$print_on_open = ($_GET['print'] ?? '') === '1';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt <?= pos_escape($sale['receipt_no']); ?> · <?= pos_escape(POS_STORE_NAME); ?></title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 1.5rem; background: #f3f6f4; color: #17211b; font-family: Arial, Helvetica, sans-serif; }
    .receipt-actions { max-width: 760px; display:flex; justify-content:space-between; gap:.75rem; margin:0 auto 1rem; }
    .receipt-actions a, .receipt-actions button { padding:.65rem .85rem; border:1px solid #cbd6ce; border-radius:.5rem; background:white; color:#17211b; font:inherit; font-weight:700; text-decoration:none; cursor:pointer; }
    .receipt-actions .primary { color:white; background:#157347; border-color:#157347; }
    .receipt { width:min(100%, 520px); margin:auto; padding:2rem; background:white; border:1px solid #e1e8e3; box-shadow:0 6px 24px rgba(20,40,28,.08); }
    .receipt-header { text-align:center; padding-bottom:1rem; border-bottom:1px dashed #9ba79f; }
    .receipt-header h1 { margin:0; font-size:1.55rem; }
    .receipt-header p { margin:.35rem 0 0; color:#64736a; font-size:.9rem; }
    .receipt-meta { display:grid; grid-template-columns:1fr 1fr; gap:.45rem; padding:1rem 0; border-bottom:1px dashed #9ba79f; font-size:.9rem; }
    .receipt-meta span:nth-child(even) { text-align:right; }
    table { width:100%; margin:.65rem 0; border-collapse:collapse; font-size:.88rem; }
    th,td { padding:.55rem .15rem; text-align:left; vertical-align:top; border-bottom:1px solid #eef1ef; }
    th:last-child,td:last-child { text-align:right; white-space:nowrap; }
    .item-details { color:#64736a; font-size:.78rem; margin-top:.15rem; }
    .totals { padding-top:.5rem; border-top:1px dashed #9ba79f; }
    .total-row { display:flex; justify-content:space-between; gap:1rem; padding:.3rem 0; }
    .grand-total { margin-top:.3rem; padding-top:.65rem; border-top:1px solid #d5ded8; font-size:1.15rem; font-weight:800; }
    .receipt-footer { margin-top:1.25rem; padding-top:1rem; border-top:1px dashed #9ba79f; text-align:center; color:#64736a; font-size:.85rem; line-height:1.5; }
    @media print {
      @page { margin:8mm; }
      body { padding:0; background:white; }
      .receipt-actions { display:none !important; }
      .receipt { width:72mm; margin:0 auto; padding:0; border:0; box-shadow:none; }
      .receipt-header h1 { font-size:1.25rem; }
      table { font-size:9pt; }
    }
  </style>
</head>
<body>
  <div class="receipt-actions">
    <a href="dashboard.php">← Counter</a>
    <div style="display:flex;gap:.5rem">
      <a href="sales.php">Sales history</a>
      <button class="primary" type="button" onclick="window.print()">Print / save PDF</button>
    </div>
  </div>
  <main class="receipt">
    <header class="receipt-header">
      <h1><?= pos_escape(POS_STORE_NAME); ?></h1>
      <p>In-store cash sale</p>
    </header>
    <div class="receipt-meta">
      <strong>Receipt</strong><span><?= pos_escape($sale['receipt_no']); ?></span>
      <span>Date</span><span><?= pos_escape(date('d M Y, h:i A', strtotime($sale['created_at']))); ?></span>
      <span>Cashier</span><span><?= pos_escape($sale['cashier_name'] ?: 'Counter'); ?></span>
      <?php if ($sale['customer_name'] !== ''): ?><span>Customer</span><span><?= pos_escape($sale['customer_name']); ?></span><?php endif; ?>
      <?php if ($sale['customer_phone'] !== ''): ?><span>Phone</span><span><?= pos_escape($sale['customer_phone']); ?></span><?php endif; ?>
    </div>
    <table>
      <thead><tr><th>Item</th><th>Qty × Price</th><th>Amount</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <strong><?= pos_escape($item['product_name']); ?></strong>
              <div class="item-details"><?= pos_escape($item['product_code']); ?><?= $item['size'] !== '' ? ' · ' . pos_escape($item['size']) : ''; ?></div>
            </td>
            <td><?= (int)$item['quantity']; ?> × ৳<?= number_format((float)$item['unit_price'], 2); ?></td>
            <td>৳<?= number_format((float)$item['line_total'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="totals">
      <div class="total-row"><span>Subtotal</span><strong>৳<?= number_format((float)$sale['subtotal'], 2); ?></strong></div>
      <div class="total-row"><span>Discount</span><strong>− ৳<?= number_format((float)$sale['discount_amount'], 2); ?></strong></div>
      <div class="total-row grand-total"><span>Cash due</span><span>৳<?= number_format((float)$sale['grand_total'], 2); ?></span></div>
      <div class="total-row"><span>Cash received</span><span>৳<?= number_format((float)$sale['cash_received'], 2); ?></span></div>
      <div class="total-row"><span>Change</span><span>৳<?= number_format((float)$sale['change_amount'], 2); ?></span></div>
    </div>
    <footer class="receipt-footer">Thank you for shopping with us.<br>Goods sold at the counter.</footer>
  </main>
  <?php if ($print_on_open): ?><script>window.addEventListener('load', () => window.print());</script><?php endif; ?>
</body>
</html>
