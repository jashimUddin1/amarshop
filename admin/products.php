<?php
require_once 'auth/config.php';

$page_title = "Manage Products";
$current_page = "products";



// Fetch Data
$products_stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Collection Map
$collection_map = [
  'new_arrivals'  => 'New Arrivals',
  'top_deals'     => 'Top Deals',
  'high_discount' => 'High Discount Products',
  'top_sales'     => 'Top Sales',
  'top_rated'     => 'Top Rated Products'
];

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center p-3 flex-shrink-0">
  <h2 class="h3 mb-0">Product Management</h2>
  <div class="d-flex gap-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
      <i class="bi bi-plus-circle me-1"></i> Add Product
    </button>
  </div>
</div>

<div id="alert-container" class="flex-shrink-0">
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

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">

      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">Image</th>
            <th class="bg-success text-white">Code</th>
            <th class="bg-success text-white">Name</th>
            <th class="bg-success text-white">Category</th>
            <th class="bg-success text-white">Purchase</th>
            <th class="bg-success text-white">Sell</th>
            <th class="bg-success text-white">Discount</th>
            <th class="bg-success text-white">Actual Price</th>
            <th class="bg-success text-white">Size</th>
            <th class="bg-success text-white">Color</th>
            <th class="bg-success text-white" title="Collection">Collec</th>
            <th class="bg-success text-white">Stock</th>
            <th class="bg-success text-white text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $row): ?>
              <tr id="product-row-<?= $row['id']; ?>">
                <td>
                  <img src="../uploads/<?= ($row['main_img'] ?: 'placeholder.jpg'); ?>" class="rounded object-fit-cover" style="width: 45px; height: 45px;" alt="Product">
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
                  if (!empty($row['color'])) {
                    // কমা (,) দিয়ে কালারগুলোকে আলাদা করে অ্যারেতে রূপান্তর করা
                    $colors = array_map('trim', explode(',', $row['color']));

                    if (count($colors) > 2) {
                      // প্রথম ২টি কালার নিয়ে কমা দিয়ে যুক্ত করা
                      $short_color = implode(', ', array_slice($colors, 0, 2)) . '...';
                      echo '<span title="' . htmlspecialchars($row['color']) . '">' . htmlspecialchars($short_color) . '</span>';
                    } else {
                      echo htmlspecialchars($row['color']);
                    }
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </td>
                <td>
                  <?php
                  $col_key = $row['collection_name'] ?? '';
                  $full_name = $collection_map[$col_key] ?? ($col_key ?: 'N/A');
                  $first_word = strtok($full_name, ' ');
                  echo htmlspecialchars($first_word);
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

                  <!-- Edit Modal -->
                  <div class="modal fade text-start" id="editProductModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <form id="editProductForm<?= $row['id']; ?>" enctype="multipart/form-data" onsubmit="handleAjaxSubmit(event, 'core/product_edit_core.php', '#editProductModal<?= $row['id']; ?>')">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Product - <?= htmlspecialchars($row['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
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

                              <div class="col-md-3">
                                <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= $row['purchase_price']; ?>" required>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label">Sell Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="sell_price" class="form-control" value="<?= $row['sell_price']; ?>" required>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label">Discount (%)</label>
                                <input type="number" step="0.01" name="discount_percent" class="form-control" value="<?= $row['discount_percent']; ?>">
                              </div>
                              <div class="col-md-3">
                                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" value="<?= $row['stock']; ?>" min="0" required>
                              </div>

                              <div class="col-md-4">
                                <label class="form-label">Size Options</label>
                                <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($row['size']); ?>">
                              </div>
                              <div class="col-md-4">
                                <label class="form-label">Product Color</label>
                                <input type="text" name="product_color" class="form-control" value="<?= htmlspecialchars($row['color'] ?? ''); ?>">
                              </div>
                              <div class="col-md-4">
                                <label class="form-label">Collection Name</label>
                                <select name="collection_name" class="form-select">
                                  <?php foreach ($collection_map as $key => $val): ?>
                                    <option value="<?= $key; ?>" <?= ($key === $row['collection_name']) ? 'selected' : ''; ?>><?= $val; ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>

                              <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($row['description']); ?></textarea>
                              </div>

                              <div class="col-md-6">
                                <label class="form-label">Main Image</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['main_img'])): ?>
                                    <img src="../uploads/<?= $row['main_img']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 45px; height: 45px;">
                                  <?php endif; ?>
                                  <input type="file" name="main_img" class="form-control" accept="image/*">
                                </div>
                              </div>

                              <div class="col-md-6">
                                <label class="form-label">Side Image 1</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['side_img1'])): ?>
                                    <img src="../uploads/<?= $row['side_img1']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 45px; height: 45px;">
                                  <?php endif; ?>
                                  <input type="file" name="side_img1" class="form-control" accept="image/*">
                                </div>
                              </div>

                              <!-- Side Image 2 -->
                              <div class="col-md-3">
                                <label class="form-label">Side Image 2</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['side_img2'])): ?>
                                    <img src="../uploads/<?= $row['side_img2']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 38px; height: 38px;">
                                  <?php endif; ?>
                                  <input type="file" name="side_img2" class="form-control" accept="image/*">
                                </div>
                              </div>

                              <!-- Side Image 3 -->
                              <div class="col-md-3">
                                <label class="form-label">Side Image 3</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['side_img3'])): ?>
                                    <img src="../uploads/<?= $row['side_img3']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 38px; height: 38px;">
                                  <?php endif; ?>
                                  <input type="file" name="side_img3" class="form-control" accept="image/*">
                                </div>
                              </div>

                              <!-- Side Image 4 -->
                              <div class="col-md-3">
                                <label class="form-label">Side Image 4</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['side_img4'])): ?>
                                    <img src="../uploads/<?= $row['side_img4']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 38px; height: 38px;">
                                  <?php endif; ?>
                                  <input type="file" name="side_img4" class="form-control" accept="image/*">
                                </div>
                              </div>

                              <!-- Side Image 5 -->
                              <div class="col-md-3">
                                <label class="form-label">Side Image 5</label>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['side_img5'])): ?>
                                    <img src="../uploads/<?= $row['side_img5']; ?>" class="img-thumbnail flex-shrink-0 object-fit-cover" style="width: 38px; height: 38px;">
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

                  <!-- Delete Modal -->
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
                            <p class="mb-0">Are you sure you want to delete <strong><?= htmlspecialchars($row['name']); ?></strong>?</p>
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
              <td colspan="13" class="text-center py-4 text-muted">No products found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- Add Product Modal -->
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

            <div class="col-md-3">
              <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" placeholder="320" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Sell Price <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="sell_price" class="form-control" placeholder="500" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Discount (%)</label>
              <input type="number" step="0.01" name="discount_percent" class="form-control" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
              <input type="number" name="stock" class="form-control" min="0" value="0" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Size Options</label>
              <input type="text" name="size" class="form-control" placeholder="S, M, L, XL">
            </div>
            <div class="col-md-4">
              <label class="form-label">Product Color</label>
              <input type="text" name="product_color" class="form-control" placeholder="Red, Blue">
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
              <textarea name="description" class="form-control" rows="2"></textarea>
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

<?php include_once 'includes/footer.php'; ?>