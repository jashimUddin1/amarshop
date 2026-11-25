<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();

$today_summary = $pdo->query("
    SELECT COUNT(*) AS sale_count, COALESCE(SUM(grand_total), 0) AS sales_total
    FROM offline_pos_sales
    WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch();
$today_summary = $today_summary ?: ['sale_count' => 0, 'sales_total' => 0];
$month_sales = (float)$pdo->query("
    SELECT COALESCE(SUM(grand_total), 0)
    FROM offline_pos_sales
    WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        AND created_at < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
")->fetchColumn();
$product_summary = $pdo->query("
    SELECT COUNT(*) AS product_count,
        COALESCE(SUM(CASE WHEN stock <= " . POS_LOW_STOCK_LEVEL . " THEN 1 ELSE 0 END), 0) AS low_stock_count
    FROM products
    WHERE deleted_at IS NULL
")->fetch() ?: ['product_count' => 0, 'low_stock_count' => 0];
$recent_sales = $pdo->query("
    SELECT s.id, s.receipt_no, s.customer_name, s.grand_total, s.created_at, u.full_name AS cashier_name
    FROM offline_pos_sales s
    JOIN offline_pos_users u ON u.id = s.cashier_id
    ORDER BY s.id DESC
    LIMIT 8
")->fetchAll();
$low_stock_products = $pdo->query("
    SELECT id, product_code, name, stock
    FROM products
    WHERE deleted_at IS NULL AND stock <= " . POS_LOW_STOCK_LEVEL . "
    ORDER BY stock ASC, name ASC
    LIMIT 6
")->fetchAll();

$page_title = 'Overview';
pos_header($page_title, 'dashboard', $user);
?>
<section class="pos-page-heading">
  <div>
    <h1>Counter overview</h1>
    <p><?= pos_escape(date('l, d F Y')); ?> · Walk-in sales and shared inventory</p>
  </div>
  <div class="pos-actions">
    <a class="pos-button pos-button-secondary" href="sales.php">Sales report</a>
    <a class="pos-button" href="sale.php">＋ New bill</a>
  </div>
</section>

<section class="pos-grid pos-grid-4" aria-label="Today's counter summary">
  <article class="pos-card pos-metric">
    <div class="pos-metric-label">Today's sales</div>
    <div class="pos-metric-value">৳<?= number_format((float)$today_summary['sales_total'], 2); ?></div>
    <div class="pos-metric-note">Cash bills completed today</div>
  </article>
  <article class="pos-card pos-metric">
    <div class="pos-metric-label">Bills today</div>
    <div class="pos-metric-value"><?= number_format((int)$today_summary['sale_count']); ?></div>
    <div class="pos-metric-note">Walk-in transactions</div>
  </article>
  <article class="pos-card pos-metric">
    <div class="pos-metric-label">This month's counter sales</div>
    <div class="pos-metric-value">৳<?= number_format($month_sales, 2); ?></div>
    <div class="pos-metric-note">Since <?= pos_escape(date('1 M Y')); ?></div>
  </article>
  <article class="pos-card pos-metric">
    <div class="pos-metric-label">Low-stock products</div>
    <div class="pos-metric-value"><?= number_format((int)$product_summary['low_stock_count']); ?></div>
    <div class="pos-metric-note">At or below <?= POS_LOW_STOCK_LEVEL; ?> units · <?= number_format((int)$product_summary['product_count']); ?> active products</div>
  </article>
</section>

<section class="pos-grid pos-grid-2" style="margin-top:1rem">
  <article class="pos-card">
    <div class="pos-card-header">
      <h2>Recent counter bills</h2>
      <a class="pos-button pos-button-secondary" href="sales.php">View all</a>
    </div>
    <div class="pos-table-wrap">
      <table class="pos-table">
        <thead><tr><th>Receipt</th><th>Customer</th><th>Cashier</th><th class="right">Total</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recent_sales as $sale): ?>
            <tr>
              <td><a href="receipt.php?id=<?= (int)$sale['id']; ?>"><strong><?= pos_escape($sale['receipt_no']); ?></strong></a></td>
              <td><?= pos_escape($sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in'); ?></td>
              <td><?= pos_escape($sale['cashier_name']); ?></td>
              <td class="right">৳<?= number_format((float)$sale['grand_total'], 2); ?></td>
              <td><?= pos_escape(date('d M, h:i A', strtotime($sale['created_at']))); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recent_sales): ?><tr><td class="pos-empty" colspan="5">No counter bills yet. Start with a new bill.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="pos-card">
    <div class="pos-card-header">
      <div>
        <h2>Shared inventory · low stock</h2>
        <div class="pos-help">Stock changes here also update the online store.</div>
      </div>
    </div>
    <div class="pos-table-wrap">
      <table class="pos-table">
        <thead><tr><th>Product</th><th>Code</th><th class="right">Available</th></tr></thead>
        <tbody>
          <?php foreach ($low_stock_products as $product): ?>
            <tr>
              <td><?= pos_escape($product['name']); ?></td>
              <td><?= pos_escape($product['product_code']); ?></td>
              <td class="right"><span class="pos-badge <?= (int)$product['stock'] === 0 ? 'pos-badge-warning' : ''; ?>"><?= (int)$product['stock']; ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$low_stock_products): ?><tr><td class="pos-empty" colspan="3">No low-stock products right now.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
<?php pos_footer(); ?>
