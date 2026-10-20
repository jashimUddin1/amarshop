<?php
require_once 'auth/config.php';
require_admin();

$page_title = "Sales History";
$current_page = "sales";

// Filter params (Fixed Key Name: order_status)
$status_filter = trim($_GET['order_status'] ?? '');
$search = trim($_GET['search'] ?? '');

// Build Query dynamically
$query = "SELECT * FROM orders WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND order_status = :order_status";
    $params[':order_status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (order_number LIKE :search OR fullname LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales = [];
}

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center my-3 px-2 flex-shrink-0 flex-wrap gap-2">
    <div>
        <h2 class="h3 mb-0">Sales & Orders History</h2>
        <p class="text-muted small mb-0">Manage customer orders and track order status.</p>
    </div>
</div>

<!-- Alert Display Container -->
<div id="alertContainer" class="px-2"></div>

<!-- Filter and Search Section -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="sales.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control"
                    placeholder="Search by Order ID, Name, or Phone..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select name="order_status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="sales.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Sales Data Table -->
<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
    <div class="card-body p-0 h-100">
        <div class="table-responsive h-100 overflow-y-auto">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success position-sticky top-0 z-2">
                    <tr>
                        <th class="bg-success text-white">Order ID</th>
                        <th class="bg-success text-white">Customer</th>
                        <th class="bg-success text-white">Phone</th>
                        <th class="bg-success text-white">Amount</th>
                        <th class="bg-success text-white">Status</th>
                        <th class="bg-success text-white">Date</th>
                        <th class="bg-success text-white text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sales)): ?>
                    <?php foreach ($sales as $sale): ?>
                    <tr id="sale-row-<?= $sale['id']; ?>">
                        <td class="fw-bold">#<?= htmlspecialchars($sale['order_number'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($sale['fullname'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($sale['phone'] ?? 'N/A'); ?></td>
                        <td class="fw-bold">৳<?= number_format((float)($sale['grand_total'] ?? 0), 2); ?></td>
                        <td>
                            <?php
                            $status = strtolower($sale['order_status'] ?? '');
                            $badgeClass = match($status) {
                                'completed' => 'bg-success',
                                'pending'   => 'bg-warning text-dark',
                                'cancelled' => 'bg-danger',
                                default     => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass; ?> text-capitalize" id="status-badge-<?= $sale['id']; ?>">
                                <?= htmlspecialchars($sale['order_status'] ?? ''); ?>
                            </span>
                        </td>
                        <td><?= !empty($sale['created_at']) ? date('d M Y, h:i A', strtotime($sale['created_at'])) : 'N/A'; ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-info me-1"
                                onclick='viewOrder(<?= htmlspecialchars(json_encode($sale, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, "UTF-8"); ?>)'>
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#updateStatusModal<?= $sale['id']; ?>">
                                <i class="bi bi-pencil-square"></i> Status
                            </button>
                        </td>
                    </tr>

                    <!-- Status Update Modal -->
                    <div class="modal fade" id="updateStatusModal<?= $sale['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                                <form onsubmit="updateStatus(event, <?= $sale['id']; ?>)">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Order #<?= htmlspecialchars($sale['order_number'] ?? ''); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="sale_id" value="<?= $sale['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Order Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="pending" <?= ($sale['order_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?= ($sale['order_status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?= ($sale['order_status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No sales orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Details Modal (Outside of table for better performance) -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderTitle">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th class="w-40 bg-light">Order ID</th>
                        <td id="viewOrderId"></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Customer Name</th>
                        <td id="viewCustomerName"></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Customer Phone</th>
                        <td id="viewCustomerPhone"></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Total Amount</th>
                        <td id="viewTotalAmount" class="fw-bold text-success"></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status</th>
                        <td id="viewStatus"></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Order Date</th>
                        <td id="viewOrderDate"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
    setTimeout(() => alertBox.remove(), 3500);
}

function viewOrder(sale) {
    if (!sale) return;

    document.getElementById('viewOrderId').innerText = '#' + (sale.order_number || '');
    document.getElementById('viewCustomerName').innerText = sale.fullname || 'N/A';
    document.getElementById('viewCustomerPhone').innerText = sale.phone || 'N/A';
    
    const amount = parseFloat(sale.grand_total || 0).toFixed(2);
    document.getElementById('viewTotalAmount').innerText = '৳' + amount;
    
    document.getElementById('viewStatus').innerText = (sale.order_status || '').toUpperCase();
    document.getElementById('viewOrderDate').innerText = sale.created_at || 'N/A';

    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    modal.show();
}

function updateStatus(event, id) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('core/sale_update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                const modalEl = document.getElementById(`updateStatusModal${id}`);
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        } catch (e) {
            console.error('Server Output:', text);
            showAlert('Server response error.', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('Network connection error.', 'danger');
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>