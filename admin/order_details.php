<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_once __DIR__ . '/../core/order_admin.php';
require_admin();

$order_id = $_GET['id'] ?? '';
if (!is_string($order_id) || filter_var($order_id, FILTER_VALIDATE_INT) === false || (int)$order_id <= 0) {
    http_response_code(400);
    exit('Invalid order ID.');
}

ensure_order_status_history_table($pdo);
ensure_order_delivery_assignments_table($pdo);
ensure_order_issues_table($pdo);
$status_csrf_token = $_SESSION['order_status_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['order_status_csrf'] = $status_csrf_token;

$order_stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$order_stmt->execute([':id' => (int)$order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.main_img
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
    ORDER BY oi.id ASC
");
$items_stmt->execute([':order_id' => (int)$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
$issues_stmt = $pdo->prepare('SELECT id, issue_type, message, status, shipment_number, shipment_status, admin_note, created_by_admin_id, created_at FROM order_issues WHERE order_id = :order_id ORDER BY created_at DESC, id DESC');
$issues_stmt->execute([':order_id' => (int)$order_id]);
$order_issues = $issues_stmt->fetchAll(PDO::FETCH_ASSOC);
$issue_items_by_id = [];
$issue_assignments_by_id = [];
if ($order_issues) {
    foreach ($order_issues as $issue_index => $issue) {
        if (empty($issue['shipment_number'])) {
            $order_issues[$issue_index]['shipment_number'] = order_shipment_number((string)$order['order_number'], issue_shipment_suffix($issue_index + 1));
        }
    }
    $issue_ids = array_map(static fn(array $issue): int => (int)$issue['id'], $order_issues);
    $issue_placeholders = implode(',', array_fill(0, count($issue_ids), '?'));
    $issue_items_stmt = $pdo->prepare("SELECT * FROM order_issue_items WHERE issue_id IN ($issue_placeholders) ORDER BY id ASC");
    $issue_items_stmt->execute($issue_ids);
    foreach ($issue_items_stmt->fetchAll(PDO::FETCH_ASSOC) as $issue_item) {
        $issue_items_by_id[(int)$issue_item['issue_id']][] = $issue_item;
    }
    $issue_assignments_stmt = $pdo->prepare("SELECT * FROM order_issue_delivery_assignments WHERE issue_id IN ($issue_placeholders)");
    $issue_assignments_stmt->execute($issue_ids);
    foreach ($issue_assignments_stmt->fetchAll(PDO::FETCH_ASSOC) as $issue_assignment) {
        $issue_assignments_by_id[(int)$issue_assignment['issue_id']] = $issue_assignment;
    }
}
$timeline = get_order_status_timeline($pdo, $order);
$assignment_stmt = $pdo->prepare('SELECT * FROM order_delivery_assignments WHERE order_id = :order_id');
$assignment_stmt->execute([':order_id' => (int)$order_id]);
$assignment = $assignment_stmt->fetch(PDO::FETCH_ASSOC);
$deliverymen_stmt = $pdo->query("SELECT id, customar_name, phone FROM deliveryman WHERE status = 'active' ORDER BY customar_name ASC");
$deliverymen = $deliverymen_stmt->fetchAll(PDO::FETCH_ASSOC);
$status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
$can_edit = !in_array($status, ['cancelled', 'delivered'], true);
$can_edit_items = in_array($status, ['processing', 'confirmed', 'picked', 'packed', 'packed_done'], true);
$status_transitions = [
    'processing' => ['confirmed', 'cancelled'],
    'confirmed' => ['picked', 'processing', 'cancelled'],
    'picked' => ['packed', 'confirmed', 'cancelled'],
    'packed' => ['packed_done', 'picked', 'cancelled'],
    'packed_done' => ['delivering', 'cancelled'],
    'delivering' => ['delivered'],
];
$available_statuses = $status_transitions[$status] ?? [];
$backward_transitions = [
    'confirmed' => 'processing',
    'picked' => 'confirmed',
    'packed' => 'picked',
];

$page_title = 'Order Details';
$current_page = 'orders';
include_once 'includes/header.php';

$timeline_descriptions = [
    'placed' => 'The order was successfully placed.',
    'processing' => 'We have received the order and it is being processed.',
    'confirmed' => 'The order has been confirmed.',
    'picked' => 'The items have been picked for packing.',
    'packed' => 'The order has been packed.',
    'packed_done' => 'Packing is complete. The order is ready for delivery.',
    'inbag' => 'The order has been placed in a delivery bag and awaits rider delivery.',
    'delivering' => 'The order is on its way to the customer.',
    'delivered' => 'The order has been delivered.',
    'cancelled' => 'The order was cancelled.',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div>
        <a href="sales.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
        <h1 class="h4 fw-bold mb-0">Order #<?= htmlspecialchars(order_shipment_number((string)($order['order_number'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge text-bg-primary fs-6"><?= htmlspecialchars(order_status_label($status), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($available_statuses): ?>
            <form class="js-order-status-form d-flex gap-2 align-items-center">
                <input type="hidden" name="sale_id" value="<?= (int)$order['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <select name="status" class="form-select form-select-sm" aria-label="Update order status">
                    <?php foreach ($available_statuses as $next_status): ?>
                        <?php
                        $is_undo = ($backward_transitions[$status] ?? null) === $next_status;
                        $is_cancel = $next_status === 'cancelled';
                        $label_prefix = $is_undo ? 'Undo to ' : ($is_cancel ? 'Cancel: ' : 'Set to ');
                        ?>
                        <option value="<?= htmlspecialchars($next_status, ENT_QUOTES, 'UTF-8'); ?>" data-undo="<?= $is_undo ? 'true' : 'false'; ?>">
                            <?= $label_prefix . htmlspecialchars(order_status_label($next_status), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">Update Status</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="overflow-y-auto px-2 pb-3">
    <div id="orderAlert" aria-live="polite"></div>
    <section class="card shadow-sm mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-clock-history me-2"></i>Order Timeline</div>
        <div class="card-body">
            <ol class="list-unstyled mb-0">
                <?php foreach ($timeline as $event): ?>
                    <?php $event_status = normalize_order_status((string)$event['status']); ?>
                    <li class="d-flex gap-3 pb-4">
                        <div class="text-nowrap small text-muted" style="min-width: 140px;">
                            <?= !empty($event['created_at']) ? date('d M Y, h:i A', strtotime($event['created_at'])) : 'Time not recorded'; ?>
                        </div>
                        <div class="border-start border-2 border-success ps-3 position-relative">
                            <span class="position-absolute top-0 start-0 translate-middle-x badge rounded-pill text-bg-success"><i class="bi bi-check"></i></span>
                            <div class="fw-semibold"><?= htmlspecialchars(order_status_label($event_status), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($timeline_descriptions[$event_status] ?? 'Order status updated.', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($event['admin_name'])): ?>
                                <div class="small text-muted"><i class="bi bi-person-check me-1"></i>Updated by <?= htmlspecialchars($event['admin_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <?php if ($assignment || $status === 'packed_done'): ?>
        <section class="card shadow-sm mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-truck me-2"></i>Assign Delivery</div>
            <div class="card-body">
                <?php if ($assignment): ?>
                    <div class="alert alert-success py-2">
                        Assigned to <strong><?= htmlspecialchars($assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        (<?= htmlspecialchars(ucfirst($assignment['delivery_type']), ENT_QUOTES, 'UTF-8'); ?>)
                        <?php if ($assignment['contact_name'] !== ''): ?> · <?= htmlspecialchars($assignment['contact_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                        <?php if ($assignment['contact_phone'] !== ''): ?> · <?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                        <?php if ($assignment['tracking_number'] !== ''): ?> · Tracking: <?= htmlspecialchars($assignment['tracking_number'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($status === 'packed_done'): ?>
                <form id="deliveryAssignmentForm" class="row g-2">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="col-12 col-md-3">
                        <label class="form-label small" for="deliveryType">Delivery type</label>
                        <select class="form-select" name="delivery_type" id="deliveryType" required>
                            <option value="rider" <?= ($assignment['delivery_type'] ?? '') === 'rider' ? 'selected' : ''; ?>>In-house rider</option>
                            <option value="courier" <?= ($assignment['delivery_type'] ?? '') === 'courier' ? 'selected' : ''; ?>>Third-party courier</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small" for="deliverymanSelect">Rider / courier name</label>
                        <input type="hidden" id="providerName" name="provider_name" value="<?= htmlspecialchars($assignment['provider_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <div id="riderSelectField">
                            <select class="form-select" id="deliverymanSelect">
                                <option value="">Choose a deliveryman</option>
                                <?php if ($assignment && $assignment['delivery_type'] === 'rider' && !in_array($assignment['provider_name'], array_column($deliverymen, 'customar_name'), true)): ?>
                                    <option value="" data-name="<?= htmlspecialchars($assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?>" data-phone="<?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?> (current assignment)</option>
                                <?php endif; ?>
                                <?php foreach ($deliverymen as $deliveryman): ?>
                                    <option value="<?= (int)$deliveryman['id']; ?>" data-name="<?= htmlspecialchars($deliveryman['customar_name'], ENT_QUOTES, 'UTF-8'); ?>" data-phone="<?= htmlspecialchars($deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?>" <?= ($assignment['delivery_type'] ?? '') === 'rider' && $assignment['provider_name'] === $deliveryman['customar_name'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($deliveryman['customar_name'], ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!$deliverymen): ?><div class="form-text">Add an active deliveryman before assigning an in-house rider.</div><?php endif; ?>
                        </div>
                        <div id="courierNameField" class="d-none">
                            <input class="form-control" id="courierName" value="<?= htmlspecialchars(($assignment['delivery_type'] ?? '') === 'courier' ? $assignment['provider_name'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Courier name">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small" for="contactName">Contact person (optional)</label>
                        <input class="form-control" id="contactName" name="contact_name" value="<?= htmlspecialchars($assignment['contact_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small" for="contactPhone">Phone (optional)</label>
                        <input class="form-control" id="contactPhone" name="contact_phone" value="<?= htmlspecialchars($assignment['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small" for="trackingNumber">Tracking / consignment number</label>
                        <input class="form-control" id="trackingNumber" name="tracking_number" value="<?= htmlspecialchars($assignment['tracking_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small" for="deliveryNotes">Notes</label>
                        <input class="form-control" id="deliveryNotes" name="notes" value="<?= htmlspecialchars($assignment['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12"><button class="btn btn-success" type="submit"><?= $assignment ? 'Update Assignment' : 'Assign Delivery'; ?></button></div>
                </form>
                <?php if (in_array('delivering', $available_statuses, true)): ?>
                    <div class="form-text mt-2">Save the assignment before changing the order status to Delivering.</div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($status === 'delivered' || $order_issues): ?>
        <section class="card shadow-sm mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Order Issues</div>
            <?php if ($order_issues): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($order_issues as $issue): ?>
                        <?php
                        $issue_id = (int)$issue['id'];
                        $issue_number = $issue['shipment_number'];
                        $issue_assignment = $issue_assignments_by_id[$issue_id] ?? null;
                        $shipment_next = match ($issue['shipment_status']) {
                            'pending' => array_merge(['packed'], $issue_assignment ? ['delivering'] : [], ['cancelled']),
                            'packed' => array_merge($issue_assignment ? ['delivering'] : [], ['pending', 'cancelled']),
                            'delivering' => ['delivered'],
                            default => [],
                        };
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <strong><?= htmlspecialchars(match ($issue['issue_type']) { 'return' => 'Return issue', 'missing' => 'Missing item', 'replacement' => 'Replacement', default => ucfirst($issue['issue_type']) }, ENT_QUOTES, 'UTF-8'); ?> · Shipment #<?= htmlspecialchars($issue_number, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="badge <?= $issue['status'] === 'resolved' ? 'text-bg-success' : ($issue['status'] === 'rejected' ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?= htmlspecialchars(ucfirst($issue['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="small mt-1"><?= nl2br(htmlspecialchars($issue['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                            <?php foreach ($issue_items_by_id[$issue_id] ?? [] as $issue_item): ?>
                                <div class="small text-muted mt-1"><strong><?= $issue_item['direction'] === 'give' ? 'Deliver (দিবেন)' : 'Collect (আনবেন)'; ?>:</strong>
                                    <?= htmlspecialchars($issue_item['product_name'] . ' · ' . $issue_item['product_code'] . ($issue_item['size'] !== '' ? ' · ' . $issue_item['size'] : '') . ' · Qty ' . $issue_item['quantity'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="small text-muted mt-1"><?= !empty($issue['created_by_admin_id']) ? 'Created by admin' : 'Previously reported'; ?></div>
                            <?php if (!empty($issue['admin_note'])): ?><div class="small text-muted mt-1">Admin note: <?= nl2br(htmlspecialchars($issue['admin_note'], ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
                            <small class="text-muted"><?= !empty($issue['created_at']) ? date('d M Y, h:i A', strtotime($issue['created_at'])) : ''; ?></small>
                            <?php if ($issue['status'] !== 'rejected' && !in_array($issue['shipment_status'], ['delivered', 'cancelled'], true)): ?>
                                <div class="row g-2 mt-2">
                                    <?php if (in_array($issue['shipment_status'], ['pending', 'packed'], true)): ?>
                                        <div class="col-12 col-lg-6">
                                            <form class="js-issue-assignment-form row g-2">
                                                <input type="hidden" name="issue_id" value="<?= $issue_id; ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <div class="col-12"><label class="form-label small mb-1">Issue shipment deliveryman</label>
                                                    <select class="form-select form-select-sm" name="deliveryman_id" required>
                                                        <option value="">Select deliveryman</option>
                                                        <?php foreach ($deliverymen as $deliveryman): ?>
                                                            <option value="<?= (int)$deliveryman['id']; ?>" <?= (int)($issue_assignment['deliveryman_id'] ?? 0) === (int)$deliveryman['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($deliveryman['customar_name'] . ' · ' . $deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12"><input class="form-control form-control-sm" name="tracking_number" maxlength="120" placeholder="Tracking number" value="<?= htmlspecialchars($issue_assignment['tracking_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                                                <div class="col-12"><input class="form-control form-control-sm" name="notes" maxlength="2000" placeholder="Delivery notes" value="<?= htmlspecialchars($issue_assignment['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                                                <div class="col-12"><button class="btn btn-sm btn-outline-success" type="submit">Save assignment</button></div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-12 col-lg-6">
                                        <?php if ($shipment_next): ?>
                                            <form class="js-issue-status-form d-flex gap-2 align-items-end">
                                                <input type="hidden" name="issue_id" value="<?= $issue_id; ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <div class="flex-grow-1"><label class="form-label small mb-1">Shipment status (<?= htmlspecialchars(ucfirst($issue['shipment_status']), ENT_QUOTES, 'UTF-8'); ?>)</label>
                                                    <select class="form-select form-select-sm" name="shipment_status">
                                                        <?php foreach ($shipment_next as $next_shipment_status): ?><option value="<?= htmlspecialchars($next_shipment_status, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($next_shipment_status === 'cancelled' ? 'Cancel issue' : ucfirst($next_shipment_status), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($status === 'delivered'): ?>
                <div class="card-body">
                    <form id="orderIssueForm" class="row g-2">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="orderIssueType">Issue type</label>
                            <select class="form-select" id="orderIssueType" name="issue_type" required>
                                <option value="return">Return issue</option>
                                <option value="missing">Missing item</option>
                                <option value="replacement">Replacement</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="issueOrderItem">Order product</label>
                            <select class="form-select" id="issueOrderItem" name="order_item_id" required>
                                <option value="">Select product from this order</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= (int)$item['id']; ?>" data-quantity="<?= (int)$item['quantity']; ?>"><?= htmlspecialchars(($item['name'] ?? $item['product_code'] ?? 'Product') . ' · ' . $item['product_code'] . ($item['size'] !== '' ? ' · ' . $item['size'] : '') . ' · Ordered ' . (int)$item['quantity'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="issueOrderItemHelp">Select the product involved in the issue shipment.</div>
                        </div>
                        <div class="col-12 col-md-4 d-none" id="issueTakeQuantityField">
                            <label class="form-label" for="issueTakeQuantity">Collect quantity (আনবেন)</label>
                            <input class="form-control" type="number" id="issueTakeQuantity" name="take_quantity" min="1" value="1">
                        </div>
                        <div class="col-12 col-md-4 d-none" id="issueGiveQuantityField">
                            <label class="form-label" for="issueGiveQuantity">Deliver quantity (দিবেন)</label>
                            <input class="form-control" type="number" id="issueGiveQuantity" name="give_quantity" min="1" value="1">
                        </div>
                        <div class="col-12 d-none" id="issueReplacementProductField">
                            <label class="form-label" for="issueGiveProductSearch">Replacement product to deliver (দিবেন)</label>
                            <input class="form-control" type="search" id="issueGiveProductSearch" placeholder="Search product name or code">
                            <div class="list-group mt-2" id="issueGiveProductResults"></div>
                            <input type="hidden" name="give_product_id" id="issueGiveProductId">
                            <label class="form-label mt-2" for="issueGiveSize">Replacement size</label>
                            <select class="form-select" name="give_size" id="issueGiveSize"><option value="">Select product first</option></select>
                            <div class="small text-success mt-1" id="issueSelectedGiveProduct"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="orderIssueMessage">Admin note (optional)</label>
                            <textarea class="form-control" id="orderIssueMessage" name="message" rows="2" maxlength="3000"></textarea>
                        </div>
                        <div class="col-12"><button class="btn btn-outline-warning" type="submit">Create issue</button></div>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <section class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Shipping Address</div>
                <div class="card-body">
                    <?php if ($can_edit): ?>
                        <form class="js-order-edit-form">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="customer">
                            <div class="mb-2"><label class="form-label small mb-1">Customer Name</label><input class="form-control form-control-sm" name="fullname" value="<?= htmlspecialchars($order['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="form-label small mb-1">Phone</label><input class="form-control form-control-sm" name="phone" value="<?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                                <div class="col-md-6"><label class="form-label small mb-1">Email</label><input type="email" class="form-control form-control-sm" name="email" value="<?= htmlspecialchars($order['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </div>
                            <div class="mb-2 mt-2"><label class="form-label small mb-1">Address</label><textarea class="form-control form-control-sm" name="address" rows="2" required><?= htmlspecialchars($order['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6"><label class="form-label small mb-1">Payment Method</label><input class="form-control form-control-sm" name="payment_method" value="<?= htmlspecialchars($order['payment_method'] ?? 'COD', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                                <div class="col-md-6"><label class="form-label small mb-1">Delivery Charge</label><input class="form-control form-control-sm" type="number" min="0" step="1" name="shipping_charge" value="<?= (int)round((float)($order['shipping_charge'] ?? 0)); ?>" required></div>
                            </div>
                            <button class="btn btn-sm btn-outline-success mt-3" type="submit">Save Customer Details</button>
                        </form>
                    <?php else: ?>
                        <div class="fw-semibold"><?= htmlspecialchars($order['fullname'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><?= htmlspecialchars($order['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if (!empty($order['email'])): ?><div><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <div class="text-muted mt-2"><?= nl2br(htmlspecialchars($order['address'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-6">
            <section class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Payment Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Order Date</span><span><?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A'; ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Payment Method</span><span><?= htmlspecialchars($order['payment_method'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>৳<?= number_format((float)($order['subtotal'] ?? 0), 0); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Delivery</span><span>৳<?= number_format((float)($order['shipping_charge'] ?? 0), 0); ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>৳<?= number_format((float)($order['grand_total'] ?? 0), 0); ?></span></div>
                </div>
            </section>
        </div>
    </div>

    <section class="card shadow-sm mt-3">
        <div class="card-header fw-semibold">Items in this order</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-center">Quantity</th><th class="text-end">Price</th><th class="text-end">Total</th><?php if ($can_edit_items): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if ($items): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item['main_img'])): ?>
                                                <img src="../uploads/<?= rawurlencode(basename((string)$item['main_img'])); ?>" alt="" class="rounded border" style="width:52px;height:52px;object-fit:contain;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($item['name'] ?? $item['product_code'] ?? 'Product', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($item['product_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?><?= !empty($item['size']) ? ' · ' . htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') : ''; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($can_edit_items): ?>
                                            <form class="js-order-edit-form d-flex justify-content-center gap-1">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="update_item">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id']; ?>">
                                                <input class="form-control form-control-sm" style="width:76px" type="number" min="1" name="quantity" value="<?= (int)$item['quantity']; ?>" aria-label="Item quantity">
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                            </form>
                                        <?php else: ?>
                                            <?= (int)$item['quantity']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">৳<?= number_format((float)$item['price'], 0); ?></td>
                                    <td class="text-end">৳<?= number_format((float)($item['total_price'] ?? ((float)$item['price'] * (int)$item['quantity'])), 0); ?></td>
                                    <?php if ($can_edit_items): ?>
                                        <td class="text-end">
                                            <form class="js-order-edit-form">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove item"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= $can_edit_items ? 5 : 4; ?>" class="text-center text-muted py-4">No items are recorded for this order.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($can_edit_items): ?>
                <div class="border-top p-3">
                    <label for="orderProductSearch" class="form-label fw-semibold">Search products to add to this order</label>
                    <input type="search" class="form-control" id="orderProductSearch" autocomplete="off" placeholder="Search by product name or product code">
                    <div class="row row-cols-1 row-cols-md-2 g-2 mt-1" id="orderProductResults"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
const orderAlert = document.getElementById('orderAlert');

function showOrderAlert(message, type) {
    orderAlert.replaceChildren();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.setAttribute('role', 'alert');
    alert.append(document.createTextNode(message));
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Close');
    alert.append(close);
    orderAlert.append(alert);
    orderAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function submitOrderForm(form, endpoint) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;
    try {
    const response = await fetch(endpoint, { method: 'POST', body: new FormData(form) });
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error(text.trim() || 'Invalid server response.');
        }
    if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'The request could not be completed.');
    }
    showOrderAlert(result.message, 'success');
    window.setTimeout(() => window.location.reload(), 700);
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
}

document.addEventListener('submit', async event => {
    const form = event.target.closest('.js-order-edit-form');
    if (!form) return;
    event.preventDefault();
    try {
        await submitOrderForm(form, 'core/save_order_edits.php');
    } catch (error) {
        showOrderAlert(error.message, 'danger');
    }
});

const deliveryForm = document.getElementById('deliveryAssignmentForm');
if (deliveryForm) {
    const deliveryType = document.getElementById('deliveryType');
    const deliverymanSelect = document.getElementById('deliverymanSelect');
    const providerName = document.getElementById('providerName');
    const courierName = document.getElementById('courierName');
    const contactName = document.getElementById('contactName');
    const contactPhone = document.getElementById('contactPhone');
    const syncDeliveryProvider = () => {
        const isRider = deliveryType.value === 'rider';
        document.getElementById('riderSelectField').classList.toggle('d-none', !isRider);
        document.getElementById('courierNameField').classList.toggle('d-none', isRider);
        if (isRider) {
            const option = deliverymanSelect.selectedOptions[0];
            providerName.value = option?.dataset.name || '';
            if (option?.dataset.phone) contactPhone.value = option.dataset.phone;
            if (option?.dataset.name) contactName.value = option.dataset.name;
        } else {
            providerName.value = courierName.value.trim();
        }
    };
    deliveryType.addEventListener('change', syncDeliveryProvider);
    deliverymanSelect.addEventListener('change', syncDeliveryProvider);
    courierName.addEventListener('input', syncDeliveryProvider);
    syncDeliveryProvider();
    deliveryForm.addEventListener('submit', async event => {
        event.preventDefault();
        syncDeliveryProvider();
        if (!providerName.value) {
            showOrderAlert(deliveryType.value === 'rider' ? 'Choose an active deliveryman first.' : 'Enter a courier name.', 'danger');
            return;
        }
        try {
            await submitOrderForm(deliveryForm, 'core/save_delivery_assignment.php');
        } catch (error) {
            showOrderAlert(error.message, 'danger');
        }
    });
}

document.querySelectorAll('.js-order-status-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.elements.status.selectedOptions[0]?.dataset.undo === 'true' &&
            !window.confirm('Undo this order to the previous status?')) {
            return;
        }
        try {
            await submitOrderForm(form, 'core/sale_update_status.php');
        } catch (error) {
            showOrderAlert(error.message, 'danger');
        }
    });
});

const orderProductSearch = document.getElementById('orderProductSearch');
const orderProductResults = document.getElementById('orderProductResults');
let productSearchTimer;
let productSearchSequence = 0;

function renderOrderProductResults(products) {
    orderProductResults.replaceChildren();
    if (!products.length) {
        const empty = document.createElement('div');
        empty.className = 'col-12 text-muted small py-2';
        empty.textContent = 'No in-stock products found.';
        orderProductResults.append(empty);
        return;
    }

    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'col';
        const panel = document.createElement('div');
        panel.className = 'card h-100';
        const body = document.createElement('div');
        body.className = 'card-body d-flex gap-2';
        if (product.main_img) {
            const image = document.createElement('img');
            image.src = `../uploads/${encodeURIComponent(product.main_img.split(/[\\/]/).pop())}`;
            image.alt = '';
            image.className = 'border rounded flex-shrink-0';
            image.style.cssText = 'width:64px;height:64px;object-fit:contain';
            body.append(image);
        }
        const content = document.createElement('div');
        content.className = 'flex-grow-1';
        const name = document.createElement('div');
        name.className = 'fw-semibold';
        name.textContent = product.name;
        const meta = document.createElement('small');
        meta.className = 'text-muted d-block';
        meta.textContent = `${product.product_code || ''} · ৳${Math.round(Number(product.price))} · stock ${product.stock}`;
        content.append(name, meta);

        const form = document.createElement('form');
        form.className = 'js-order-edit-form d-flex flex-wrap gap-2 align-items-center mt-2';
        const fields = {
            order_id: '<?= (int)$order['id']; ?>',
            csrf_token: '<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>',
            action: 'add_item',
            product_id: product.id
        };
        Object.entries(fields).forEach(([field, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field;
            input.value = value;
            form.append(input);
        });
        const sizes = String(product.size || '').split(',').map(value => value.trim()).filter(Boolean);
        if (sizes.length) {
            const sizeSelect = document.createElement('select');
            sizeSelect.className = 'form-select form-select-sm';
            sizeSelect.name = 'size';
            sizeSelect.setAttribute('aria-label', 'Product size');
            sizes.forEach(size => sizeSelect.add(new Option(size, size)));
            form.append(sizeSelect);
        } else {
            const size = document.createElement('input');
            size.type = 'hidden';
            size.name = 'size';
            size.value = '';
            form.append(size);
        }
        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.name = 'quantity';
        quantity.min = '1';
        quantity.max = String(product.stock);
        quantity.value = '1';
        quantity.className = 'form-control form-control-sm';
        quantity.style.width = '76px';
        quantity.setAttribute('aria-label', 'Quantity');
        const add = document.createElement('button');
        add.type = 'submit';
        add.className = 'btn btn-sm btn-success';
        add.textContent = 'Add to this order';
        form.append(quantity, add);
        content.append(form);
        body.append(content);
        panel.append(body);
        card.append(panel);
        orderProductResults.append(card);
    });
}

orderProductSearch?.addEventListener('input', () => {
    window.clearTimeout(productSearchTimer);
    const query = orderProductSearch.value.trim();
    const requestSequence = ++productSearchSequence;
    if (query.length < 2) {
        orderProductResults.replaceChildren();
        return;
    }
    productSearchTimer = window.setTimeout(async () => {
        try {
            const response = await fetch(`core/search_order_products.php?q=${encodeURIComponent(query)}`);
                const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error(text.trim() || 'Invalid server response.');
        }
            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Product search failed.');
            if (requestSequence === productSearchSequence) renderOrderProductResults(result.products);
        } catch (error) {
            if (requestSequence !== productSearchSequence) return;
            orderProductResults.replaceChildren();
            const failure = document.createElement('div');
            failure.className = 'col-12 text-danger small py-2';
            failure.textContent = error.message;
            orderProductResults.append(failure);
        }
    }, 250);
});
const orderIssueForm = document.getElementById('orderIssueForm');
if (orderIssueForm) {
    const issueType = document.getElementById('orderIssueType');
    const orderItemSelect = document.getElementById('issueOrderItem');
    const takeField = document.getElementById('issueTakeQuantityField');
    const takeInput = document.getElementById('issueTakeQuantity');
    const giveField = document.getElementById('issueGiveQuantityField');
    const giveInput = document.getElementById('issueGiveQuantity');
    const replacementField = document.getElementById('issueReplacementProductField');
    const replacementSearch = document.getElementById('issueGiveProductSearch');
    const replacementResults = document.getElementById('issueGiveProductResults');
    const replacementId = document.getElementById('issueGiveProductId');
    const replacementSize = document.getElementById('issueGiveSize');
    const selectedReplacement = document.getElementById('issueSelectedGiveProduct');
    let replacementSearchTimer = null;

    const updateIssueForm = () => {
        const type = issueType.value;
        const collect = type === 'return' || type === 'replacement';
        const deliver = type === 'missing' || type === 'replacement';
        takeField.classList.toggle('d-none', !collect);
        takeInput.required = collect;
        giveField.classList.toggle('d-none', !deliver);
        giveInput.required = deliver;
        replacementField.classList.toggle('d-none', type !== 'replacement');
        replacementId.required = type === 'replacement';
        if (type !== 'replacement') replacementSize.required = false;
        document.getElementById('issueOrderItemHelp').textContent = type === 'missing'
            ? 'Select the missing item to deliver (দিবেন).'
            : type === 'return'
                ? 'Select the customer item to collect (আনবেন).'
                : 'Select the customer item to collect (আনবেন); then choose its replacement to deliver (দিবেন).';
        replacementSearch.required = false;
        if (type !== 'replacement') {
            replacementId.value = '';
            selectedReplacement.textContent = '';
            replacementSize.replaceChildren(new Option('Select product first', ''));
        }
    };
    issueType.addEventListener('change', updateIssueForm);
    orderItemSelect.addEventListener('change', () => {
        const option = orderItemSelect.selectedOptions[0];
        const maximum = Number(option?.dataset.quantity || 1);
        takeInput.max = String(maximum);
        giveInput.max = String(maximum);
        if (Number(takeInput.value) > maximum) takeInput.value = String(maximum);
        if (Number(giveInput.value) > maximum) giveInput.value = String(maximum);
    });
    replacementSearch.addEventListener('input', () => {
        window.clearTimeout(replacementSearchTimer);
        const query = replacementSearch.value.trim();
        replacementId.value = '';
        selectedReplacement.textContent = '';
        replacementResults.replaceChildren();
        if (query.length < 2) return;
        replacementSearchTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`core/search_order_products.php?q=${encodeURIComponent(query)}`);
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Product search failed.');
                result.products.forEach(product => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.textContent = `${product.name} · ${product.product_code} · stock ${product.stock}`;
                    button.addEventListener('click', () => {
                        replacementId.value = product.id;
                        selectedReplacement.textContent = `Selected to deliver: ${product.name} · ${product.product_code}`;
                        replacementSize.replaceChildren();
                        const sizes = String(product.size || '').split(',').map(size => size.trim()).filter(Boolean);
                        replacementSize.required = sizes.length > 0;
                        replacementSize.add(new Option(sizes.length ? 'Select size' : 'Not applicable', ''));
                        sizes.forEach(size => replacementSize.add(new Option(size, size)));
                        replacementResults.replaceChildren();
                    });
                    replacementResults.append(button);
                });
                if (!result.products.length) {
                    const empty = document.createElement('div');
                    empty.className = 'list-group-item small text-muted';
                    empty.textContent = 'No in-stock products found.';
                    replacementResults.append(empty);
                }
            } catch (error) {
                const failure = document.createElement('div');
                failure.className = 'list-group-item small text-danger';
                failure.textContent = error.message || 'Product search failed.';
                replacementResults.replaceChildren(failure);
            }
        }, 250);
    });
    updateIssueForm();
    orderIssueForm.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            await submitOrderForm(orderIssueForm, 'core/create_order_issue.php');
        } catch (error) {
            showOrderAlert(error.message || 'Order issue could not be created.', 'danger');
        }
    });
}

document.querySelectorAll('.js-issue-assignment-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            await submitOrderForm(form, 'core/save_order_issue_assignment.php');
        } catch (error) {
            showOrderAlert(error.message || 'Issue shipment assignment could not be saved.', 'danger');
        }
    });
});

document.querySelectorAll('.js-issue-status-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            await submitOrderForm(form, 'core/update_order_issue_shipment.php');
        } catch (error) {
            showOrderAlert(error.message || 'Issue shipment status could not be updated.', 'danger');
        }
    });
});

</script>
<?php include_once 'includes/footer.php'; ?>
