<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

$page_title = "Orders";
$current_page = "orders";

$status_filter = $_GET['order_status'] ?? 'all';
$status_filter = is_string($status_filter) ? strtolower(trim($status_filter)) : 'all';
$search = $_GET['search'] ?? '';
$search = is_string($search) ? trim($search) : '';
$order_statuses = [
    'processing' => 'Processing',
    'confirmed' => 'Confirmed',
    'picked' => 'Picked',
    'packed' => 'Packed',
    'delivering' => 'Delivering',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$order_status_transitions = [
    'processing' => ['confirmed', 'cancelled'],
    'confirmed' => ['picked', 'processing', 'cancelled'],
    'picked' => ['packed', 'confirmed', 'cancelled'],
    'packed' => ['delivering', 'picked', 'cancelled'],
    'delivering' => ['delivered'],
    'delivered' => [],
    'cancelled' => [],
];
$backward_transitions = [
    'confirmed' => 'processing',
    'picked' => 'confirmed',
    'packed' => 'picked',
];
$status_csrf_token = $_SESSION['order_status_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['order_status_csrf'] = $status_csrf_token;

if ($status_filter !== 'all' && !array_key_exists($status_filter, $order_statuses)) {
    $status_filter = 'all';
}

// Build Query dynamically
$query = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    if ($status_filter === 'delivered') {
        $query .= " AND LOWER(order_status) IN ('delivered', 'completed')";
    } elseif ($status_filter === 'processing') {
        $query .= " AND LOWER(order_status) IN ('processing', 'pending')";
    } elseif ($status_filter === 'delivering') {
        $query .= " AND LOWER(order_status) IN ('delivering', 'shipped')";
    } else {
        $query .= " AND LOWER(order_status) = :order_status";
        $params[':order_status'] = $status_filter;
    }
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
    error_log('Admin orders could not be loaded: ' . $e->getMessage());
    $sales = [];
    $sales_load_error = 'Orders could not be loaded. Please try again.';
}

include_once 'includes/header.php';
?>

<!-- Alert Display Container -->
<div id="alertContainer" class="px-2"></div>

<form method="get" class="row g-2 align-items-end px-2 py-3 flex-shrink-0">
    <div class="col-12 col-md">
        <label for="orderSearch" class="form-label mb-1">Search orders</label>
        <input type="search" class="form-control" id="orderSearch" name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Order ID, customer name, or phone">
    </div>
    <div class="col-12 col-md-4">
        <label for="orderStatus" class="form-label mb-1">Status</label>
        <select class="form-select" id="orderStatus" name="order_status">
            <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="processing" <?= $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
            <?php foreach ($order_statuses as $status_value => $status_label): ?>
                <?php if ($status_value !== 'processing'): ?>
                    <option value="<?= htmlspecialchars($status_value, ENT_QUOTES, 'UTF-8'); ?>" <?= $status_filter === $status_value ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-auto d-flex gap-2">
        <button type="submit" class="btn btn-success"><i class="bi bi-search me-1"></i>Search</button>
        <a href="sales.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

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
                        <td class="fw-bold">৳<?= number_format((float)($sale['grand_total'] ?? 0), 0); ?></td>
                        <td>
                            <?php
                            $status = normalize_order_status((string)($sale['order_status'] ?? 'processing'));
                            $available_statuses = array_merge(
                                [$status => $order_statuses[$status] ?? ucfirst($status)],
                                array_intersect_key(
                                    $order_statuses,
                                    array_flip($order_status_transitions[$status] ?? [])
                                )
                            );
                            $badgeClass = match($status) {
                                'delivered' => 'bg-success',
                                'delivering' => 'bg-primary',
                                'packed', 'picked' => 'bg-info text-dark',
                                'processing' => 'bg-info text-dark',
                                'confirmed' => 'bg-secondary',
                                'cancelled' => 'bg-danger',
                                default     => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass; ?> text-capitalize" id="status-badge-<?= $sale['id']; ?>">
                                <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td><?= !empty($sale['created_at']) ? date('d M Y, h:i A', strtotime($sale['created_at'])) : 'N/A'; ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info me-1"
                                href="order_details.php?id=<?= (int)$sale['id']; ?>">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <?php if (!empty($order_status_transitions[$status])): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#updateStatusModal<?= $sale['id']; ?>">
                                    <i class="bi bi-arrow-repeat"></i> Update
                                </button>
                            <?php else: ?>
                                <span class="text-muted small"><?= $status === 'cancelled' ? 'Closed' : 'Complete'; ?></span>
                            <?php endif; ?>
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
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Order Status</label>
                                            <select name="status" class="form-select" required>
                                                <?php foreach ($available_statuses as $status_value => $status_label): ?>
                                                    <?php
                                                    $is_undo = ($backward_transitions[$status] ?? null) === $status_value;
                                                    $is_cancel = $status_value === 'cancelled';
                                                    $prefix = $status === $status_value ? '' : ($is_undo ? 'Undo to ' : ($is_cancel ? 'Cancel: ' : 'Set to '));
                                                    ?>
                                                    <option value="<?= htmlspecialchars($status_value, ENT_QUOTES, 'UTF-8'); ?>" data-undo="<?= $is_undo ? 'true' : 'false'; ?>" <?= $status === $status_value ? 'selected' : ''; ?>>
                                                        <?= $prefix . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
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
                        <?php if (!empty($sales_load_error)): ?>
                            <td colspan="7" class="text-center py-4 text-danger"><?= htmlspecialchars($sales_load_error, ENT_QUOTES, 'UTF-8'); ?></td>
                        <?php else: ?>
                        <td colspan="7" class="text-center py-4 text-muted">No sales orders found.</td>
                        <?php endif; ?>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

function updateStatus(event, id) {
    event.preventDefault();
    const form = event.target;
    if (form.elements.status.selectedOptions[0]?.dataset.undo === 'true' &&
        !window.confirm('Undo this order to the previous status?')) {
        return;
    }
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