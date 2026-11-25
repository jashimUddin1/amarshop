<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);

$csrf_token = $_SESSION['assign_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['assign_csrf'] = $csrf_token;
$notice = is_array($_SESSION['assign_notice'] ?? null) ? $_SESSION['assign_notice'] : null;
unset($_SESSION['assign_notice']);
$active_tab = is_string($_GET['tab'] ?? null) && in_array($_GET['tab'], ['orders', 'ready', 'bags'], true)
    ? $_GET['tab']
    : 'ready';

function assign_delivery_bag_to_deliveryman(PDO $pdo, int $bag_id, int $deliveryman_id): string
{
    $bag_stmt = $pdo->prepare("SELECT id, status FROM delivery_bags WHERE id = :id FOR UPDATE");
    $bag_stmt->execute([':id' => $bag_id]);
    $bag = $bag_stmt->fetch(PDO::FETCH_ASSOC);
    $rider_stmt = $pdo->prepare("SELECT id, customar_name, phone FROM deliveryman WHERE id = :id AND status = 'active'");
    $rider_stmt->execute([':id' => $deliveryman_id]);
    $rider = $rider_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bag || $bag['status'] !== 'open' || !$rider) {
        throw new InvalidArgumentException('Only an open bag can be assigned to an active deliveryman.');
    }

    $pdo->prepare("UPDATE delivery_bags SET deliveryman_id = :rider, status = 'assigned' WHERE id = :bag")
        ->execute([':rider' => $deliveryman_id, ':bag' => $bag_id]);
    $items_stmt = $pdo->prepare("SELECT id, shipment_type, order_id, issue_id FROM order_quantity WHERE bag_id = :bag AND status = 'in_bag' FOR UPDATE");
    $items_stmt->execute([':bag' => $bag_id]);
    $mark_delivering = $pdo->prepare("UPDATE order_quantity SET status = 'delivering' WHERE id = :id");
    $order_assignment = $pdo->prepare("
        INSERT INTO order_delivery_assignments
            (order_id, deliveryman_id, delivery_type, provider_name, contact_name, contact_phone)
        VALUES (:order_id, :rider_id, 'rider', 'AmarShop Delivery', :name, :phone)
        ON DUPLICATE KEY UPDATE deliveryman_id = VALUES(deliveryman_id), delivery_type = 'rider',
            provider_name = VALUES(provider_name), contact_name = VALUES(contact_name),
            contact_phone = VALUES(contact_phone), updated_at = CURRENT_TIMESTAMP
    ");
    $issue_assignment = $pdo->prepare("
        INSERT INTO order_issue_delivery_assignments
            (issue_id, deliveryman_id, delivery_type, provider_name, contact_name, contact_phone)
        VALUES (:issue_id, :rider_id, 'rider', 'AmarShop Delivery', :name, :phone)
        ON DUPLICATE KEY UPDATE deliveryman_id = VALUES(deliveryman_id), delivery_type = 'rider',
            provider_name = VALUES(provider_name), contact_name = VALUES(contact_name),
            contact_phone = VALUES(contact_phone), updated_at = CURRENT_TIMESTAMP
    ");
    $set_order_delivering = $pdo->prepare("UPDATE orders SET order_status = 'delivering' WHERE id = :id AND order_status = 'inbag'");
    $add_history = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (:id, 'delivering')");
    $set_issue_delivering = $pdo->prepare("UPDATE order_issues SET shipment_status = 'delivering' WHERE id = :id AND shipment_status = 'packed'");
    $issue_history = $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:id, 'delivering')");
    foreach ($items_stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $mark_delivering->execute([':id' => (int)$item['id']]);
        if ($item['shipment_type'] === 'order') {
            $order_assignment->execute([
                ':order_id' => (int)$item['order_id'],
                ':rider_id' => $deliveryman_id,
                ':name' => $rider['customar_name'],
                ':phone' => $rider['phone'],
            ]);
            $set_order_delivering->execute([':id' => (int)$item['order_id']]);
            $add_history->execute([':id' => (int)$item['order_id']]);
        } else {
            $issue_assignment->execute([
                ':issue_id' => (int)$item['issue_id'],
                ':rider_id' => $deliveryman_id,
                ':name' => $rider['customar_name'],
                ':phone' => $rider['phone'],
            ]);
            $set_issue_delivering->execute([':id' => (int)$item['issue_id']]);
            $issue_history->execute([':id' => (int)$item['issue_id']]);
        }
    }
    return (string)$rider['customar_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $redirect_tab = $action === 'create_bag' || $action === 'assign_bag' ? 'bags' : 'orders';
    try {
        $submitted_csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $submitted_csrf)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }

        if ($action === 'create_bag') {
            $zone = trim((string)($_POST['zone'] ?? ''));
            $deliveryman_input = $_POST['deliveryman_id'] ?? '';
            if (!is_string($deliveryman_input)) {
                throw new InvalidArgumentException('Select a valid deliveryman.');
            }
            $deliveryman_id = $deliveryman_input === '' ? null : filter_var($deliveryman_input, FILTER_VALIDATE_INT);
            $selected = $_POST['shipments'] ?? [];
            if ($zone === '' || strlen($zone) > 180 ||
                ($deliveryman_input !== '' && ($deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0)) ||
                !is_array($selected) || count($selected) === 0 || count($selected) > 200) {
                throw new InvalidArgumentException('Enter the area and select at least one packed shipment (maximum 200).');
            }

            $pdo->beginTransaction();
            $shipments = [];
            $selected_keys = [];
            foreach ($selected as $selection) {
                if (!is_string($selection) || !preg_match('/^(order|issue):([1-9][0-9]*)$/', $selection, $match)) {
                    throw new InvalidArgumentException('A selected shipment is invalid.');
                }
                $type = $match[1];
                $id = (int)$match[2];
                $selection_key = $type . ':' . $id;
                if (isset($selected_keys[$selection_key])) {
                    throw new InvalidArgumentException('A shipment can only be selected once per bag.');
                }
                $selected_keys[$selection_key] = true;
                $source_stmt = $pdo->prepare($type === 'order'
                    ? "SELECT id, order_number, fullname, phone, address, grand_total, payment_method, shipping_area
                       FROM orders WHERE id = :id AND order_status = 'packed_done' FOR UPDATE"
                    : "SELECT i.id, i.order_id, i.shipment_number, o.order_number, o.fullname, o.phone, o.address,
                              o.shipping_area, i.status, i.shipment_status
                       FROM order_issues i JOIN orders o ON o.id = i.order_id
                       WHERE i.id = :id FOR UPDATE");
                $source_stmt->execute([':id' => $id]);
                $source = $source_stmt->fetch(PDO::FETCH_ASSOC);
                if (!$source) {
                    throw new InvalidArgumentException('A selected shipment is no longer ready for delivery or does not exist. Refresh and retry.');
                }
                if ($type === 'issue' &&
                    ($source['status'] === 'rejected' || $source['shipment_status'] !== 'packed')) {
                    throw new InvalidArgumentException('Only approved, packed issue shipments can be added to a bag.');
                }
                $duplicate_stmt = $pdo->prepare("
                    SELECT 1 FROM order_quantity
                    WHERE shipment_type = :type
                        AND " . ($type === 'order' ? 'order_id' : 'issue_id') . " = :source_id
                        AND status IN ('in_bag', 'delivering', 'delivered')
                    LIMIT 1
                    FOR UPDATE
                ");
                $duplicate_stmt->execute([':type' => $type, ':source_id' => $id]);
                if ($duplicate_stmt->fetchColumn()) {
                    throw new InvalidArgumentException('A selected shipment is already assigned to an active or completed bag.');
                }

                if ($type === 'order') {
                    $shipment_number = order_shipment_number((string)$source['order_number']);
                    $cash = strtoupper((string)($source['payment_method'] ?? 'COD')) === 'COD'
                        ? (float)$source['grand_total']
                        : 0.0;
                    $source_id = $id;
                } else {
                    $shipment_number = trim((string)($source['shipment_number'] ?? ''));
                    if ($shipment_number === '') {
                        $sequence_stmt = $pdo->prepare('SELECT COUNT(*) FROM order_issues WHERE order_id = :order_id AND id <= :issue_id');
                        $sequence_stmt->execute([':order_id' => (int)$source['order_id'], ':issue_id' => $id]);
                        $shipment_number = order_shipment_number((string)$source['order_number'], issue_shipment_suffix((int)$sequence_stmt->fetchColumn() - 1));
                    }
                    $cash = 0.0;
                    $source_id = $id;
                }
                $shipping_area = order_shipping_area_label((string)($source['shipping_area'] ?? ''));
                $shipments[] = [
                    'type' => $type,
                    'id' => $source_id,
                    'number' => $shipment_number,
                    'name' => (string)($source['fullname'] ?? ''),
                    'phone' => (string)($source['phone'] ?? ''),
                    'address' => trim((string)($source['address'] ?? '') . ($shipping_area !== '' ? ', ' . $shipping_area : '')),
                    'cash' => $cash,
                ];
            }

            $bag_code = 'BAG-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $bag_name = 'Bag ' . date('ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $bag_insert = $pdo->prepare("
                INSERT INTO delivery_bags (bag_code, bag_name, zone, order_quantity, notes, status, created_by_admin_id)
                VALUES (:code, :name, :zone, :quantity, NULL, 'open', :admin_id)
            ");
            $bag_insert->execute([
                ':code' => $bag_code,
                ':name' => $bag_name,
                ':zone' => $zone,
                ':quantity' => count($shipments),
                ':admin_id' => (int)$_SESSION['admin_user_id'],
            ]);
            $bag_id = (int)$pdo->lastInsertId();
            $manifest_insert = $pdo->prepare("
                INSERT INTO order_quantity
                    (bag_id, shipment_type, order_id, issue_id, shipment_number, customer_name,
                     customer_phone, address, expected_cash, status)
                VALUES
                    (:bag_id, :type, :order_id, :issue_id, :shipment_number, :customer_name,
                     :customer_phone, :address, :expected_cash, 'in_bag')
            ");
            $set_inbag = $pdo->prepare("UPDATE orders SET order_status = 'inbag' WHERE id = :id");
            $add_history = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (:id, 'inbag')");
            $clear_order_assignment = $pdo->prepare('DELETE FROM order_delivery_assignments WHERE order_id = :id');
            $clear_issue_assignment = $pdo->prepare('DELETE FROM order_issue_delivery_assignments WHERE issue_id = :id');
            foreach ($shipments as $shipment) {
                $manifest_insert->execute([
                    ':bag_id' => $bag_id,
                    ':type' => $shipment['type'],
                    ':order_id' => $shipment['type'] === 'order' ? $shipment['id'] : null,
                    ':issue_id' => $shipment['type'] === 'issue' ? $shipment['id'] : null,
                    ':shipment_number' => $shipment['number'],
                    ':customer_name' => $shipment['name'],
                    ':customer_phone' => $shipment['phone'],
                    ':address' => $shipment['address'],
                    ':expected_cash' => $shipment['cash'],
                ]);
                if ($shipment['type'] === 'order') {
                    $clear_order_assignment->execute([':id' => $shipment['id']]);
                    $set_inbag->execute([':id' => $shipment['id']]);
                    $add_history->execute([':id' => $shipment['id']]);
                } else {
                    $clear_issue_assignment->execute([':id' => $shipment['id']]);
                }
            }
            $assigned_deliveryman = $deliveryman_id !== null
                ? assign_delivery_bag_to_deliveryman($pdo, $bag_id, (int)$deliveryman_id)
                : null;
            $pdo->commit();
            $_SESSION['assign_notice'] = [
                'type' => 'success',
                'message' => 'Bag ' . $bag_code . ' created with ' . count($shipments) . ' shipment(s).' .
                    ($assigned_deliveryman !== null ? ' Assigned to ' . $assigned_deliveryman . '.' : ''),
            ];
        } elseif ($action === 'assign_bag') {
            $bag_id = filter_var($_POST['bag_id'] ?? null, FILTER_VALIDATE_INT);
            $deliveryman_id = filter_var($_POST['deliveryman_id'] ?? null, FILTER_VALIDATE_INT);
            if ($bag_id === false || $bag_id === null || $bag_id <= 0 ||
                $deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0) {
                throw new InvalidArgumentException('Select a bag and an active deliveryman.');
            }
            $pdo->beginTransaction();
            $rider_name = assign_delivery_bag_to_deliveryman($pdo, (int)$bag_id, (int)$deliveryman_id);
            $pdo->commit();
            $_SESSION['assign_notice'] = ['type' => 'success', 'message' => 'Bag assigned to ' . $rider_name . '.'];
        } else {
            throw new InvalidArgumentException('Invalid assignment action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['assign_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Admin assignment action failed: ' . $e->getMessage());
        $_SESSION['assign_notice'] = ['type' => 'danger', 'message' => 'Assignment could not be saved. Please try again.'];
    }
    header('Location: assign.php?tab=' . $redirect_tab);
    exit;
}

$order_search = is_string($_GET['order_search'] ?? null) ? trim($_GET['order_search']) : '';
$order_results = [];
if ($active_tab === 'orders' && $order_search !== '') {
    $search_value = ltrim($order_search, '#');
    if (preg_match('/^([0-9]+)[A-Z]$/i', $search_value, $shipment_match)) {
        $search_value = $shipment_match[1];
    }
    $order_stmt = $pdo->prepare("
        SELECT id, order_number, fullname, phone, order_status, grand_total, created_at
        FROM orders
        WHERE CAST(id AS CHAR) = :id_search OR order_number = :number_search
            OR fullname LIKE :customer_search OR phone LIKE :phone_search
        ORDER BY id DESC LIMIT 50
    ");
    $order_stmt->execute([
        ':id_search' => $search_value,
        ':number_search' => $search_value,
        ':customer_search' => '%' . $search_value . '%',
        ':phone_search' => '%' . $search_value . '%',
    ]);
    $order_results = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$packed_orders = $pdo->query("
    SELECT o.id, o.order_number, o.fullname, o.phone, o.address, o.shipping_area, o.order_status,
        o.payment_method, o.grand_total
    FROM orders o
    WHERE o.order_status = 'packed_done'
        AND NOT EXISTS (SELECT 1 FROM order_quantity q WHERE q.order_id = o.id AND q.status IN ('in_bag','delivering','delivered'))
    ORDER BY o.created_at ASC, o.id ASC
")->fetchAll(PDO::FETCH_ASSOC);
$packed_issues = $pdo->query("
    SELECT i.id, i.order_id, i.shipment_number, i.status, i.shipment_status,
        o.order_number, o.fullname, o.phone, o.address, o.shipping_area, o.payment_method
    FROM order_issues i JOIN orders o ON o.id = i.order_id
    WHERE i.status <> 'rejected' AND i.shipment_status = 'packed'
        AND NOT EXISTS (SELECT 1 FROM order_quantity q WHERE q.issue_id = i.id AND q.status IN ('in_bag','delivering','delivered'))
    ORDER BY i.created_at ASC, i.id ASC
")->fetchAll(PDO::FETCH_ASSOC);
$deliverymen = $pdo->query("SELECT id, customar_name, phone FROM deliveryman WHERE status = 'active' ORDER BY customar_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$bags = $pdo->query("
    SELECT b.id, b.bag_code, b.bag_name, b.zone, b.order_quantity, b.notes, b.deliveryman_id,
        b.status, b.return_submitted, b.created_at, d.customar_name AS deliveryman_name, d.phone AS deliveryman_phone
    FROM delivery_bags b LEFT JOIN deliveryman d ON d.id = b.deliveryman_id
    ORDER BY b.created_at DESC, b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Assign';
$current_page = 'assign';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center px-2 py-3"></div>
<div class="px-2">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'orders' ? 'active' : ''; ?>" href="assign.php?tab=orders">Order</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'ready' ? 'active' : ''; ?>" href="assign.php?tab=ready">Ready to Delivery<?php if ($ready_for_delivery_count > 0): ?> <span class="badge text-bg-warning ms-1"><?= $ready_for_delivery_count; ?></span><?php endif; ?></a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'bags' ? 'active' : ''; ?>" href="assign.php?tab=bags">Bag</a></li>
    </ul>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($active_tab === 'orders'): ?>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <form method="get" action="assign.php" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="orders">
                <div class="col"><label for="orderSearch" class="form-label">Search order ID, shipment/order number, customer or phone</label><input type="search" class="form-control" id="orderSearch" name="order_search" value="<?= htmlspecialchars($order_search, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="col-auto"><button class="btn btn-success" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            </form>
        </div></div>
        <?php if ($order_search !== ''): ?>
            <?php if (!$order_results): ?><div class="alert alert-info">No matching order found.</div>
            <?php else: ?><div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Phone</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>
                <?php foreach ($order_results as $order): ?><tr>
                    <td class="fw-semibold">#<?= htmlspecialchars(order_shipment_number((string)$order['order_number']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($order['fullname'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars(order_status_label((string)$order['order_status']), ENT_QUOTES, 'UTF-8'); ?></td><td>৳<?= number_format((float)$order['grand_total'], 2); ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-primary" href="order_details.php?id=<?= (int)$order['id']; ?>">View order</a></td>
                </tr><?php endforeach; ?>
                </tbody></table></div></div><?php endif; ?>
        <?php endif; ?>
    <?php elseif ($active_tab === 'ready'): ?>
        <form method="post" class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Pack Done / Ready to Delivery</div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label" for="bagZone">Zone / area</label><input class="form-control" id="bagZone" name="zone" maxlength="180" required></div>
                    <div class="col-md-6"><label class="form-label" for="createBagDeliveryman">Deliveryman (optional)</label>
                        <select class="form-select" id="createBagDeliveryman" name="deliveryman_id">
                            <option value="">No deliveryman (assign later)</option>
                            <?php foreach ($deliverymen as $deliveryman): ?>
                                <option value="<?= (int)$deliveryman['id']; ?>"><?= htmlspecialchars($deliveryman['customar_name'] . ' · ' . $deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="create_bag">
                <div class="table-responsive"><table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th></th><th>Shipment</th><th>Customer</th><th>Address</th><th>Area</th><th>Type</th><th>Tk</th></tr></thead><tbody>
                    <?php foreach ($packed_orders as $order): $shipping_area = order_shipping_area_label((string)($order['shipping_area'] ?? '')); ?>
                        <tr><td><input class="form-check-input" type="checkbox" name="shipments[]" value="order:<?= (int)$order['id']; ?>" aria-label="Select order"></td>
                            <td class="fw-semibold">#<?= htmlspecialchars(order_shipment_number((string)$order['order_number']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($order['fullname'] . ' · ' . $order['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)$order['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($shipping_area, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= strtoupper((string)($order['payment_method'] ?? 'COD')) === 'COD' ? 'COD' : 'Online Paid'; ?></td>
                            <td>৳<?= number_format((float)$order['grand_total'], 2); ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach ($packed_issues as $issue): $shipping_area = order_shipping_area_label((string)($issue['shipping_area'] ?? '')); ?>
                        <?php $issue_number = $issue['shipment_number'] ?: order_shipment_number((string)$issue['order_number'], 'B'); ?>
                        <tr><td><input class="form-check-input" type="checkbox" name="shipments[]" value="issue:<?= (int)$issue['id']; ?>" aria-label="Select issue shipment"></td>
                            <td class="fw-semibold">#<?= htmlspecialchars($issue_number, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($issue['fullname'] . ' · ' . $issue['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)$issue['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($shipping_area, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= strtoupper((string)($issue['payment_method'] ?? 'COD')) === 'COD' ? 'COD' : 'Online Paid'; ?><small class="d-block text-muted">Issue shipment</small></td>
                            <td><span class="text-muted" title="Issue shipments do not have a separate order total">—</span></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$packed_orders && !$packed_issues): ?><tr><td colspan="7" class="text-center text-muted py-4">There are no packed shipments available.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <button class="btn btn-success" type="submit"><i class="bi bi-bag-plus me-1"></i>Create bag from selected shipments</button>
            </div>
        </form>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Bag list</div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Bag Id</th><th>Zone</th><th>Shipments</th><th>Status</th><th>Deliveryman</th><th>Created</th><th></th></tr></thead><tbody>
                <?php if (!$bags): ?><tr><td colspan="8" class="text-center text-muted py-4">No bags created yet.</td></tr>
                <?php else: foreach ($bags as $bag): ?><tr>

                    <td>#<?= str_pad((string)$bag['id'], 2, '0', STR_PAD_LEFT); ?></td>
                    <td><?= htmlspecialchars($bag['zone'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= (int)$bag['order_quantity']; ?></td>
                    <td><span class="badge text-bg-<?= $bag['status'] === 'closed' ? 'secondary' : ($bag['status'] === 'assigned' ? 'primary' : 'warning'); ?>"><?= htmlspecialchars(ucfirst($bag['status']), ENT_QUOTES, 'UTF-8'); ?><?= (int)$bag['return_submitted'] === 1 ? ' · submitted' : ''; ?></span></td>
                    <td><?php if ($bag['status'] === 'open' && $deliverymen): ?>
                        <form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="assign_bag"><input type="hidden" name="bag_id" value="<?= (int)$bag['id']; ?>">
                            <select class="form-select form-select-sm" name="deliveryman_id" required><option value="">Select deliveryman</option><?php foreach ($deliverymen as $deliveryman): ?><option value="<?= (int)$deliveryman['id']; ?>"><?= htmlspecialchars($deliveryman['customar_name'] . ' · ' . $deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-primary" type="submit">Assign</button></form>
                    <?php elseif ($bag['deliveryman_id']): ?><a href="deliveryman_details.php?id=<?= (int)$bag['deliveryman_id']; ?>"><?= htmlspecialchars($bag['deliveryman_name'] . ' · ' . $bag['deliveryman_phone'], ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?>—<?php endif; ?></td>
                    <td><?= !empty($bag['created_at']) ? date('d M Y, h:i A', strtotime($bag['created_at'])) : ''; ?></td><td><a class="btn btn-sm btn-outline-secondary" href="bag_details.php?id=<?= (int)$bag['id']; ?>">View</a></td>
                </tr><?php endforeach; endif; ?>
                </tbody></table></div>
        </div>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
