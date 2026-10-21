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
$timeline = get_order_status_timeline($pdo, $order);
$assignment_stmt = $pdo->prepare('SELECT * FROM order_delivery_assignments WHERE order_id = :order_id');
$assignment_stmt->execute([':order_id' => (int)$order_id]);
$assignment = $assignment_stmt->fetch(PDO::FETCH_ASSOC);
$status = normalize_order_status((string)($order['order_status'] ?? 'processing'));
$can_edit = !in_array($status, ['cancelled', 'delivered'], true);
$can_edit_items = in_array($status, ['processing', 'confirmed', 'picked', 'packed'], true);
$status_transitions = [
    'processing' => ['confirmed', 'cancelled'],
    'confirmed' => ['processing', 'picked', 'cancelled'],
    'picked' => ['confirmed', 'packed', 'cancelled'],
    'packed' => ['picked', 'delivering', 'cancelled'],
    'delivering' => ['delivered'],
];
$available_statuses = $status_transitions[$status] ?? [];
$backward_statuses = ['processing', 'confirmed', 'picked'];

$page_title = 'Order Details';
$current_page = 'orders';
include_once 'includes/header.php';

$timeline_descriptions = [
    'placed' => 'The order was successfully placed.',
    'processing' => 'We have received the order and it is being processed.',
    'confirmed' => 'The order has been confirmed.',
    'picked' => 'The items have been picked for packing.',
    'packed' => 'The order has been packed.',
    'delivering' => 'The order is on its way to the customer.',
    'delivered' => 'The order has been delivered.',
    'cancelled' => 'The order was cancelled.',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div>
        <a href="sales.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
        <h1 class="h4 fw-bold mb-0">Order #<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge text-bg-primary fs-6"><?= htmlspecialchars(order_status_label($status), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($available_statuses): ?>
            <form class="js-order-status-form d-flex gap-2 align-items-center">
                <input type="hidden" name="sale_id" value="<?= (int)$order['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <select name="status" class="form-select form-select-sm" aria-label="Update order status">
                    <?php foreach ($available_statuses as $next_status): ?>
                        <option value="<?= htmlspecialchars($next_status, ENT_QUOTES, 'UTF-8'); ?>" data-undo="<?= in_array($next_status, $backward_statuses, true) ? 'true' : 'false'; ?>">
                            <?= in_array($next_status, $backward_statuses, true) ? 'Undo to ' : 'Set to '; ?><?= htmlspecialchars(order_status_label($next_status), ENT_QUOTES, 'UTF-8'); ?>
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
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <?php if ($assignment || $status === 'packed'): ?>
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
                <?php if ($status === 'packed'): ?>
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
                        <label class="form-label small" for="providerName">Rider / courier name</label>
                        <input class="form-control" id="providerName" name="provider_name" value="<?= htmlspecialchars($assignment['provider_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
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
}

async function submitOrderForm(form, endpoint) {
    const response = await fetch(endpoint, { method: 'POST', body: new FormData(form) });
    const result = await response.json();
    if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'The request could not be completed.');
    }
    showOrderAlert(result.message, 'success');
    window.setTimeout(() => window.location.reload(), 700);
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
    deliveryForm.addEventListener('submit', async event => {
        event.preventDefault();
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
            const result = await response.json();
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
</script>
<?php include_once 'includes/footer.php'; ?>
