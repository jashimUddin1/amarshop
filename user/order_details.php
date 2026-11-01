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
ensure_order_request_messages_table($pdo);
ensure_order_issues_table($pdo);
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
    SELECT oi.*, p.name, p.main_img, p.sell_price AS mrp
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
$requests_stmt = $pdo->prepare('SELECT id, request_type, product_id, size, quantity, message, status, admin_note, created_at, resolved_at FROM order_requests WHERE order_id = :order_id AND user_id = :user_id ORDER BY id DESC');
$requests_stmt->execute([':order_id' => (int)$order_id, ':user_id' => (int)$_SESSION['user_id']]);
$order_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
$request_messages = [];
if ($order_requests) {
    $request_ids = array_column($order_requests, 'id');
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    $messages_stmt = $pdo->prepare("SELECT id, request_id, sender_type, message, created_at FROM order_request_messages WHERE request_id IN ($placeholders) ORDER BY id ASC");
    $messages_stmt->execute($request_ids);
    foreach ($messages_stmt->fetchAll(PDO::FETCH_ASSOC) as $request_message) {
        $request_messages[(int)$request_message['request_id']][] = $request_message;
    }
}
$mrp_total = 0.0;
foreach ($items as $item) {
    $mrp_total += max((float)($item['mrp'] ?? 0), (float)$item['price']) * (int)$item['quantity'];
}
$order_subtotal = (float)($order['subtotal'] ?? 0);
$discount_total = max(0, $mrp_total - $order_subtotal);
$latest_request_statuses = [];
foreach ($order_requests as $request) {
    if (!isset($latest_request_statuses[$request['request_type']])) {
        $latest_request_statuses[$request['request_type']] = $request['status'];
    }
}
$request_flash = $_SESSION['order_request_flash'] ?? '';
unset($_SESSION['order_request_flash']);
$latest_request_messages = [];
foreach ($request_messages as $request_id => $messages) {
    if ($messages) {
        $latest_request_messages[(int)$request_id] = end($messages);
    }
}
$timeline = get_order_status_timeline($pdo, $order);
$timeline_events = array_map(static function (array $event): array {
    $event['event_type'] = 'status';
    return $event;
}, $timeline);
foreach ($order_requests as $request) {
    $timeline_events[] = [
        'event_type' => 'request',
        'request' => $request,
        'created_at' => $request['created_at'],
    ];
    foreach ($request_messages[(int)$request['id']] ?? [] as $request_message) {
        $timeline_events[] = [
            'event_type' => 'request_message',
            'request' => $request,
            'message_id' => (int)$request_message['id'],
            'sender_type' => $request_message['sender_type'],
            'message' => $request_message['message'],
            'created_at' => $request_message['created_at'],
        ];
    }
    if ($request['status'] !== 'pending' && !empty($request['resolved_at'])) {
        $timeline_events[] = [
            'event_type' => 'request_decision',
            'request' => $request,
            'created_at' => $request['resolved_at'],
        ];
    }
}
$issue_stmt = $pdo->prepare('SELECT * FROM order_issues WHERE order_id = :order_id AND user_id = :user_id ORDER BY id ASC');
$issue_stmt->execute([':order_id' => (int)$order_id, ':user_id' => (int)$_SESSION['user_id']]);
$order_issues = $issue_stmt->fetchAll(PDO::FETCH_ASSOC);
$issue_assignments = [];
$order_issues_by_id = [];
if ($order_issues) {
    foreach ($order_issues as $issue_index => $order_issue) {
        $order_issues_by_id[(int)$order_issue['id']] = $order_issue;
        if (empty($order_issue['shipment_number'])) {
            $order_issues_by_id[(int)$order_issue['id']]['shipment_number'] = order_shipment_number((string)$order['order_number'], issue_shipment_suffix($issue_index + 1));
        }
    }
    $issue_ids = array_map(static fn(array $issue): int => (int)$issue['id'], $order_issues);
    $issue_placeholders = implode(',', array_fill(0, count($issue_ids), '?'));
    $issue_assignment_stmt = $pdo->prepare("SELECT * FROM order_issue_delivery_assignments WHERE issue_id IN ($issue_placeholders)");
    $issue_assignment_stmt->execute($issue_ids);
    foreach ($issue_assignment_stmt->fetchAll(PDO::FETCH_ASSOC) as $issue_assignment) {
        $issue_assignments[(int)$issue_assignment['issue_id']] = $issue_assignment;
    }
    foreach ($order_issues as $order_issue) {
        $timeline_events[] = [
            'event_type' => 'issue_created',
            'issue_id' => (int)$order_issue['id'],
            'created_at' => $order_issue['created_at'],
        ];
        if (!empty($issue_assignments[(int)$order_issue['id']])) {
            $assignment = $issue_assignments[(int)$order_issue['id']];
            $timeline_events[] = [
                'event_type' => 'issue_assigned',
                'issue_id' => (int)$order_issue['id'],
                'created_at' => $assignment['updated_at'] ?: $assignment['assigned_at'],
            ];
        }
    }
    $issue_history_stmt = $pdo->prepare("SELECT issue_id, shipment_status, created_at FROM order_issue_shipment_history WHERE issue_id IN ($issue_placeholders) ORDER BY created_at ASC, id ASC");
    $issue_history_stmt->execute($issue_ids);
    $has_final_issue_event = [];
    foreach ($issue_history_stmt->fetchAll(PDO::FETCH_ASSOC) as $issue_history) {
        if (!in_array($issue_history['shipment_status'], ['delivered', 'cancelled'], true)) {
            continue;
        }
        $has_final_issue_event[(int)$issue_history['issue_id']] = true;
        $timeline_events[] = [
            'event_type' => 'issue_shipment',
            'issue_id' => (int)$issue_history['issue_id'],
            'shipment_status' => $issue_history['shipment_status'],
            'created_at' => $issue_history['created_at'],
        ];
    }
    foreach ($order_issues as $order_issue) {
        $issue_id = (int)$order_issue['id'];
        $final_status = $order_issue['status'] === 'rejected'
            ? 'cancelled'
            : (in_array($order_issue['shipment_status'], ['delivered', 'cancelled'], true) ? $order_issue['shipment_status'] : null);
        if ($final_status !== null && empty($has_final_issue_event[$issue_id])) {
            $timeline_events[] = [
                'event_type' => 'issue_shipment',
                'issue_id' => $issue_id,
                'shipment_status' => $final_status,
                'created_at' => $order_issue['resolved_at'] ?: $order_issue['created_at'],
            ];
        }
    }
}
usort($timeline_events, static function (array $left, array $right): int {
    $left_time = !empty($left['created_at']) ? strtotime((string)$left['created_at']) : PHP_INT_MAX;
    $right_time = !empty($right['created_at']) ? strtotime((string)$right['created_at']) : PHP_INT_MAX;
    return $left_time <=> $right_time;
});
$order_status = normalize_order_status((string)$order['order_status']);
$can_request_item_addition = in_array($order_status, ['processing', 'confirmed', 'picked', 'packed'], true);
$has_delivering = false;
foreach ($timeline as $event) {
    if (normalize_order_status((string)($event['status'] ?? '')) === 'delivering') {
        $has_delivering = true;
        break;
    }
}
if ($assignment && !$has_delivering && in_array($order_status, ['packed', 'delivering', 'delivered'], true)) {
    $delivery_event = [
        'status' => 'delivering',
        'created_at' => $assignment['assigned_at'] ?? null,
    ];
    $timeline_events[] = array_merge($delivery_event, ['event_type' => 'status']);
    $delivery_insert_index = count($timeline);
    foreach ($timeline as $index => $event) {
        if (normalize_order_status((string)$event['status']) === 'packed') {
            $delivery_insert_index = $index + 1;
        }
    }
    array_splice($timeline, $delivery_insert_index, 0, [$delivery_event]);
    usort($timeline_events, static function (array $left, array $right): int {
        $left_time = !empty($left['created_at']) ? strtotime((string)$left['created_at']) : PHP_INT_MAX;
        $right_time = !empty($right['created_at']) ? strtotime((string)$right['created_at']) : PHP_INT_MAX;
        return $left_time <=> $right_time;
    });
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
    <title>Order #<?= htmlspecialchars(order_shipment_number((string)$order['order_number']), ENT_QUOTES, 'UTF-8'); ?> · My Orders</title>
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
            <a href="my_orders.php" class="btn btn-sm btn-outline-secondary mb-2">Back to Orders</a>
            <h1 class="h4 fw-bold mb-0">View Order: #<?= htmlspecialchars(order_shipment_number((string)$order['order_number']), ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <span class="badge text-bg-success fs-6"><?= htmlspecialchars(order_status_label((string)($order['order_status'] ?? 'processing')), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <section class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2 text-success"></i>Timeline</div>
        <div class="card-body">
            <ol class="list-unstyled mb-0">
                <?php foreach ($timeline_events as $event): ?>
                    <?php
                    $event_type = $event['event_type'] ?? 'status';
                    $event_time = $event['created_at'] ?? null;
                    $event_request = $event['request'] ?? null;
                    $event_issue = isset($event['issue_id']) ? ($order_issues_by_id[(int)$event['issue_id']] ?? null) : null;
                    ?>
                    <li class="d-flex gap-3 pb-4">
                        <div class="small text-muted text-nowrap" style="min-width: 118px;">
                            <?= !empty($event_time) ? date('M d, Y', strtotime($event_time)) . '<br>' . date('h:i A', strtotime($event_time)) : 'Time not recorded'; ?>
                        </div>
                        <div class="border-start border-2 border-success ps-3 position-relative">
                            <span class="position-absolute top-0 start-0 translate-middle-x badge rounded-pill text-bg-success">
                                <i class="bi <?= $event_type === 'issue_shipment' ? 'bi-box-seam' : ($event_type === 'status' && normalize_order_status((string)$event['status']) === 'delivering' ? 'bi-truck' : ($event_type === 'request_message' ? 'bi-chat-dots' : 'bi-check')); ?>"></i>
                            </span>
                            <?php if ($event_type === 'status'): ?>
                                <?php $event_status = normalize_order_status((string)$event['status']); ?>
                                <div class="fw-semibold"><?= htmlspecialchars(order_status_label($event_status), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($timeline_descriptions[$event_status] ?? 'Your order status was updated.', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php elseif ($event_type === 'request'): ?>
                                <?php
                                $request_label = match ($event_request['request_type']) {
                                    'add_item' => 'Add item request',
                                    'update' => 'Order update request',
                                    'cancel' => 'Cancellation request',
                                    default => 'Order request',
                                };
                                ?>
                                <div class="fw-semibold"><?= htmlspecialchars($request_label, ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="badge <?= $event_request['status'] === 'approved' ? 'text-bg-success' : ($event_request['status'] === 'rejected' ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?= htmlspecialchars(ucfirst($event_request['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="small text-muted"><?= nl2br(htmlspecialchars($event_request['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                            <?php elseif ($event_type === 'request_message'): ?>
                                <div class="fw-semibold"><?= $event['sender_type'] === 'admin' ? 'Admin replied' : 'Your reply'; ?> · <?= htmlspecialchars(match ($event_request['request_type']) { 'add_item' => 'Add item', 'update' => 'Order update', 'cancel' => 'Cancellation', default => 'Order request' }, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted"><?= nl2br(htmlspecialchars($event['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                                <?php
                                $latest_message = $latest_request_messages[(int)$event_request['id']] ?? null;
                                $is_latest_admin_reply = $event['sender_type'] === 'admin'
                                    && $event_request['status'] === 'pending'
                                    && (int)($latest_message['id'] ?? 0) === (int)$event['message_id'];
                                ?>
                                <?php if ($is_latest_admin_reply): ?>
                                    <form class="js-request-message-form d-flex gap-2 mt-2">
                                        <input type="hidden" name="request_id" value="<?= (int)$event_request['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['order_request_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="message" class="form-control form-control-sm" maxlength="3000" required placeholder="Reply to the admin">
                                        <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Send reply</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($event_type === 'request_decision'): ?>
                                <div class="fw-semibold"><?= htmlspecialchars(ucfirst($event_request['status']), ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars(match ($event_request['request_type']) { 'add_item' => 'Add item request', 'update' => 'Order update request', 'cancel' => 'Cancellation request', default => 'Order request' }, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php if (!empty($event_request['admin_note'])): ?><div class="small text-muted"><?= nl2br(htmlspecialchars($event_request['admin_note'], ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
                            <?php elseif ($event_type === 'issue_created' && $event_issue): ?>
                                <div class="fw-semibold">Issue created</div>
                            <?php elseif ($event_type === 'issue_assigned' && $event_issue): ?>
                                <?php $issue_assignment = $issue_assignments[(int)$event_issue['id']]; ?>
                                <div class="fw-semibold">Issue assigned</div>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($issue_assignment['contact_name'] ?: $issue_assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($issue_assignment['contact_phone'] !== ''): ?> · <a href="tel:<?= htmlspecialchars($issue_assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($issue_assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
                                </div>
                            <?php elseif ($event_type === 'issue_shipment' && $event_issue): ?>
                                <div class="fw-semibold">Issue <?= $event['shipment_status'] === 'delivered' ? 'delivered' : 'cancelled'; ?></div>
                            <?php endif; ?>
                            <?php if ($event_type === 'status' && normalize_order_status((string)$event['status']) === 'delivering' && !empty($assignment)): ?>
                                <div class="bg-light border rounded p-2 mt-2" style="max-width: 480px;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge text-bg-success" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($assignment['delivery_type'] === 'courier' ? 'Courier' : 'Rider', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <strong class="text-dark small"><?= htmlspecialchars($assignment['provider_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <?php if ($assignment['contact_name'] !== ''): ?>
                                        <div class="small text-secondary mb-1">
                                            <i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($assignment['contact_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assignment['contact_phone'] !== ''): ?>
                                        <div class="small mb-1">
                                            <i class="bi bi-telephone me-1 text-muted"></i>
                                            <a href="tel:<?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($assignment['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assignment['tracking_number'] !== ''): ?>
                                        <div class="small text-muted mt-1 pt-1 border-top">
                                            <i class="bi bi-upc-scan me-1"></i>Tracking / Consignment: 
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($assignment['tracking_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="card-footer bg-white text-center small">
            Need help? Call <a href="tel:01950047790">01950047790</a> or contact customer support.
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
                            <small class="text-muted">
                                Code: <?= htmlspecialchars($item['product_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($item['size'])): ?> · Size: <?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            </small>
                            <div class="small text-muted"><?= (int)$item['quantity']; ?> × ৳<?= number_format((float)$item['price'], 0); ?> = ৳<?= number_format((float)($item['total_price'] ?? ((float)$item['price'] * (int)$item['quantity'])), 0); ?></div>
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
                        ?>
                            <button type="button" class="btn btn-sm btn-outline-<?= $action['style']; ?> order-request-button" onclick="openOrderRequest('<?= $type; ?>')" <?= $request_status === 'pending' ? 'disabled' : ''; ?>>
                                <span class="request-label d-block"><i class="bi <?= $action['icon']; ?> me-1"></i><?= $action['label']; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">Request updates and replies appear in the timeline above.</div>
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
                <div class="card-body p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-3">Payment Summary</h2>
                    <?php if ($discount_total > 0): ?>
                        <p class="text-success mb-3">You saved ৳<?= number_format($discount_total, 2); ?> on this order.</p>
                    <?php endif; ?>
                    <p class="small text-muted">MRP and savings use current product catalog prices; historical MRP was not saved when this order was placed.</p>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>Order ID</span><span>#<?= htmlspecialchars(order_shipment_number((string)$order['order_number']), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>Order at</span><span class="text-end"><?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : 'N/A'; ?></span></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>MRP</span><span>৳<?= number_format($mrp_total, 2); ?></span></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>Delivery charge</span><span><?= (float)($order['shipping_charge'] ?? 0) <= 0 ? 'Free' : '৳' . number_format((float)$order['shipping_charge'], 2); ?></span></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>Discount applied</span><span>-৳<?= number_format($discount_total, 2); ?></span></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span>Coupon applied</span><span>৳0.00</span></div>
                    <div class="d-flex justify-content-between gap-3 mb-3"><span>Rounding off</span><span>৳0.00</span></div>
                    <div class="d-flex justify-content-between gap-3 border-top pt-3 mb-3 fw-bold"><span>Payable</span><span>৳<?= number_format((float)$order['grand_total'], 2); ?></span></div>
                    <a class="btn btn-outline-success w-100 fw-semibold" href="invoice.php?id=<?= (int)$order['id']; ?>" target="_blank" rel="noopener">
                        <i class="bi bi-download me-1"></i>Download Invoice
                    </a>
                    <div class="form-text text-center mt-2">In the print window, choose Save as PDF to download.</div>
                </div>
            </section>
        </div>
    </div>
    <footer class="card border-0 shadow-sm mt-3">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <section class="col-12 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="../assets/img/logo1.png" alt="AmarShop logo" style="width:44px;height:44px;object-fit:contain;">
                        <strong class="text-success fs-5">AmarShop</strong>
                    </div>
                    <p class="small text-muted mb-0">Your one-stop online shop for quality products at affordable prices, with fast delivery and friendly customer support.</p>
                </section>
                <section class="col-12 col-md-6 text-md-end small text-muted">
                    <strong class="d-block text-dark mb-2">Company Address</strong>
                    <div>123 Shopping Street<br>Dhaka, Bangladesh</div>
                    <div class="mt-1">01950047790<br>support@amarshop.com</div>
                </section>
            </div>
            <div class="border-top mt-3 pt-2 text-end small text-muted">&copy; <?= date('Y'); ?> AmarShop. All rights reserved.</div>
        </div>
    </footer>
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

document.querySelectorAll('.js-request-message-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const response = await fetch('../core/add_order_request_message.php', {
                method: 'POST',
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Reply could not be sent.');
            }
            window.location.reload();
        } catch (error) {
            const alert = document.getElementById('requestAlert');
            alert.textContent = error.message || 'Reply could not be sent.';
            alert.className = 'alert alert-danger';
        }
    });
});

</script>
</body>
</html>
