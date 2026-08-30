<?php
require_once 'auth/config.php';

$page_title = "Sales History";
$current_page = "sales";

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <h2 class="h3 mb-0">Sales & Orders</h2>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">Order ID</th>
            <th class="bg-success text-white">Customer</th>
            <th class="bg-success text-white">Amount</th>
            <th class="bg-success text-white">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#ORD-1001</td>
            <td>Rahim Ahmed</td>
            <td>৳1,200.00</td>
            <td><span class="badge bg-success">Completed</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>