<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

$page_title = 'Process Orders';
$current_page = 'process';
$search = $_GET['search'] ?? '';
$search = is_string($search) ? trim($search) : '';
if (preg_match('/^#?([0-9]+)[A-Z]$/i', $search, $order_number_search)) {
    $search = $order_number_search[1];
}
$active_stage = $_GET['stage'] ?? 'new';
$active_stage = is_string($active_stage) ? $active_stage : 'new';
$stage_labels = [
    'new' => 'New',
    'confirmed' => 'Confirmed',
    'picked' => 'Picked',
    'packed' => 'Packed',
];
if (!isset($stage_labels[$active_stage])) {
    $active_stage = 'new';
}
$next_status = [
    'new' => 'confirmed',
    'confirmed' => 'picked',
    'picked' => 'packed',
    'packed' => 'packed_done',
];
$undo_status = [
    'confirmed' => 'processing',
    'picked' => 'confirmed',
    'packed' => 'picked',
];
$status_csrf_token = $_SESSION['order_status_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['order_status_csrf'] = $status_csrf_token;

$orders_by_stage = [
    'new' => [],
    'confirmed' => [],
    'picked' => [],
    'packed' => [],
];
$query = "
    SELECT o.*,
        (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
    FROM orders o
    WHERE LOWER(o.order_status) IN ('processing', 'pending', 'confirmed', 'picked', 'packed')
";
$params = [];
if ($search !== '') {
    $query .= ' AND (o.order_number LIKE :search_number OR o.fullname LIKE :search_name OR o.phone LIKE :search_phone)';
    $search_pattern = '%' . $search . '%';
    $params = [
        ':search_number' => $search_pattern,
        ':search_name' => $search_pattern,
        ':search_phone' => $search_pattern,
    ];
}
$query .= ' ORDER BY o.id ASC';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $order) {
        $status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
        $stage = $status === 'processing' ? 'new' : $status;
        if (isset($orders_by_stage[$stage])) {
            $orders_by_stage[$stage][] = $order;
        }
    }
} catch (PDOException $e) {
    error_log('Admin order processing queue could not be loaded: ' . $e->getMessage());
    $orders_load_error = 'Orders could not be loaded. Please try again.';
}

$orders = $orders_by_stage[$active_stage];
$stage_counts = array_map('count', $orders_by_stage);
$target_status = $next_status[$active_stage] ?? null;
$single_action_label = match ($target_status) {
    'confirmed' => 'Confirm',
    'picked' => 'Mark Picked',
    'packed' => 'Mark Packed',
    'packed_done' => 'Mark Packed Done',
    default => '',
};
$bulk_action_label = match ($target_status) {
    'confirmed' => 'Confirm Selected',
    'picked' => 'Mark Picked',
    'packed' => 'Mark Packed',
    'packed_done' => 'Mark Packed Done',
    default => '',
};
$undo_target_status = $undo_status[$active_stage] ?? null;
$current_order_status = $active_stage === 'new' ? 'processing' : $active_stage;

include_once 'includes/header.php';
?>

<div id="alertContainer" class="px-2"></div>
<!-- <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 py-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Order Processing</h1>
        <div class="text-muted">Process orders through confirmation, picking, and packing. Mark packed orders done to send them to Assign.</div>
    </div>
    <span class="badge text-bg-danger fs-6"><?= (int)$orders_to_pack_count; ?> to process</span>
</div> -->

