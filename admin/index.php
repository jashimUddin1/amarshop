<?php
require_once 'auth/config.php';
require_admin();

$page_title = "Admin Dashboard";
$current_page = "dashboard";

// --- Dynamic Database Queries --- //

// 1. Total Products Count
$stmt_products = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt_products->fetchColumn();

// 2. Total Sales (Completed Orders Sum)
// Note: 'orders' টেবিলে 'grand_total' এবং 'order_status' কলাম আছে ধরে নেওয়া হয়েছে
try {
  $stmt_sales = $pdo->query("SELECT SUM(grand_total) FROM orders WHERE order_status = 'delivered'");
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

// 4. Pending / Active Orders Count
try {
  $stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
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
          <h2 class="mb-0 fw-bold">৳<?= number_format($total_sales, 2); ?></h2>
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
          <h6 class="text-uppercase fs-7 opacity-75">Pending Orders</h6>
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
            <th>order_status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recent_orders)): ?>
            <?php foreach ($recent_orders as $order): ?>
              <tr>
                <td class="fw-bold">#ORD-<?= htmlspecialchars($order['order_number']); ?></td>
                <td><?= htmlspecialchars($order['fullname'] ?? 'Guest'); ?></td>
                <td><?= htmlspecialchars($order['phone'] ?? 'N/A'); ?></td>
                <td class="fw-bold text-success">৳<?= number_format($order['grand_total'] ?? 0, 2); ?></td>
                <td>
                  <?php 
                    $order_status = $order['order_status'] ?? 'pending';
                    $badge_class = match($order_status) {
                      'completed' => 'bg-success',
                      'pending'   => 'bg-warning text-dark',
                      'cancelled' => 'bg-danger',
                      default     => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?= $badge_class; ?>"><?= ucfirst($order_status); ?></span>
                </td>
                <td><?= date('d M, Y', strtotime($order['created_at'] ?? 'now')); ?></td>
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