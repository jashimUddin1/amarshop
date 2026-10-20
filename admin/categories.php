<?php
require_once 'auth/config.php';
require_admin();

$page_title = "Manage Categories";
$current_page = "category";

$categories_stmt =$pdo->query("SELECT * FROM categories ORDER BY id ASC");
$categories =$categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="my-4 px-2 flex-shrink-0">
  <h2 class="h3 mb-3 text-center">Category Management</h2>
  <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <input type="search" id="categorySearchInput" class="form-control" style="width: min(16rem, 100%);" placeholder="Search categories..." aria-label="Search categories" oninput="searchCategoryTable()">
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      Add Category
    </button>
  </div>
</div>

<!-- Dynamic Alert Display -->
<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show mx-2" role="alert">
    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show mx-2" role="alert">
    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
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
            <th class="bg-success text-white">Photo</th>
            <th class="bg-success text-white">Category Name</th>
            <th class="bg-success text-white">Sub Category / Info</th>
            <th class="bg-success text-white text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <?php $search_text = implode(' ', [$cat['id'], $cat['name'] ?? '', $cat['sub_category'] ?? '']); ?>
            <tr id="category-row-<?= $cat['id']; ?>" data-search="<?= htmlspecialchars($search_text, ENT_QUOTES); ?>">
              <td><?= $cat['id']; ?></td>
              <td>
                <img src="../uploads/categories/<?= htmlspecialchars($cat['category_photo'] ?: 'default-avatar.svg'); ?>" alt="<?= htmlspecialchars($cat['name']); ?> category" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
              </td>
              <td class="fw-bold"><?= htmlspecialchars($cat['name']); ?></td>
              <td><?= htmlspecialchars($cat['sub_category'] ?? 'N/A'); ?></td>
              <td class="text-end">
                <!-- Edit Button -->
                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id']; ?>">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
                <!-- Delete Button -->
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id']; ?>)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>

            <!-- Edit Category Modal -->
            <div class="modal fade" id="editCategoryModal<?= $cat['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content text-start">
                  <form id="editCategoryForm<?= $cat['id']; ?>" onsubmit="updateCategory(event, <?= $cat['id']; ?>)" enctype="multipart/form-data">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Category</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="category_id" value="<?= $cat['id']; ?>">
                      <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($cat['name']); ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Sub Category / Info</label>
                        <input type="text" name="sub_category" class="form-control" value="<?= htmlspecialchars($cat['sub_category'] ?? ''); ?>">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Category Photo</label>
                        <input type="file" name="category_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                        <img src="../uploads/categories/<?= htmlspecialchars($cat['category_photo'] ?: 'default-avatar.svg'); ?>" alt="Current category photo" class="mt-2 rounded" style="width: 64px; height: 64px; object-fit: cover;">
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
          <tr id="categorySearchEmpty" class="d-none">
            <td colspan="5" class="text-center text-muted py-4">No matching categories found.</td>
          </tr>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No categories found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCategoryForm" onsubmit="handleAjaxSubmit(event, 'core/category_add.php', '#addCategoryModal')" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="category_name" class="form-control" required placeholder="e.g. Electronics">
          </div>
          <div class="mb-3">
            <label class="form-label">Sub Category / Info</label>
            <input type="text" name="sub_category" class="form-control" placeholder="e.g. 240+ Items">
          </div>
          <div class="mb-3">
            <label for="newCategoryPhoto" class="form-label">Category Photo (Optional)</label>
            <input type="file" id="newCategoryPhoto" name="category_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif" onchange="previewCategoryPhoto(this)">
            <small class="form-text text-muted">JPG, PNG, WEBP, or GIF. No photo selected? The default avatar will be used.</small>
            <img id="newCategoryPhotoPreview" src="../uploads/categories/default-avatar.svg" alt="Default category avatar preview" class="mt-2 rounded" style="width: 80px; height: 80px; object-fit: cover;">
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

<!-- Scripts -->
<script>
 function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        fetch(`core/category_delete.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById(`category-row-${id}`).remove();
                    showAlert('Category deleted successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to delete category', 'danger');
                }
            })
            .catch(err => console.error(err));
    }
}

function showAlert(message, type) {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} alert-dismissible fade show mx-2 my-2`;
    alertBox.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    const container = document.querySelector('.d-flex.justify-content-between');
    container.parentNode.insertBefore(alertBox, container.nextSibling);
    setTimeout(() => { alertBox.remove(); }, 3000);
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
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong!');
    });
}
</script>
<script>
function previewCategoryPhoto(input) {
    const preview = document.getElementById('newCategoryPhotoPreview');
    if (input.files && input.files[0]) {
        preview.src = URL.createObjectURL(input.files[0]);
    } else {
        preview.src = '../uploads/categories/default-avatar.svg';
    }
}

function searchCategoryTable() {
    const input = document.getElementById('categorySearchInput');
    const categoryRows = document.querySelectorAll('tr[id^="category-row-"]');
    const emptyState = document.getElementById('categorySearchEmpty');
    const query = input.value.trim().toLocaleLowerCase();
    let visibleCount = 0;

    categoryRows.forEach(row => {
        const matches = (row.dataset.search || '').toLocaleLowerCase().includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount > 0 || categoryRows.length === 0);
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>