<form method="get" class="row g-2 align-items-end px-2 pb-3 mt-2 flex-shrink-0">
    <input type="hidden" name="stage" value="<?= htmlspecialchars($active_stage, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="col">
        <label for="processOrderSearch" class="form-label mb-1">Find an order</label>
        <input type="search" class="form-control" id="processOrderSearch" name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Order ID, customer name, or phone">
    </div>
    <div class="col-auto d-flex gap-2">
        <button type="submit" class="btn btn-success"><i class="bi bi-search me-1"></i>Search</button>
        <?php if ($search !== ''): ?>
            <a href="process.php?stage=<?= rawurlencode($active_stage); ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<ul class="nav nav-tabs px-2 flex-shrink-0" role="tablist" aria-label="Order processing stages">
    <?php foreach ($stage_labels as $stage => $label): ?>
        <?php
        $tab_query = ['stage' => $stage];
        if ($search !== '') {
            $tab_query['search'] = $search;
        }
        ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $active_stage === $stage ? 'active' : ''; ?>"
                href="process.php?<?= htmlspecialchars(http_build_query($tab_query), ENT_QUOTES, 'UTF-8'); ?>"
                <?= $active_stage === $stage ? 'aria-current="page"' : ''; ?>>
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                <span class="badge text-bg-secondary ms-1"><?= (int)$stage_counts[$stage]; ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-2 py-2 flex-shrink-0">
    <div class="text-muted small"><?= htmlspecialchars($stage_labels[$active_stage], ENT_QUOTES, 'UTF-8'); ?> orders</div>
    <?php if ($target_status !== null): ?>
        <button type="button" class="btn btn-sm btn-primary" id="bulkAdvanceButton"
            data-target-status="<?= htmlspecialchars($target_status, ENT_QUOTES, 'UTF-8'); ?>"
            data-action-label="<?= htmlspecialchars($bulk_action_label, ENT_QUOTES, 'UTF-8'); ?>" disabled>
            <?= htmlspecialchars($bulk_action_label, ENT_QUOTES, 'UTF-8'); ?> (0)
        </button>
    <?php endif; ?>
    <?php if ($undo_target_status !== null): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkUndoButton"
            data-target-status="<?= htmlspecialchars($undo_target_status, ENT_QUOTES, 'UTF-8'); ?>"
            data-action-label="Undo Selected" disabled>
            Undo Selected (0)
        </button>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
    <div class="card-body p-0 h-100">
        <div class="table-responsive h-100 overflow-y-auto">
            <table class="table table-hover align-middle mb-0" id="processTable">
                <thead class="table-success position-sticky top-0 z-2">
                    <tr>
                        <?php if ($target_status !== null): ?>
                            <th class="bg-success text-white">
                                <input class="form-check-input" type="checkbox" id="selectAllOrders" aria-label="Select all orders on this tab">
                            </th>
                        <?php endif; ?>
                        <th class="bg-success text-white">Order</th>
                        <th class="bg-success text-white">Customer</th>
                        <th class="bg-success text-white">Items</th>
                        <th class="bg-success text-white">Amount</th>
                        <th class="bg-success text-white">Stage</th>
                        <th class="bg-success text-white">Received</th>
                        <th class="bg-success text-white text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php $status = normalize_order_status((string)($order['order_status'] ?? 'processing')); ?>
                            <tr>
                                <?php if ($target_status !== null): ?>
                                    <td>
                                        <input class="form-check-input order-select" type="checkbox" value="<?= (int)$order['id']; ?>"
                                            aria-label="Select order <?= htmlspecialchars((string)($order['order_number'] ?? $order['id']), ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                <?php endif; ?>
                                <td class="fw-bold">#<?= htmlspecialchars(order_shipment_number((string)($order['order_number'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($order['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($order['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td><?= (int)$order['item_count']; ?></td>
                                <td class="fw-bold">৳<?= number_format((float)($order['grand_total'] ?? 0), 0); ?></td>
                                <td><span class="badge text-bg-info"><?= htmlspecialchars(order_status_label($status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A'; ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-info" href="order_details.php?id=<?= (int)$order['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($target_status !== null): ?>
                                        <form class="d-inline" onsubmit="advanceOrder(event, this)">
                                            <input type="hidden" name="sale_id" value="<?= (int)$order['id']; ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars($target_status, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary ms-1">
                                                <?= htmlspecialchars($single_action_label, ENT_QUOTES, 'UTF-8'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($undo_target_status !== null): ?>
                                        <form class="d-inline" data-undo="true" onsubmit="advanceOrder(event, this)">
                                            <input type="hidden" name="sale_id" value="<?= (int)$order['id']; ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars($undo_target_status, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary ms-1">Undo</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 <?= !empty($orders_load_error) ? 'text-danger' : 'text-muted'; ?>">
                                <?= htmlspecialchars($orders_load_error ?? 'No orders in this stage.', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const orderStatusCsrfToken = <?= json_encode($status_csrf_token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const currentOrderStatus = <?= json_encode($current_order_status, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const selectAllOrders = document.getElementById('selectAllOrders');
const bulkAdvanceButton = document.getElementById('bulkAdvanceButton');
const bulkUndoButton = document.getElementById('bulkUndoButton');

function updateBulkSelection() {
    const checkboxes = Array.from(document.querySelectorAll('.order-select'));
    const selectedCount = checkboxes.filter(checkbox => checkbox.checked).length;
    if (bulkAdvanceButton) {
        bulkAdvanceButton.disabled = selectedCount === 0;
        bulkAdvanceButton.textContent = `${bulkAdvanceButton.dataset.actionLabel} (${selectedCount})`;
    }
    if (bulkUndoButton) {
        bulkUndoButton.disabled = selectedCount === 0;
        bulkUndoButton.textContent = `${bulkUndoButton.dataset.actionLabel} (${selectedCount})`;
    }
    if (selectAllOrders) {
        selectAllOrders.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
        selectAllOrders.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
    }
}

document.querySelectorAll('.order-select').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkSelection);
});
if (selectAllOrders) {
    selectAllOrders.addEventListener('change', () => {
        document.querySelectorAll('.order-select').forEach(checkbox => {
            checkbox.checked = selectAllOrders.checked;
        });
        updateBulkSelection();
    });
}

function showProcessError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.setAttribute('role', 'alert');
    alert.append(document.createTextNode(message));
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Close');
    alert.append(close);
    document.getElementById('alertContainer').append(alert);
}

function sendStatusRequest(url, formData, button, originalText) {
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';
    fetch(url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message || 'Order status could not be updated.');
            }
            window.location.reload();
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = originalText;
            showProcessError(error.message);
        });
}

function advanceOrder(event, form) {
    
    event.preventDefault();
    if (form.dataset.undo === 'true' && !window.confirm('Undo this order to the previous stage?')) return;
    sendStatusRequest('core/sale_update_status.php', new FormData(form), form.querySelector('button[type="submit"]'),
        form.querySelector('button[type="submit"]').innerHTML);
}

function advanceSelectedOrders(button) {
        const selectedIds = Array.from(document.querySelectorAll('.order-select:checked'))
            .map(checkbox => checkbox.value);
        if (!selectedIds.length) return;
        if (!window.confirm(`${button.dataset.actionLabel} for ${selectedIds.length} selected order(s)?`)) return;

        const formData = new FormData();
        formData.set('order_ids', selectedIds.join(','));
        formData.set('from_status', currentOrderStatus);
        formData.set('status', button.dataset.targetStatus);
        formData.set('csrf_token', orderStatusCsrfToken);
        sendStatusRequest('core/sale_update_status_bulk.php', formData, button, button.textContent);
}

if (bulkAdvanceButton) {
    bulkAdvanceButton.addEventListener('click', () => advanceSelectedOrders(bulkAdvanceButton));
}
if (bulkUndoButton) {
    bulkUndoButton.addEventListener('click', () => advanceSelectedOrders(bulkUndoButton));
}
</script>

<?php include_once 'includes/footer.php'; ?>
