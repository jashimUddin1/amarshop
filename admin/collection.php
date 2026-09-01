<?php
require_once 'auth/config.php';

$page_title = "Manage Collections";
$current_page = "collection";

// Database থেকে Collections নিয়ে আসা
$collections_stmt = $pdo->query("SELECT * FROM collections ORDER BY id DESC");
$collections = $collections_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center my-4 px-2 flex-shrink-0">
  <h2 class="h3 mb-0">Product Collections</h2>
  <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCollectionModal">
    <i class="bi bi-plus-lg"></i> Add Collection
  </button>
</div>

<!-- Dynamic Alert Display -->
<div id="alertContainer" class="px-2">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
  <div class="card-body p-0 h-100">
    <div class="table-responsive h-100 overflow-y-auto">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-success position-sticky top-0 z-2">
          <tr>
            <th class="bg-success text-white">ID</th>
            <th class="bg-success text-white">Title</th>
            <th class="bg-success text-white">Slug / Key</th>
            <th class="bg-success text-white">Badge</th>
            <th class="bg-success text-white">Badge BG</th>
            <th class="bg-success text-white">Percentage BG</th>
            <th class="bg-success text-white text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($collections as $col): ?>
            <tr id="collection-row-<?= $col['id']; ?>">
              <td><?= $col['id']; ?></td>
              <td class="fw-bold"><?= htmlspecialchars($col['title'] ?? $col['display_name'] ?? ''); ?></td>
              <td><code><?= htmlspecialchars($col['slug'] ?? $col['collection_key'] ?? ''); ?></code></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($col['badge'] ?? 'N/A'); ?></span></td>
              <td><code><?= htmlspecialchars($col['badge_bg'] ?? 'bg-danger'); ?></code></td>
              <td><code><?= htmlspecialchars($col['percentage_bg'] ?? 'bg-dark'); ?></code></td>
              <td class="text-end">
                <!-- Edit Button -->
                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editCollectionModal<?= $col['id']; ?>">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
                <!-- Delete Button -->
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCollection(<?= $col['id']; ?>)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>

            <!-- Edit Collection Modal -->
            <div class="modal fade" id="editCollectionModal<?= $col['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content text-start">
                  <form id="editCollectionForm<?= $col['id']; ?>" onsubmit="updateCollection(event, <?= $col['id']; ?>)">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Collection</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="collection_id" value="<?= $col['id']; ?>">
                      
                      <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($col['title'] ?? $col['display_name'] ?? ''); ?>" required placeholder="e.g. New Arrivals">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Slug / Collection Key <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($col['slug'] ?? $col['collection_key'] ?? ''); ?>" required placeholder="e.g. new_arrivals">
                        <small class="text-muted">Unique identifier (use lowercase & underscores)</small>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Badge Text</label>
                        <input type="text" name="badge" class="form-control" value="<?= htmlspecialchars($col['badge'] ?? ''); ?>" placeholder="e.g. New / Hot / Sale">
                      </div>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Badge Background</label>
                          <select name="badge_bg" class="form-select">
                            <option value="bg-danger" <?= ($col['badge_bg'] ?? '') === 'bg-danger' ? 'selected' : ''; ?>>Red (bg-danger)</option>
                            <option value="bg-primary" <?= ($col['badge_bg'] ?? '') === 'bg-primary' ? 'selected' : ''; ?>>Blue (bg-primary)</option>
                            <option value="bg-success" <?= ($col['badge_bg'] ?? '') === 'bg-success' ? 'selected' : ''; ?>>Green (bg-success)</option>
                            <option value="bg-warning text-dark" <?= ($col['badge_bg'] ?? '') === 'bg-warning text-dark' ? 'selected' : ''; ?>>Yellow (bg-warning)</option>
                            <option value="bg-dark" <?= ($col['badge_bg'] ?? '') === 'bg-dark' ? 'selected' : ''; ?>>Dark (bg-dark)</option>
                          </select>
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Percentage Background</label>
                          <select name="percentage_bg" class="form-select">
                            <option value="bg-dark" <?= ($col['percentage_bg'] ?? '') === 'bg-dark' ? 'selected' : ''; ?>>Dark (bg-dark)</option>
                            <option value="bg-danger" <?= ($col['percentage_bg'] ?? '') === 'bg-danger' ? 'selected' : ''; ?>>Red (bg-danger)</option>
                            <option value="bg-primary" <?= ($col['percentage_bg'] ?? '') === 'bg-primary' ? 'selected' : ''; ?>>Blue (bg-primary)</option>
                            <option value="bg-success" <?= ($col['percentage_bg'] ?? '') === 'bg-success' ? 'selected' : ''; ?>>Green (bg-success)</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-warning submit-btn">Update Collection</button>
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

