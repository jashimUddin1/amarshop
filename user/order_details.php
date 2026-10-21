<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/order_status.php';
require_once __DIR__ . '/../core/order_admin.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = $_GET['id'] ?? '';
if (!is_string($order_id) || filter_var($order_id, FILTER_VALIDATE_INT) === false || (int)$order_id <= 0) {
    http_response_code(400);
    exit('Invalid order ID.');
}

ensure_order_status_history_table($pdo);
ensure_order_delivery_assignments_table($pdo);
ensure_order_requests_table($pdo);
if (empty($_SESSION['order_request_csrf'])) {
    $_SESSION['order_request_csrf'] = bin2hex(random_bytes(32));
}
$order_stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$order_stmt->execute([
    ':id' => (int)$order_id,
    ':user_id' => (int)$_SESSION['user_id'],
]);
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
$assignment_stmt = $pdo->prepare('SELECT delivery_type, provider_name, contact_name, contact_phone, tracking_number, assigned_at FROM order_delivery_assignments WHERE order_id = :order_id');
$assignment_stmt->execute([':order_id' => (int)$order_id]);
$assignment = $assignment_stmt->fetch(PDO::FETCH_ASSOC);
$requests_stmt = $pdo->prepare('SELECT request_type, message, status, admin_note, created_at FROM order_requests WHERE order_id = :order_id AND user_id = :user_id ORDER BY id DESC');
$requests_stmt->execute([':order_id' => (int)$order_id, ':user_id' => (int)$_SESSION['user_id']]);
$order_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
$latest_request_statuses = [];
foreach ($order_requests as $request) {
    if (!isset($latest_request_statuses[$request['request_type']])) {
        $latest_request_statuses[$request['request_type']] = $request['status'];
    }
}
$request_flash = $_SESSION['order_request_flash'] ?? '';
unset($_SESSION['order_request_flash']);
$timeline = get_order_status_timeline($pdo, $order);
$order_status = normalize_order_status((string)$order['order_status']);
$can_request_item_addition = in_array($order_status, ['processing', 'confirmed', 'picked', 'packed'], true);
if ($assignment) {
    $delivery_event = [
        'type' => 'delivery_information',
        'created_at' => $assignment['assigned_at'],
    ];
    $delivery_insert_index = count($timeline);
    foreach ($timeline as $index => $event) {
        if (normalize_order_status((string)$event['status']) === 'packed') {
            $delivery_insert_index = $index + 1;
        }
    }
    array_splice($timeline, $delivery_insert_index, 0, [$delivery_event]);
}

