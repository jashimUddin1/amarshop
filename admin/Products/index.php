<?php // admin/products/index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../auth/config.php';

// Category Add Handler (সরাসরি পোস্ট হ্যান্ডেল করা আছে)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
  $cat_name = trim($_POST['category_name']);
  if (!empty($cat_name)) {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$cat_name]);

    $_SESSION['status'] = "Category Added Successfully!";
    $_SESSION['status_type'] = "success";
  } else {
    $_SESSION['status'] = "Category Name Cannot be Empty!";
    $_SESSION['status_type'] = "danger";
  }
  header("Location: index.php");
  exit;
}

// Data Fetching
$products_stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Table Column  Mapping Array
$collection_map = [
  'new_arrivals'  => 'New Arrivals',
  'top_deals'     => 'Top Deals',
  'high_discount' => 'High Discount Products',
  'top_sales'     => 'Top Sales',
  'top_rated'     => 'Top Rated Products'
];

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel - Products</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      overflow-x: hidden;
    }

    .sidebar {
      min-height: calc(100vh - 56px);
      border-right: 1px solid var(--bs-border-color);
    }

    .sidebar .nav-link {
      color: var(--bs-body-color);
      padding: .8rem 1rem;
      border-radius: .5rem;
      margin: .2rem .5rem;
    }

    .sidebar .nav-link:hover {
      background: var(--bs-secondary-bg);
    }

    .sidebar .nav-link.active {
      background: var(--bs-primary);
      color: #fff;
    }

    .img-thumbnail-table {
      width: 45px;
      height: 45px;
      object-fit: cover;
      border-radius: 6px;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top px-3">
    <button class="btn d-lg-none me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
      <i class="bi bi-list fs-3"></i>
    </button>
    <a class="navbar-brand fw-bold" href="#">AmarShop Admin</a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="fw-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
      <button class="btn btn-outline-secondary" id="themeToggle"><i class="bi bi-moon"></i></button>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">

      <aside class="col-lg-2 d-none d-lg-block sidebar bg-body-tertiary">
        <ul class="nav flex-column py-3">
          <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
          <li class="nav-item"><a class="nav-link" href="../Sales/index.php"><i class="bi bi-cart-check me-2"></i>Sales</a></li>
          <li class="nav-item"><a class="nav-link" href="../Users/index.php"><i class="bi bi-people me-2"></i>Users</a></li>
          <li class="nav-item"><a class="nav-link" href="../site-setting/index.php"><i class="bi bi-gear me-2"></i>Site Settings</a></li>
        </ul>
      </aside>

      <main class="col-lg-10 p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="h3 mb-0">Product Management</h2>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
              <i class="bi bi-folder-plus me-1"></i> Add Category
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
              <i class="bi bi-plus-circle me-1"></i> Add Product
            </button>
          </div>
        </div>

        <div id="alert-container">
          <?php if (isset($_SESSION['status'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['status_type'] ?? 'info'); ?> alert-dismissible fade show auto-banish-alert" role="alert">
              <?= htmlspecialchars($_SESSION['status']); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
            unset($_SESSION['status']);
            unset($_SESSION['status_type']);
            ?>
          <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                  <tr>
                    <th>Image</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Purchase</th>
                    <th>Sell</th>
                    <th>Discount</th>
                    <th>Actual Price</th>
                    <th>Size</th>
                    <th>Collection</th>
                    <th>Stock</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($products)): ?>
                    <?php foreach ($products as $row): ?>
                      <tr id="product-row-<?= $row['id']; ?>">
                        <td>
                          <img src="<?= '../../uploads/' . ($row['main_img'] ?: 'placeholder.jpg'); ?>" class="img-thumbnail-table" alt="Product">
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['product_code']); ?></span></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['name']); ?></td>
                        <td><span class="badge bg-info-subtle text-info border border-info"><?= htmlspecialchars($row['category_name'] ?: 'Uncategorized'); ?></span></td>
                        <td>৳<?= number_format($row['purchase_price'], 2); ?></td>
                        <td>৳<?= number_format($row['sell_price'], 2); ?></td>
                        <td><?= $row['discount_percent']; ?>%</td>
                        <td class="text-success fw-bold">৳<?= number_format($row['actual_price'] ?? 0, 2); ?></td>
                        <td><?= htmlspecialchars($row['size'] ?: 'N/A'); ?></td>
                        <td>
                          <?php
                          $col_key = $row['collection_name'] ?? '';
                          echo htmlspecialchars($collection_map[$col_key] ?? ($col_key ?: 'N/A'));
                          ?>
                        </td>
                        <td>
                          <?php if ($row['stock'] > 5): ?>
                            <span class="badge bg-success-subtle text-success border border-success"><?= $row['stock']; ?> Pcs</span>
                          <?php elseif ($row['stock'] > 0): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning"><?= $row['stock']; ?> Low</span>
                          <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger">Out Stock</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $row['id']; ?>">
                            <i class="bi bi-pencil"></i>
                          </button>

                          <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id']; ?>">
                            <i class="bi bi-trash"></i>
                          </button>

