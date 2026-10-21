<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

$page_title = "Admin Dashboard";
$current_page = "dashboard";

// --- Dynamic Database Queries --- //

// 1. Total Products Count
$stmt_products = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt_products->fetchColumn();

// 2. Total delivered sales
try {
  $stmt_sales = $pdo->query("SELECT SUM(grand_total) FROM orders WHERE LOWER(order_status) IN ('delivered', 'completed')");
  $total_sales = $stmt_sales->fetchColumn() ?: 0;
} catch (PDOException $e) {
  $total_sales = 0; // টেবিল না থাকলে 0 দেখাবে
}

// 3. Total Users Count
try {
  $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
  $total_users = $stmt_users->fetchColumn();
} catch (PDOException $e) {
  $total_users = 0;
}

// 4. Orders that still need fulfillment
try {
  $stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(order_status) IN ('pending', 'confirmed', 'processing', 'picked', 'packed', 'shipped', 'delivering')");
  $pending_orders = $stmt_orders->fetchColumn();
} catch (PDOException $e) {
  $pending_orders = 0;
}

// 5. Fetch Recent 5 Orders
$recent_orders = [];
try {
  $stmt_recent = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
  $recent_orders = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $recent_orders = [];
}

include_once 'includes/header.php';
?>


<!-- Dynamic Counter Cards -->
<div class="row g-3 mb-4 pt-4 px-2 flex-shrink-0">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-primary text-white">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h6 class="text-uppercase fs-7 opacity-75">Total Products</h6>
          <h2 class="mb-0 fw-bold"><?= number_format($total_products); ?></h2>
        </div>
        <i class="bi bi-box-seam fs-1 opacity-50"></i>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-success text-white">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h6 class="text-uppercase fs-7 opacity-75">Total Sales</h6>
          <h2 class="mb-0 fw-bold">৳<?= number_format((float)$total_sales, 0); ?></h2>
        </div>
        <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-warning text-dark">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h6 class="text-uppercase fs-7 opacity-75">Total Users</h6>
          <h2 class="mb-0 fw-bold"><?= number_format($total_users); ?></h2>
        </div>
        <i class="bi bi-people fs-1 opacity-50"></i>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm bg-info text-white">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h6 class="text-uppercase fs-7 opacity-75">Active Orders</h6>
          <h2 class="mb-0 fw-bold"><?= number_format($pending_orders); ?></h2>
        </div>
        <i class="bi bi-cart-check fs-1 opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders Section -->
<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-header bg-body border-0 py-3">
    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Recent Orders</h5>
  </div>
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light position-sticky top-0 z-2">
          <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recent_orders)): ?>
            <?php foreach ($recent_orders as $order): ?>
              <tr>
                <td class="fw-bold"><a href="order_details.php?id=<?= (int)$order['id']; ?>" class="link-body-emphasis">#<?= htmlspecialchars($order['order_number']); ?></a></td>
                <td><?= htmlspecialchars($order['fullname'] ?? 'Guest'); ?></td>
                <td><?= htmlspecialchars($order['phone'] ?? 'N/A'); ?></td>
                <td class="fw-bold text-success">৳<?= number_format((float)($order['grand_total'] ?? 0), 0); ?></td>
                <td>
                  <?php 
                    $order_status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
                    $badge_class = match($order_status) {
                      'delivered' => 'bg-success',
                      'delivering' => 'bg-primary',
                      'packed', 'picked' => 'bg-info text-dark',
                      'processing' => 'bg-info text-dark',
                      'confirmed' => 'bg-secondary',
                      'cancelled' => 'bg-danger',
                      default     => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars(ucfirst($order_status), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td><?= !empty($order['created_at']) ? date('d M, Y', strtotime($order['created_at'])) : 'N/A'; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">No recent orders found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>