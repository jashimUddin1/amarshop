<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

$page_title = 'Admin Dashboard';
$current_page = 'dashboard';

$total_products = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn();
$delivered_sales = (float)$pdo->query("
    SELECT COALESCE(SUM(grand_total), 0)
    FROM orders
    WHERE LOWER(order_status) IN ('delivered', 'completed')
")->fetchColumn();

$today_orders = (int)$pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetchColumn();

$active_orders = (int)$pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE LOWER(order_status) IN ('pending', 'processing', 'confirmed', 'picked', 'packed', 'packed_done', 'inbag', 'shipped', 'delivering')
")->fetchColumn();

$low_stock_count = (int)$pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE deleted_at IS NULL AND stock <= 5
")->fetchColumn();
$low_stock_products = $pdo->query("
    SELECT id, product_code, name, stock
    FROM products
    WHERE deleted_at IS NULL AND stock <= 5
    ORDER BY stock ASC, name ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$order_activity_rows = $pdo->query("
    SELECT DATE(created_at) AS order_date, COUNT(*) AS order_count
    FROM orders
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
        AND created_at < CURDATE() + INTERVAL 1 DAY
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
$orders_by_day = [];
foreach ($order_activity_rows as $row) {
    $orders_by_day[(string)$row['order_date']] = (int)$row['order_count'];
}

$order_activity = [];
for ($days_ago = 6; $days_ago >= 0; $days_ago--) {
    $day = new DateTimeImmutable('today');
    if ($days_ago > 0) {
        $day = $day->modify('-' . $days_ago . ' days');
    }
    $date_key = $day->format('Y-m-d');
    $order_activity[] = [
        'label' => $day->format('D'),
        'date' => $date_key,
        'count' => $orders_by_day[$date_key] ?? 0,
    ];
}
$max_daily_orders = max(1, ...array_column($order_activity, 'count'));

$pipeline = $pdo->query("
    SELECT
        SUM(CASE WHEN LOWER(order_status) IN ('pending', 'processing') THEN 1 ELSE 0 END) AS to_process,
        SUM(CASE WHEN LOWER(order_status) IN ('confirmed', 'picked', 'packed', 'packed_done', 'inbag') THEN 1 ELSE 0 END) AS ready_for_delivery,
        SUM(CASE WHEN LOWER(order_status) IN ('shipped', 'delivering') THEN 1 ELSE 0 END) AS out_for_delivery
    FROM orders
")->fetch(PDO::FETCH_ASSOC) ?: [];
$pipeline = [
    'to_process' => (int)($pipeline['to_process'] ?? 0),
    'ready_for_delivery' => (int)($pipeline['ready_for_delivery'] ?? 0),
    'out_for_delivery' => (int)($pipeline['out_for_delivery'] ?? 0),
];

$recent_orders = $pdo->query("
    SELECT id, order_number, fullname, phone, grand_total, order_status, created_at
    FROM orders
    ORDER BY id DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>
<style>
  .dashboard-page {
    min-height: 0;
    overflow-y: auto;
  }

  .dashboard-heading {
    background: linear-gradient(120deg, var(--bs-success-bg-subtle), var(--bs-body-bg) 72%);
  }

  .dashboard-metric {
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 0.125rem 0.5rem rgba(33, 37, 41, 0.04);
  }

  .dashboard-metric-icon {
    width: 2.75rem;
    height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    font-size: 1.25rem;
  }

  .dashboard-chart {
    height: 190px;
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    align-items: end;
    gap: clamp(0.35rem, 2vw, 1rem);
    border-bottom: 1px solid var(--bs-border-color);
    background: repeating-linear-gradient(
      to top,
      transparent 0,
      transparent calc(25% - 1px),
      var(--bs-border-color-translucent) 25%,
      transparent calc(25% + 1px)
    );
  }

  .dashboard-chart-day {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: end;
    gap: 0.4rem;
    min-width: 0;
  }

  .dashboard-chart-bar {
    width: min(100%, 2.25rem);
    min-height: 4px;
    border-radius: 0.4rem 0.4rem 0 0;
    background: var(--bs-success);
  }

  .dashboard-chart-label {
    transform: translateY(1.45rem);
  }

  .dashboard-pipeline-link {
    color: inherit;
    text-decoration: none;
  }

  .dashboard-pipeline-link:hover {
    border-color: var(--bs-success-border-subtle) !important;
    background: var(--bs-success-bg-subtle);
  }
</style>

<div class="dashboard-page flex-grow-1 px-3 px-xl-4 pb-4">
  <section class="dashboard-heading rounded-4 border p-3 p-md-4 mt-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <div class="text-success fw-semibold small text-uppercase mb-1">Overview</div>
      <h1 class="h3 fw-bold mb-1">Admin dashboard</h1>
      <p class="text-body-secondary mb-0">A clear view of orders, sales and inventory.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="sales.php" class="btn btn-outline-success"><i class="bi bi-receipt me-1"></i>View orders</a>
      <a href="purchases.php?tab=new" class="btn btn-success"><i class="bi bi-bag-plus me-1"></i>Record purchase</a>
    </div>
  </section>

  <section class="row g-3 mb-4" aria-label="Business summary">
    <div class="col-12 col-sm-6 col-xl-3">
      <article class="card dashboard-metric h-100 rounded-3">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <div class="small text-body-secondary mb-2">Delivered sales</div>
            <div class="h3 fw-bold mb-1">৳<?= number_format($delivered_sales, 0); ?></div>
            <div class="small text-body-secondary">All-time, delivered orders</div>
          </div>
          <span class="dashboard-metric-icon bg-success-subtle text-success"><i class="bi bi-currency-exchange"></i></span>
        </div>
      </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <article class="card dashboard-metric h-100 rounded-3">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <div class="small text-body-secondary mb-2">Orders today</div>
            <div class="h3 fw-bold mb-1"><?= number_format($today_orders); ?></div>
            <div class="small text-body-secondary">Placed since midnight</div>
          </div>
          <span class="dashboard-metric-icon bg-primary-subtle text-primary"><i class="bi bi-cart-plus"></i></span>
        </div>
      </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <article class="card dashboard-metric h-100 rounded-3">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <div class="small text-body-secondary mb-2">Active orders</div>
            <div class="h3 fw-bold mb-1"><?= number_format($active_orders); ?></div>
            <div class="small text-body-secondary">Still in fulfillment</div>
          </div>
          <span class="dashboard-metric-icon bg-info-subtle text-info-emphasis"><i class="bi bi-box-seam"></i></span>
        </div>
      </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <article class="card dashboard-metric h-100 rounded-3">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <div class="small text-body-secondary mb-2">Low stock</div>
            <div class="h3 fw-bold mb-1"><?= number_format($low_stock_count); ?></div>
            <div class="small text-body-secondary">Products with 5 or fewer units</div>
          </div>
          <span class="dashboard-metric-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-exclamation-triangle"></i></span>
        </div>
      </article>
    </div>
  </section>

  <section class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
      <article class="card h-100 rounded-3">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
              <h2 class="h5 fw-bold mb-1">Order activity</h2>
              <p class="small text-body-secondary mb-0">New orders placed in the last 7 days</p>
            </div>
            <a href="sales.php" class="btn btn-sm btn-outline-secondary">All orders<i class="bi bi-arrow-up-right ms-1"></i></a>
          </div>
          <div class="dashboard-chart" role="img" aria-label="Daily order counts for the last seven days">
            <?php foreach ($order_activity as $day): ?>
              <?php $bar_height = $day['count'] > 0 ? max(8, (int)round($day['count'] / $max_daily_orders * 100)) : 3; ?>
              <div class="dashboard-chart-day" title="<?= htmlspecialchars($day['date'], ENT_QUOTES, 'UTF-8'); ?>: <?= number_format($day['count']); ?> orders">
                <span class="small fw-semibold text-body-secondary"><?= number_format($day['count']); ?></span>
                <div class="dashboard-chart-bar" style="height: <?= $bar_height; ?>%;"></div>
                <span class="dashboard-chart-label small text-body-secondary"><?= htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </article>
    </div>
    <div class="col-12 col-xl-4">
      <article class="card h-100 rounded-3">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
              <h2 class="h5 fw-bold mb-1">Order pipeline</h2>
              <p class="small text-body-secondary mb-0">Where active orders stand</p>
            </div>
            <a href="sales.php" class="small link-success">Manage</a>
          </div>
          <div class="d-grid gap-2">
            <a href="sales.php?order_status=processing" class="dashboard-pipeline-link border rounded-3 p-3 d-flex justify-content-between align-items-center">
              <span><i class="bi bi-hourglass-split text-warning me-2"></i>To process</span>
              <span class="badge rounded-pill text-bg-warning"><?= number_format($pipeline['to_process']); ?></span>
            </a>
            <a href="sales.php" class="dashboard-pipeline-link border rounded-3 p-3 d-flex justify-content-between align-items-center">
              <span><i class="bi bi-box2 text-primary me-2"></i>Preparing / ready</span>
              <span class="badge rounded-pill text-bg-primary"><?= number_format($pipeline['ready_for_delivery']); ?></span>
            </a>
            <a href="sales.php?order_status=delivering" class="dashboard-pipeline-link border rounded-3 p-3 d-flex justify-content-between align-items-center">
              <span><i class="bi bi-bicycle text-success me-2"></i>Out for delivery</span>
              <span class="badge rounded-pill text-bg-success"><?= number_format($pipeline['out_for_delivery']); ?></span>
            </a>
          </div>
        </div>
      </article>
    </div>
  </section>

  <section class="row g-3">
    <div class="col-12 col-xl-8">
      <article class="card rounded-3">
        <div class="card-header bg-body d-flex justify-content-between align-items-center py-3">
          <h2 class="h5 fw-bold mb-0">Recent orders</h2>
          <a href="sales.php" class="btn btn-sm btn-outline-secondary">View all</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th><span class="visually-hidden">Action</span></th></tr>
            </thead>
            <tbody>
              <?php if ($recent_orders): ?>
                <?php foreach ($recent_orders as $order): ?>
                  <?php
                    $order_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
                    $badge_class = match ($order_status) {
                      'delivered' => 'text-bg-success',
                      'delivering' => 'text-bg-primary',
                      'packed', 'picked' => 'text-bg-info',
                      'packed_done' => 'text-bg-primary',
                      'processing' => 'text-bg-warning',
                      'confirmed', 'inbag' => 'text-bg-secondary',
                      'cancelled' => 'text-bg-danger',
                      default => 'text-bg-secondary',
                    };
                  ?>
                  <tr>
                    <td class="fw-semibold">#<?= htmlspecialchars(order_shipment_number((string)($order['order_number'] ?? $order['id'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($order['fullname'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="small text-body-secondary"><?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td class="text-nowrap">৳<?= number_format((float)($order['grand_total'] ?? 0), 0); ?></td>
                    <td><span class="badge <?= $badge_class; ?>"><?= htmlspecialchars(order_status_label($order_status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td class="text-nowrap"><?= !empty($order['created_at']) ? date('d M, Y', strtotime($order['created_at'])) : '—'; ?></td>
                    <td><a href="order_details.php?id=<?= (int)$order['id']; ?>" class="btn btn-sm btn-outline-secondary">Open</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center text-body-secondary py-4">No orders yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </div>
    <div class="col-12 col-xl-4">
      <article class="card h-100 rounded-3">
        <div class="card-header bg-body d-flex justify-content-between align-items-center py-3">
          <div>
            <h2 class="h5 fw-bold mb-1">Low stock</h2>
            <div class="small text-body-secondary">Replenishment may be needed</div>
          </div>
          <a href="products.php" class="btn btn-sm btn-outline-secondary">Products</a>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($low_stock_products as $product): ?>
            <a href="products.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 py-3">
              <span class="min-w-0">
                <span class="d-block fw-semibold text-truncate"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="small text-body-secondary"><?= htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
              <span class="badge <?= (int)$product['stock'] === 0 ? 'text-bg-danger' : 'text-bg-warning'; ?> flex-shrink-0">
                <?= (int)$product['stock'] === 0 ? 'Out of stock' : number_format((int)$product['stock']) . ' left'; ?>
              </span>
            </a>
          <?php endforeach; ?>
          <?php if (!$low_stock_products): ?>
            <div class="text-center text-body-secondary py-4">No products at low-stock level.</div>
          <?php endif; ?>
        </div>
        <div class="card-body border-top py-3">
          <div class="d-flex justify-content-between align-items-center">
            <span class="small text-body-secondary">Active products</span>
            <span class="fw-semibold"><?= number_format($total_products); ?></span>
          </div>
        </div>
      </article>
    </div>
  </section>
</div>

<?php include_once 'includes/footer.php'; ?>