<!-- Add Collection Modal -->
<div class="modal fade" id="addCollectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCollectionForm" onsubmit="addCollection(event)">
        <div class="modal-header">
          <h5 class="modal-title">Add Collection</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. New Arrivals">
          </div>

          <div class="mb-3">
            <label class="form-label">Slug / Collection Key <span class="text-danger">*</span></label>
            <input type="text" name="slug" class="form-control" required placeholder="e.g. new_arrivals">
            <small class="text-muted">Unique key (use lowercase & underscores)</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Badge Text</label>
            <input type="text" name="badge" class="form-control" placeholder="e.g. New / Hot / Sale">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Badge Background</label>
              <select name="badge_bg" class="form-select">
                <option value="bg-danger" selected>Red (bg-danger)</option>
                <option value="bg-primary">Blue (bg-primary)</option>
                <option value="bg-success">Green (bg-success)</option>
                <option value="bg-warning text-dark">Yellow (bg-warning)</option>
                <option value="bg-dark">Dark (bg-dark)</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Percentage Background</label>
              <select name="percentage_bg" class="form-select">
                <option value="bg-dark" selected>Dark (bg-dark)</option>
                <option value="bg-danger">Red (bg-danger)</option>
                <option value="bg-primary">Blue (bg-primary)</option>
                <option value="bg-success">Green (bg-success)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary submit-btn">Save Collection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function showAlert(message, type = 'success') {
  const container = document.getElementById('alertContainer');
  const alertBox = document.createElement('div');
  alertBox.className = `alert alert-${type} alert-dismissible fade show`;
  alertBox.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  container.appendChild(alertBox);

  setTimeout(() => {
    alertBox.remove();
  }, 3500);
}

// Add Collection AJAX
function addCollection(event) {
  event.preventDefault();
  const form = document.getElementById('addCollectionForm');
  const formData = new FormData(form);

  fetch('core/collection_add.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(text => {
    try {
      const data = JSON.parse(text);
      if (data.status === 'success') {
        location.reload();
      } else {
        showAlert(data.message, 'danger');
      }
    } catch (e) {
      console.error('Server Output:', text);
      showAlert('Server error occurred. Check console for details.', 'danger');
    }
  })
  .catch(err => {
    console.error(err);
    showAlert('Network error or connection failed.', 'danger');
  });
}

// Update Collection AJAX
function updateCollection(event, id) {
  event.preventDefault();
  const form = document.getElementById(`editCollectionForm${id}`);
  const formData = new FormData(form);

  fetch('core/collection_edit.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(text => {
    try {
      const data = JSON.parse(text);
      if (data.status === 'success') {
        location.reload();
      } else {
        showAlert(data.message, 'danger');
      }
    } catch (e) {
      console.error('Server Output:', text);
      showAlert('Server error occurred while updating.', 'danger');
    }
  })
  .catch(err => {
    console.error(err);
    showAlert('Network error or connection failed.', 'danger');
  });
}

// Delete Collection AJAX
function deleteCollection(id) {
  if (confirm('Are you sure you want to delete this collection?')) {
    fetch(`core/collection_delete.php?id=${id}`)
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const row = document.getElementById(`collection-row-${id}`);
          if (row) row.remove();
          showAlert(data.message, 'success');
        } else {
          showAlert(data.message || 'Failed to delete collection', 'danger');
        }
      })
      .catch(err => console.error(err));
  }
}
</script>

<?php include_once 'includes/footer.php'; ?>
