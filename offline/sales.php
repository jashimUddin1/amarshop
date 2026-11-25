<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();

$today = date('Y-m-d');
$from = is_string($_GET['from'] ?? null) ? $_GET['from'] : $today;
$to = is_string($_GET['to'] ?? null) ? $_GET['to'] : $today;
$valid_from = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
$valid_to = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
if (!$valid_from || $valid_from->format('Y-m-d') !== $from ||
    !$valid_to || $valid_to->format('Y-m-d') !== $to || $valid_from > $valid_to) {
    http_response_code(400);
    exit('Enter a valid date range.');
}
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (strlen($search) > 100) {
    http_response_code(400);
    exit('Search text is too long.');
}

$where = 's.created_at >= :from_date AND s.created_at < :to_date';
$params = [
    ':from_date' => $from . ' 00:00:00',
    ':to_date' => $valid_to->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
];
if ($search !== '') {
    $where .= ' AND (s.receipt_no LIKE :search_receipt OR s.customer_name LIKE :search_name OR s.customer_phone LIKE :search_phone)';
    $search_pattern = '%' . $search . '%';
    $params[':search_receipt'] = $search_pattern;
    $params[':search_name'] = $search_pattern;
    $params[':search_phone'] = $search_pattern;
}

$summary_stmt = $pdo->prepare("
    SELECT COUNT(*) AS sale_count,
        COALESCE(SUM(s.subtotal), 0) AS subtotal,
        COALESCE(SUM(s.discount_amount), 0) AS discount_total,
        COALESCE(SUM(s.grand_total), 0) AS sales_total
    FROM offline_pos_sales s
    WHERE $where
");
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch() ?: ['sale_count' => 0, 'subtotal' => 0, 'discount_total' => 0, 'sales_total' => 0];
$sales_stmt = $pdo->prepare("
    SELECT s.id, s.receipt_no, s.customer_name, s.customer_phone, s.subtotal,
        s.discount_amount, s.grand_total, s.cash_received, s.change_amount, s.created_at,
        u.full_name AS cashier_name,
        COALESCE(item_totals.item_count, 0) AS item_count
    FROM offline_pos_sales s
    LEFT JOIN offline_pos_users u ON u.id = s.cashier_id
    LEFT JOIN (
        SELECT sale_id, SUM(quantity) AS item_count
        FROM offline_pos_sale_items
        GROUP BY sale_id
    ) AS item_totals ON item_totals.sale_id = s.id
    WHERE $where
    ORDER BY s.id DESC
    LIMIT 500
");
$sales_stmt->execute($params);
$sales = $sales_stmt->fetchAll();

$page_title = 'Sales history';
pos_header($page_title, 'sales', $user);
?>
<section class="pos-page-heading">
  <div><h1>Counter sales history</h1><p>Review completed in-store cash bills and print receipts.</p></div>
  <a class="pos-button" href="sale.php">＋ New bill</a>
</section>

<section class="pos-card" style="margin-bottom:1rem">
  <form method="get" class="pos-card-body">
    <div class="pos-form-row" style="grid-template-columns:repeat(4,minmax(0,1fr));align-items:end">
      <div><label class="pos-label" for="fromDate">From</label><input class="pos-input" id="fromDate" type="date" name="from" value="<?= pos_escape($from); ?>" required></div>
      <div><label class="pos-label" for="toDate">To</label><input class="pos-input" id="toDate" type="date" name="to" value="<?= pos_escape($to); ?>" required></div>
      <div><label class="pos-label" for="salesSearch">Search</label><input class="pos-input" id="salesSearch" name="q" value="<?= pos_escape($search); ?>" placeholder="Receipt, customer, phone"></div>
      <button class="pos-button" type="submit">Apply filters</button>
    </div>
  </form>
</section>

<section class="pos-grid pos-grid-4" style="margin-bottom:1rem">
  <article class="pos-card pos-metric"><div class="pos-metric-label">Bills</div><div class="pos-metric-value"><?= number_format((int)$summary['sale_count']); ?></div><div class="pos-metric-note">For selected date range</div></article>
  <article class="pos-card pos-metric"><div class="pos-metric-label">Gross subtotal</div><div class="pos-metric-value">৳<?= number_format((float)$summary['subtotal'], 2); ?></div></article>
  <article class="pos-card pos-metric"><div class="pos-metric-label">Discounts</div><div class="pos-metric-value">৳<?= number_format((float)$summary['discount_total'], 2); ?></div></article>
  <article class="pos-card pos-metric"><div class="pos-metric-label">Net cash sales</div><div class="pos-metric-value">৳<?= number_format((float)$summary['sales_total'], 2); ?></div></article>
</section>

<section class="pos-card">
  <div class="pos-card-header"><h2>Completed bills</h2><span class="pos-help">Showing up to 500 records</span></div>
  <div class="pos-table-wrap">
    <table class="pos-table">
      <thead><tr><th>Receipt</th><th>Customer</th><th>Cashier</th><th>Items</th><th class="right">Discount</th><th class="right">Cash due</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sales as $sale): ?>
          <tr>
            <td><strong><?= pos_escape($sale['receipt_no']); ?></strong></td>
            <td>
              <?= pos_escape($sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in'); ?>
              <?php if ($sale['customer_phone'] !== ''): ?><div class="pos-help"><?= pos_escape($sale['customer_phone']); ?></div><?php endif; ?>
            </td>
            <td><?= pos_escape($sale['cashier_name'] ?: 'Former cashier'); ?></td>
            <td><?= number_format((int)$sale['item_count']); ?></td>
            <td class="right">৳<?= number_format((float)$sale['discount_amount'], 2); ?></td>
            <td class="right"><strong>৳<?= number_format((float)$sale['grand_total'], 2); ?></strong></td>
            <td><?= pos_escape(date('d M Y, h:i A', strtotime($sale['created_at']))); ?></td>
            <td><a class="pos-button pos-button-secondary" href="receipt.php?id=<?= (int)$sale['id']; ?>">Receipt</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$sales): ?><tr><td class="pos-empty" colspan="8">No counter bills match these filters.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<style>@media(max-width:640px){.pos-form-row[style*="repeat(4"]{grid-template-columns:1fr 1fr !important}.pos-grid-4{grid-template-columns:1fr 1fr}}</style>
<?php pos_footer(); ?>
