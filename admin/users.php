<?php
require_once 'auth/config.php';

$page_title = "Manage Users";
$current_page = "users";

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <h2 class="h3 mb-0">User Management</h2>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">ID</th>
            <th class="bg-success text-white">Name</th>
            <th class="bg-success text-white">Role</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>Admin User</td>
            <td><span class="badge bg-primary">Admin</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>