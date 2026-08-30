<?php
require_once 'auth/config.php';

$page_title = "Manage Categories";
$current_page = "category";

$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
  <h2 class="h3 mb-0">Category Management</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
    <i class="bi bi-plus-circle me-1"></i> Add Category
  </button>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">ID</th>
            <th class="bg-success text-white">Category Name</th>
            <th class="bg-success text-white text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= $cat['id']; ?></td>
              <td class="fw-bold"><?= htmlspecialchars($cat['name']); ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>