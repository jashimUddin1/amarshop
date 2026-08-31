<?php
require_once 'auth/config.php';

$page_title = "Manage Categories";
$current_page = "category";

$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center my-4 px-2 flex-shrink-0">
  <h2 class="h3 mb-0">Category Management</h2>
  <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
    Add Category
  </button>
</div>

<!-- Dynamic Alert Display (Session Alert Fix) -->
<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show mx-2" role="alert">
    <?= $_SESSION['success'];
    unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show mx-2" role="alert">
    <?= $_SESSION['error'];
    unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

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
            <tr id="category-row-<?= $cat['id']; ?>">
              <td><?= $cat['id']; ?></td>
              <td class="fw-bold"><?= htmlspecialchars($cat['name']); ?></td>
              <td class="text-end">
                <!-- Edit Button -->
                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id']; ?>">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
                <!-- Ajax Delete Button -->
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id']; ?>)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>

            <!-- Edit Category Modal -->
            <div class="modal fade" id="editCategoryModal<?= $cat['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content text-start">
                  <form id="editCategoryForm<?= $cat['id']; ?>" onsubmit="updateCategory(event, <?= $cat['id']; ?>)">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Category</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="category_id" value="<?= $cat['id']; ?>">
                      <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="cat_name_<?= $cat['id']; ?>" class="form-control" value="<?= htmlspecialchars($cat['name']); ?>" required>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-warning submit-btn">Update Category</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCategoryForm" onsubmit="handleAjaxSubmit(event, 'core/category_add.php', '#addCategoryModal')">
        <div class="modal-header">
          <h5 class="modal-title">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="category_name" class="form-control" required placeholder="e.g. Electronics">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary submit-btn">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Script -->
<script>
 function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        fetch(`core/category_delete.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // ১. টেবিল থেকে রো মুছে ফেলা
                    document.getElementById(`category-row-${id}`).remove();

                    // ২. পেজ রিলোড ছাড়া সরাসরি স্ক্রিনে অ্যালার্ট মেসেজ দেখানো
                    showAlert('Category deleted successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to delete category', 'danger');
                }
            })
            .catch(err => console.error(err));
    }
}

// স্ক্রিনে লাইভ অ্যালার্ট দেখানোর ফাংশন
function showAlert(message, type) {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} alert-dismissible fade show mx-2 my-2`;
    alertBox.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // কার্ডের বা কনটেইনারের একদম উপরে মেসেজ বসানো
    const container = document.querySelector('.d-flex.justify-content-between');
    container.parentNode.insertBefore(alertBox, container.nextSibling);

    // ৩ সেকেন্ড পর স্বয়ংক্রিয়ভাবে মুছে যাবে
    setTimeout(() => {
        alertBox.remove();
    }, 3000);
}

function updateCategory(event, id) {
    event.preventDefault();

    const form = document.getElementById(`editCategoryForm${id}`);
    const formData = new FormData(form);

    fetch('core/category_edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            location.reload(); // সফল হলে পেজ রিলোড হবে
        } else {
            alert(data.message); // ভুল থাকলে (যেমন: Duplicate Name) অ্যালার্ট দেবে
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong!');
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>