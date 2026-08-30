<?php
require_once 'auth/config.php';

$page_title = "Manage Collections";
$current_page = "collection";

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <h2 class="h3 mb-0">Product Collections</h2>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">Collection Key</th>
            <th class="bg-success text-white">Display Name</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>new_arrivals</td><td>New Arrivals</td></tr>
          <tr><td>top_deals</td><td>Top Deals</td></tr>
          <tr><td>high_discount</td><td>High Discount Products</td></tr>
          <tr><td>top_sales</td><td>Top Sales</td></tr>
          <tr><td>top_rated</td><td>Top Rated Products</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>