$timeline_descriptions = [
    'placed' => 'Your order was successfully placed.',
    'processing' => 'We have received your order and it is being processed.',
    'confirmed' => 'We have confirmed your order.',
    'picked' => 'The items have been picked for packing.',
    'packed' => 'Your order is packed and ready for delivery.',
    'delivering' => 'Our delivery partner is delivering your order.',
    'delivered' => 'You have received your order.',
    'cancelled' => 'This order has been cancelled.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order #<?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?> · My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .order-request-button { flex: 1 1 0; min-width: 0; padding-inline: .25rem; }
        .order-request-button .request-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body class="bg-light py-3 py-md-4">
<main class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="my_orders.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
            <h1 class="h4 fw-bold mb-0">View Order: #<?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <span class="badge text-bg-success fs-6"><?= htmlspecialchars(order_status_label((string)($order['order_status'] ?? 'processing')), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <?php if ($order_requests): ?>
        <section class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Your Requests</div>
            <div class="list-group list-group-flush">
                <?php foreach ($order_requests as $request): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= htmlspecialchars(match ($request['request_type']) { 'add_item' => 'Add item', 'update' => 'Order update', 'cancel' => 'Cancellation', default => ucfirst($request['request_type']) }, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="badge <?= $request['status'] === 'approved' ? 'text-bg-success' : ($request['status'] === 'rejected' ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?= htmlspecialchars(ucfirst($request['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="small text-muted mt-1"><?= htmlspecialchars($request['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if (!empty($request['admin_note'])): ?><div class="small mt-1">Admin: <?= htmlspecialchars($request['admin_note'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        <small class="text-muted"><?= !empty($request['created_at']) ? date('d M Y, h:i A', strtotime($request['created_at'])) : ''; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2 text-success"></i>Timeline</div>
        <div class="card-body">
            <ol class="list-unstyled mb-0">
                <?php foreach ($timeline as $event): ?>
                    <?php if (($event['type'] ?? '') === 'delivery_information'): ?>
                        <li class="d-flex gap-3 pb-4">
                            <div class="small text-muted text-nowrap" style="min-width: 118px;">
                                <?= !empty($event['created_at']) ? date('M d, Y', strtotime($event['created_at'])) . '<br>' . date('h:i A', strtotime($event['created_at'])) : 'Time not recorded'; ?>
                            </div>
                            <div class="border-start border-2 border-success ps-3 position-relative">
                                <span class="position-absolute top-0 start-0 translate-middle-x badge rounded-pill text-bg-success"><i class="bi bi-truck"></i></span>
                                <div class="fw-semibold">Delivery Information</div>
                                <div class="small">
                                    <?= htmlspecialchars($assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="badge text-bg-light"><?= htmlspecialchars($assignment['delivery_type'] === 'courier' ? 'Courier' : 'Rider', ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <?php if ($assignment['contact_name'] !== ''): ?><div class="small"><?= htmlspecialchars($assignment['contact_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                <?php if ($assignment['contact_phone'] !== ''): ?><div class="small"><a href="tel:<?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?></a></div><?php endif; ?>
                                <?php if ($assignment['tracking_number'] !== ''): ?><div class="small text-muted mt-1">Tracking / Consignment: <?= htmlspecialchars($assignment['tracking_number'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                            </div>
                        </li>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php $event_status = normalize_order_status((string)$event['status']); ?>
                    <li class="d-flex gap-3 pb-4">
                        <div class="small text-muted text-nowrap" style="min-width: 118px;">
                            <?= !empty($event['created_at']) ? date('M d, Y', strtotime($event['created_at'])) . '<br>' . date('h:i A', strtotime($event['created_at'])) : 'Time not recorded'; ?>
                        </div>
                        <div class="border-start border-2 border-success ps-3 position-relative">
                            <span class="position-absolute top-0 start-0 translate-middle-x badge rounded-pill text-bg-success"><i class="bi bi-check"></i></span>
                            <div class="fw-semibold"><?= htmlspecialchars(order_status_label($event_status), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($timeline_descriptions[$event_status] ?? 'Your order status was updated.', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="card-footer bg-white text-center small">
            Need help? Call <a href="tel:16778">16778</a> or contact customer support.
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Items in this order</div>
        <div class="card-body p-0">
            <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                    <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                        <?php if (!empty($item['main_img'])): ?>
                            <img src="../uploads/<?= rawurlencode(basename((string)$item['main_img'])); ?>" alt="" class="border rounded bg-white" style="width:64px;height:64px;object-fit:contain;">
                        <?php else: ?>
                            <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width:64px;height:64px;"><i class="bi bi-box text-muted"></i></div>
                        <?php endif; ?>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold"><?= htmlspecialchars($item['name'] ?? $item['product_code'] ?? 'Product', ENT_QUOTES, 'UTF-8'); ?></div>
                            <small class="text-muted"><?= !empty($item['size']) ? 'Size: ' . htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($item['product_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            <div class="small text-muted">Quantity: <?= (int)$item['quantity']; ?></div>
                        </div>
                        <div class="text-end text-nowrap">
                            <div class="fw-semibold">৳<?= number_format((float)($item['total_price'] ?? ((float)$item['price'] * (int)$item['quantity'])), 0); ?></div>
                            <small class="text-muted">৳<?= number_format((float)$item['price'], 0); ?> each</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted py-4 mb-0">No items are recorded for this order.</p>
            <?php endif; ?>
            <div class="p-3 text-center">
                <div id="requestAlert" aria-live="polite"></div>
                <?php if ($request_flash !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($request_flash, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if (!in_array($order_status, ['cancelled', 'delivered'], true)): ?>
                    <div class="d-flex flex-nowrap gap-2">
                        <?php
                        $request_actions = [
                            'add_item' => ['label' => 'Add item', 'icon' => 'bi-plus-circle', 'style' => 'success'],
                            'update' => ['label' => 'Update', 'icon' => 'bi-pencil-square', 'style' => 'primary'],
                            'cancel' => ['label' => 'Cancel', 'icon' => 'bi-x-circle', 'style' => 'danger'],
                        ];
                        foreach ($request_actions as $type => $action):
                            if ($type === 'add_item' && !$can_request_item_addition) {
                                continue;
                            }
                            $request_status = $latest_request_statuses[$type] ?? null;
                            $status_style = match ($request_status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => '',
                            };
                        ?>
                            <button type="button" class="btn btn-sm btn-outline-<?= $action['style']; ?> order-request-button" onclick="openOrderRequest('<?= $type; ?>')" <?= $request_status === 'pending' ? 'disabled' : ''; ?>>
                                <span class="request-label d-block"><i class="bi <?= $action['icon']; ?> me-1"></i><?= $action['label']; ?></span>
                                <?php if ($request_status !== null): ?><span class="badge text-bg-<?= $status_style; ?>"><?= htmlspecialchars(ucfirst($request_status), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">Request status appears below each action. Pending means it is waiting for admin review.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Shipping Address</div>
                <div class="card-body">
                    <div class="fw-semibold"><?= htmlspecialchars($order['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="small text-muted mt-2"><?= nl2br(htmlspecialchars($order['address'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Payment Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Order ID</span><span>#<?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Ordered at</span><span><?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A'; ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Payment</span><span><?= htmlspecialchars($order['payment_method'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>৳<?= number_format((float)($order['subtotal'] ?? 0), 0); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Delivery</span><span>৳<?= number_format((float)($order['shipping_charge'] ?? 0), 0); ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold"><span>Payable</span><span>৳<?= number_format((float)$order['grand_total'], 0); ?></span></div>
                </div>
            </section>
        </div>
    </div>
</main>
<div class="modal fade" id="orderRequestModal" tabindex="-1" aria-labelledby="orderRequestTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form id="orderRequestForm">
            <div class="modal-header"><h2 class="modal-title fs-5" id="orderRequestTitle">Order Request</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['order_request_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                <input type="hidden" name="request_type" id="requestType">
                <div id="requestProductFields" class="d-none">
                    <label for="requestProductSearch" class="form-label">Search products</label>
                    <input type="search" id="requestProductSearch" class="form-control" placeholder="Search by product name or code">
                    <div id="requestProductResults" class="list-group mt-2"></div>
                    <input type="hidden" name="product_id" id="requestProductId">
                    <div class="row g-2 mt-2">
                        <div class="col-6"><label for="requestProductSize" class="form-label">Size</label><select id="requestProductSize" name="size" class="form-select"><option value="">Select product first</option></select></div>
                        <div class="col-6"><label for="requestProductQuantity" class="form-label">Quantity</label><input type="number" id="requestProductQuantity" name="quantity" class="form-control" min="1" value="1"></div>
                    </div>
                </div>
                <div id="requestMessageField" class="mt-3">
                    <label for="requestMessage" class="form-label" id="requestMessageLabel">Message / reason</label>
                    <textarea class="form-control" name="message" id="requestMessage" rows="3" placeholder="Describe what you need changed"></textarea>
                    <div class="form-text d-none" id="cancelReasonHelp">Please tell us why you want to cancel. This reason will be sent to the admin.</div>
                </div>
                <div id="requestSelectedProduct" class="small text-success mt-2"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-success">Send Request</button></div>
        </form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const requestTypeInput = document.getElementById('requestType');
const requestProductFields = document.getElementById('requestProductFields');
const requestMessageField = document.getElementById('requestMessageField');
const requestMessageInput = document.getElementById('requestMessage');
const requestProductSearch = document.getElementById('requestProductSearch');
const requestProductResults = document.getElementById('requestProductResults');
let selectedRequestProduct = null;
let requestSearchTimer = null;

function openOrderRequest(type) {
    requestTypeInput.value = type;
    selectedRequestProduct = null;
    document.getElementById('requestProductId').value = '';
    document.getElementById('requestSelectedProduct').textContent = '';
    requestProductResults.replaceChildren();
    requestProductSearch.value = '';
    requestMessageInput.value = '';
    const titles = { add_item: 'Request to Add an Item', update: 'Request an Order Update', cancel: 'Request Order Cancellation' };
    document.getElementById('orderRequestTitle').textContent = titles[type];
    requestProductFields.classList.toggle('d-none', type !== 'add_item');
    requestMessageField.classList.toggle('d-none', type === 'add_item');
    requestMessageInput.required = type === 'update' || type === 'cancel';
    document.getElementById('requestMessageLabel').textContent = type === 'cancel' ? 'Cancellation reason' : 'Message / reason';
    requestMessageInput.placeholder = type === 'cancel'
        ? 'Please explain why you want to cancel this order'
        : 'Describe what you need changed';
    document.getElementById('cancelReasonHelp').classList.toggle('d-none', type !== 'cancel');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('orderRequestModal')).show();
}

requestProductSearch?.addEventListener('input', () => {
    window.clearTimeout(requestSearchTimer);
    const query = requestProductSearch.value.trim();
    if (query.length < 2) {
        requestProductResults.replaceChildren();
        return;
    }
    requestSearchTimer = window.setTimeout(async () => {
        try {
            const response = await fetch(`../search_api.php?search=${encodeURIComponent(query)}`);
            const result = await response.json();
            if (!response.ok || result.status !== 'success') throw new Error('Product search failed.');
            requestProductResults.replaceChildren();
            result.products.filter(product => Number(product.stock) > 0).forEach(product => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex justify-content-between';
                button.textContent = `${product.name} · ${product.product_code || ''} · ৳${Math.round(Number(product.actual_price || product.sell_price || 0))} · stock ${product.stock}`;
                button.addEventListener('click', () => {
                    selectedRequestProduct = product;
                    document.getElementById('requestProductId').value = product.id;
                    document.getElementById('requestSelectedProduct').textContent = `Selected: ${product.name}`;
                    const sizeSelect = document.getElementById('requestProductSize');
                    sizeSelect.replaceChildren();
                    const sizes = String(product.size || '').split(',').map(size => size.trim()).filter(Boolean);
                    if (sizes.length) {
                        sizes.forEach(size => sizeSelect.add(new Option(size, size)));
                    } else {
                        sizeSelect.add(new Option('Not applicable', ''));
                    }
                    requestProductResults.replaceChildren();
                });
                requestProductResults.append(button);
            });
            if (!requestProductResults.children.length) {
                requestProductResults.innerHTML = '<div class="list-group-item text-muted">No in-stock products found.</div>';
            }
        } catch (error) {
            requestProductResults.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
        }
    }, 250);
});

document.getElementById('orderRequestForm')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    if (requestTypeInput.value === 'add_item' && !selectedRequestProduct) {
        document.getElementById('requestSelectedProduct').textContent = 'Please search and select an item first.';
        return;
    }
    try {
        const response = await fetch('../core/create_order_request.php', { method: 'POST', body: new FormData(form) });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Request failed.');
        bootstrap.Modal.getInstance(document.getElementById('orderRequestModal')).hide();
        const alert = document.getElementById('requestAlert');
        alert.textContent = result.message;
        alert.className = 'alert alert-success';
        window.setTimeout(() => window.location.reload(), 900);
    } catch (error) {
        document.getElementById('requestAlert').textContent = error.message;
        document.getElementById('requestAlert').className = 'alert alert-danger';
    }
});
</script>
</body>
</html>