<div class="modal fade text-start" id="editProductModal<?= $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editProductForm<?= $row['id']; ?>" enctype="multipart/form-data" onsubmit="handleAjaxSubmit(event, 'core/product_edit_core.php', '#editProductModal<?= $row['id']; ?>')">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product - <?= htmlspecialchars($row['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden Inputs for IDs and Old Images -->
                    <input type="hidden" name="product_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="old_main_img" value="<?= $row['main_img']; ?>">
                    <input type="hidden" name="old_side_img1" value="<?= $row['side_img1']; ?>">
                    <input type="hidden" name="old_side_img2" value="<?= $row['side_img2']; ?>">
                    <input type="hidden" name="old_side_img3" value="<?= $row['side_img3']; ?>">
                    <input type="hidden" name="old_side_img4" value="<?= $row['side_img4']; ?>">
                    <input type="hidden" name="old_side_img5" value="<?= $row['side_img5']; ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($row['product_code']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_name" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']); ?>" <?= ($cat['name'] === $row['category_name']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= $row['purchase_price']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sell Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="sell_price" class="form-control" value="<?= $row['sell_price']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount (%)</label>
                            <input type="number" step="0.01" name="discount_percent" class="form-control" value="<?= $row['discount_percent']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Size Options</label>
                            <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($row['size']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control" value="<?= $row['stock']; ?>" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Collection Name</label>
                            <select name="collection_name" class="form-select">
                                <?php
                                $collections = [
                                    'new_arrivals' => 'New Arrivals',
                                    'top_deals' => 'Top Deals',
                                    'high_discount' => 'High Discount Products',
                                    'top_sales' => 'Top Sales',
                                    'top_rated' => 'Top Rated Products'
                                ];
                                foreach ($collections as $key => $val):
                                ?>
                                    <option value="<?= $key; ?>" <?= ($key === $row['collection_name']) ? 'selected' : ''; ?>><?= $val; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($row['description']); ?></textarea>
                        </div>

                        <!-- Main Image -->
                        <div class="col-md-6">
                            <label class="form-label">Main Image</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['main_img'])): ?>
                                    <img src="../../uploads/<?= $row['main_img']; ?>" alt="Main" class="img-thumbnail flex-shrink-0" style="width: 45px; height: 45px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="main_img" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <!-- Side Image 1 -->
                        <div class="col-md-6">
                            <label class="form-label">Side Image 1</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['side_img1'])): ?>
                                    <img src="../../uploads/<?= $row['side_img1']; ?>" alt="Side 1" class="img-thumbnail flex-shrink-0" style="width: 45px; height: 45px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="side_img1" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <!-- Side Image 2 -->
                        <div class="col-md-3">
                            <label class="form-label">Side Image 2</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['side_img2'])): ?>
                                    <img src="../../uploads/<?= $row['side_img2']; ?>" alt="Side 2" class="img-thumbnail flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="side_img2" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <!-- Side Image 3 -->
                        <div class="col-md-3">
                            <label class="form-label">Side Image 3</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['side_img3'])): ?>
                                    <img src="../../uploads/<?= $row['side_img3']; ?>" alt="Side 3" class="img-thumbnail flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="side_img3" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <!-- Side Image 4 -->
                        <div class="col-md-3">
                            <label class="form-label">Side Image 4</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['side_img4'])): ?>
                                    <img src="../../uploads/<?= $row['side_img4']; ?>" alt="Side 4" class="img-thumbnail flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="side_img4" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <!-- Side Image 5 -->
                        <div class="col-md-3">
                            <label class="form-label">Side Image 5</label>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['side_img5'])): ?>
                                    <img src="../../uploads/<?= $row['side_img5']; ?>" alt="Side 5" class="img-thumbnail flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="side_img5" class="form-control" accept="image/*">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning submit-btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

                          <div class="modal fade text-start" id="deleteModal<?= $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                              <div class="modal-content">
                                <form id="deleteProductForm<?= $row['id']; ?>" onsubmit="handleAjaxSubmit(event, 'core/product_delete_core.php', '#deleteModal<?= $row['id']; ?>')">
                                  <div class="modal-header">
                                    <h5 class="modal-title text-danger">Confirm Delete</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="product_id" value="<?= $row['id']; ?>">
                                    <p class="mb-0">Are you sure you want to permanently delete <strong><?= htmlspecialchars($row['name']); ?></strong>?</p>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger submit-btn">Yes, Delete</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>

                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="12" class="text-center py-4 text-muted">No products found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="index.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Category Name</label>
              <input type="text" name="category_name" class="form-control" required placeholder="e.g. Electronics">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="add_category" class="btn btn-primary">Save Category</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addProductForm" enctype="multipart/form-data" onsubmit="handleAjaxSubmit(event, 'core/product_add_core.php', '#addProductModal')">
          <div class="modal-header">
            <h5 class="modal-title">Add New Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Product Code <span class="text-danger">*</span></label>
                <input type="text" name="product_code" class="form-control" required placeholder="PROD-101">
              </div>
              <div class="col-md-4">
                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category_name" class="form-select" required>
                  <option value="" selected disabled>Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']); ?>"><?= htmlspecialchars($cat['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="purchase_price" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Sell Price <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="sell_price" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Discount (%)</label>
                <input type="number" step="0.01" name="discount_percent" class="form-control" value="0">
              </div>

              <div class="col-md-4">
                <label class="form-label">Size Options</label>
                <input type="text" name="size" class="form-control" placeholder="e.g. S, M, L, XL">
              </div>
              <div class="col-md-4">
                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                <input type="number" name="stock" class="form-control" min="0" value="0" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Collection Name</label>
                <select name="collection_name" class="form-select">
                  <option value="new_arrivals">New Arrivals</option>
                  <option value="top_deals">Top Deals</option>
                  <option value="high_discount">High Discount Products</option>
                  <option value="top_sales">Top Sales</option>
                  <option value="top_rated">Top Rated Products</option>
                </select>
              </div>

              <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Write products description"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label">Main Image <span class="text-danger">*</span></label>
                <input type="file" name="main_img" class="form-control" accept="image/*" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Side Image 1</label>
                <input type="file" name="side_img1" class="form-control" accept="image/*">
              </div>
              <div class="col-md-3">
                <label class="form-label">Side Image 2</label>
                <input type="file" name="side_img2" class="form-control" accept="image/*">
              </div>
              <div class="col-md-3">
                <label class="form-label">Side Image 3</label>
                <input type="file" name="side_img3" class="form-control" accept="image/*">
              </div>
              <div class="col-md-3">
                <label class="form-label">Side Image 4</label>
                <input type="file" name="side_img4" class="form-control" accept="image/*">
              </div>
              <div class="col-md-3">
                <label class="form-label">Side Image 5</label>
                <input type="file" name="side_img5" class="form-control" accept="image/*">
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary submit-btn">Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Theme Toggle
    const html = document.documentElement;
    const btn = document.getElementById("themeToggle");

    function applyTheme(theme) {
      html.setAttribute("data-bs-theme", theme);
      btn.innerHTML = theme === "dark" ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    }

    const savedTheme = localStorage.getItem("amarshop_admin_theme");
    if (savedTheme) {
      applyTheme(savedTheme);
    }

    btn.addEventListener("click", () => {
      const currentTheme = html.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
      applyTheme(currentTheme);
      localStorage.setItem("amarshop_admin_theme", currentTheme);
    });

    // Auto-Banish Timer
    function initAutoBanish() {
      const autoAlert = document.querySelector('.auto-banish-alert');
      if (autoAlert) {
        setTimeout(() => {
          const bsAlert = new bootstrap.Alert(autoAlert);
          bsAlert.close();
        }, 10000);
      }
    }
    initAutoBanish();

    // Reusable AJAX Form Submission Function
    function handleAjaxSubmit(event, url, modalId) {
      event.preventDefault();
      const form = event.target;
      const submitBtn = form.querySelector('.submit-btn');
      const originalBtnText = submitBtn.innerHTML;

      // 1. Disable submit button to prevent multiple submissions
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...';

      const formData = new FormData(form);

      // 2. Send AJAX Request
      fetch(url, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Hide Modal
          const modalElement = document.querySelector(modalId);
          const modalInstance = bootstrap.Modal.getInstance(modalElement);
          if (modalInstance) {
            modalInstance.hide();
          }

          // Enable Submit Button back
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;

          if (data.status === 'success') {
            // Page Refresh via Reload after setting Session State via Ajax execution
            window.location.reload();
          } else {
            alert(data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
          alert('An error occurred. Please try again.');
        });
    }
  </script>
</body>

</html>