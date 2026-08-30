// Handle Category Addition (POST)
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
  header("Location: products.php");
  exit;
}

$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);



<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
    <i class="bi bi-folder-plus me-1"></i> Add Category
</button>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="products.php" method="POST">
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