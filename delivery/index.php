<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../core/reseller.php';
ensure_reseller_tables($pdo);
$deliveryman = require_deliveryman();
$deliveryman_id = (int)$deliveryman['id'];
$csrf_token = $_SESSION['delivery_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['delivery_csrf'] = $csrf_token;
$notice = is_array($_SESSION['delivery_notice'] ?? null) ? $_SESSION['delivery_notice'] : null;
unset($_SESSION['delivery_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload and try again.');
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $bag_id = filter_var($_POST['bag_id'] ?? null, FILTER_VALIDATE_INT);
        if ($bag_id === false || $bag_id === null || $bag_id <= 0) {
            throw new InvalidArgumentException('Choose a valid bag.');
        }

        if ($action === 'deliver') {
            $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
            if ($item_id === false || $item_id === null || $item_id <= 0) {
                throw new InvalidArgumentException('Choose a valid shipment.');
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT q.*, o.payment_method, o.grand_total
                FROM order_quantity q
                JOIN delivery_bags b ON b.id = q.bag_id
                LEFT JOIN orders o ON o.id = q.order_id
                WHERE q.id = :item_id AND q.bag_id = :bag_id
                    AND b.deliveryman_id = :rider_id AND b.status = 'assigned'
                    AND b.return_submitted = 0 AND q.status = 'delivering'
                    AND NOT EXISTS (
                        SELECT 1 FROM delivery_bag_cash_collections c
                        WHERE c.bag_id = b.id AND c.status IN ('pending', 'confirmed')
                    )
                FOR UPDATE
            ");
            $stmt->execute([':item_id' => $item_id, ':bag_id' => $bag_id, ':rider_id' => $deliveryman_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                throw new InvalidArgumentException('This shipment is not currently assigned to you.');
            }
            $received = null;
            if ($item['shipment_type'] === 'order' && strtoupper((string)$item['payment_method']) === 'COD') {
                $received_input = filter_var($_POST['cash_received'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($received_input === false || $received_input < 0 ||
                    abs((float)$received_input - (float)$item['expected_cash']) > 0.01) {
                    throw new InvalidArgumentException('Enter the exact COD amount received before issuing the receipt.');
                }
                $received = (float)$received_input;
            }
            $receipt_no = $received !== null ? delivery_receipt_number($item_id) : null;
            $update_item = $pdo->prepare("
                UPDATE order_quantity
                SET status = 'delivered', cash_received = :cash, cash_receipt_no = :receipt, delivered_at = NOW()
                WHERE id = :id AND status = 'delivering'
            ");
            $update_item->execute([':cash' => $received, ':receipt' => $receipt_no, ':id' => $item_id]);
            if ($item['shipment_type'] === 'order') {
                $pdo->prepare("UPDATE orders SET order_status = 'delivered' WHERE id = :id")
                    ->execute([':id' => (int)$item['order_id']]);
                $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (:id, 'delivered')")
                    ->execute([':id' => (int)$item['order_id']]);
                record_reseller_order_commission($pdo, (int)$item['order_id']);
            } else {
                $pdo->prepare("UPDATE order_issues SET shipment_status = 'delivered' WHERE id = :id")
                    ->execute([':id' => (int)$item['issue_id']]);
                $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:id, 'delivered')")
                    ->execute([':id' => (int)$item['issue_id']]);
                $returned_stmt = $pdo->prepare("
                    SELECT product_id, SUM(quantity) AS quantity
                    FROM order_issue_items
                    WHERE issue_id = :id AND direction = 'take' AND product_id IS NOT NULL
                    GROUP BY product_id
                ");
                $returned_stmt->execute([':id' => (int)$item['issue_id']]);
                $restock = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');
                foreach ($returned_stmt->fetchAll(PDO::FETCH_ASSOC) as $returned) {
                    $restock->execute([':quantity' => (int)$returned['quantity'], ':product_id' => (int)$returned['product_id']]);
                }
            }
            $pdo->commit();
            $_SESSION['delivery_notice'] = [
                'type' => 'success',
                'message' => $receipt_no ? 'Cash recorded and receipt issued.' : 'Shipment marked as delivered.',
            ];
        } elseif ($action === 'submit_cash') {
            $collector_id = filter_var($_POST['collector_id'] ?? null, FILTER_VALIDATE_INT);
            if ($collector_id === false || $collector_id === null || $collector_id <= 0) {
                throw new InvalidArgumentException('Choose a cash collector before submitting cash.');
            }
            $pdo->beginTransaction();
            $bag_stmt = $pdo->prepare("SELECT id FROM delivery_bags WHERE id = :id AND deliveryman_id = :rider AND status = 'assigned' AND return_submitted = 0 FOR UPDATE");
            $bag_stmt->execute([':id' => $bag_id, ':rider' => $deliveryman_id]);
            if (!$bag_stmt->fetchColumn()) {
                throw new InvalidArgumentException('This bag is not available for cash submission.');
            }
            $collector_stmt = $pdo->prepare('SELECT name FROM cash_collectors WHERE id = :id FOR UPDATE');
            $collector_stmt->execute([':id' => $collector_id]);
            $collector_name = $collector_stmt->fetchColumn();
            if (!is_string($collector_name)) {
                throw new InvalidArgumentException('The selected cash collector is no longer available. Reload and try again.');
            }
            $amount_stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_received), 0) FROM order_quantity WHERE bag_id = :bag AND status = 'delivered'");
            $amount_stmt->execute([':bag' => $bag_id]);
            $amount = (float)$amount_stmt->fetchColumn();
            if ($amount <= 0) {
                throw new InvalidArgumentException('There is no collected COD cash to submit for this bag.');
            }
            $pending_stmt = $pdo->prepare("SELECT 1 FROM delivery_bag_cash_collections WHERE bag_id = :bag AND status = 'pending' LIMIT 1");
            $pending_stmt->execute([':bag' => $bag_id]);
            if ($pending_stmt->fetchColumn()) {
                throw new InvalidArgumentException('A cash collection request is already awaiting manager review.');
            }
            $note = trim((string)($_POST['rider_note'] ?? ''));
            $pdo->prepare("
                INSERT INTO delivery_bag_cash_collections
                    (bag_id, deliveryman_id, collector_id, collector_name, submitted_amount, rider_note, status)
                VALUES (:bag, :rider, :collector_id, :collector_name, :amount, :note, 'pending')
            ")->execute([
                ':bag' => $bag_id,
                ':rider' => $deliveryman_id,
                ':collector_id' => $collector_id,
                ':collector_name' => $collector_name,
                ':amount' => $amount,
                ':note' => $note !== '' ? $note : null,
            ]);
            $pdo->commit();
            $_SESSION['delivery_notice'] = ['type' => 'success', 'message' => 'Cash collection submitted to the manager for review.'];
        } elseif ($action === 'submit_bag') {
            $pdo->beginTransaction();
            $bag_stmt = $pdo->prepare("SELECT id FROM delivery_bags WHERE id = :id AND deliveryman_id = :rider AND status = 'assigned' AND return_submitted = 0 FOR UPDATE");
            $bag_stmt->execute([':id' => $bag_id, ':rider' => $deliveryman_id]);
            if (!$bag_stmt->fetchColumn()) {
                throw new InvalidArgumentException('This bag is not available for return submission.');
            }
            $amount_stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_received), 0) FROM order_quantity WHERE bag_id = :bag AND status = 'delivered'");
            $amount_stmt->execute([':bag' => $bag_id]);
            $cash_amount = (float)$amount_stmt->fetchColumn();
            if ($cash_amount > 0) {
                $review_stmt = $pdo->prepare("
                    SELECT status FROM delivery_bag_cash_collections
                    WHERE bag_id = :bag ORDER BY id DESC LIMIT 1
                ");
                $review_stmt->execute([':bag' => $bag_id]);
                if ($review_stmt->fetchColumn() !== 'confirmed') {
                    throw new InvalidArgumentException('The manager must confirm the cash collection before you submit the bag.');
                }
            }
            $pdo->prepare("UPDATE delivery_bags SET return_submitted = 1 WHERE id = :id")->execute([':id' => $bag_id]);
            $pdo->commit();
            $_SESSION['delivery_notice'] = ['type' => 'success', 'message' => 'Bag and undelivered parcels submitted to the manager.'];
        } else {
            throw new InvalidArgumentException('Invalid delivery action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['delivery_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Deliveryman workflow failed: ' . $e->getMessage());
        $_SESSION['delivery_notice'] = ['type' => 'danger', 'message' => 'The delivery action could not be completed. Please try again.'];
    }
    $return_tab = is_string($_POST['return_tab'] ?? null) ? $_POST['return_tab'] : '';
    $tab = in_array($return_tab, ['delivering', 'delivered', 'cash-collection', 'bag-submit'], true)
        ? $return_tab
        : 'delivering';
    header('Location: index.php?tab=' . $tab);
    exit;
}

$requested_tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : '';
$active_tab = in_array($requested_tab, ['delivering', 'delivered', 'cash-collection', 'bag-submit'], true)
    ? $requested_tab
    : 'delivering';
$stmt = $pdo->prepare("
    SELECT q.*, b.bag_code, b.bag_name, b.zone, b.return_submitted, b.status AS bag_status, o.order_number,
        o.payment_method, o.grand_total, i.issue_type
    FROM order_quantity q
    JOIN delivery_bags b ON b.id = q.bag_id
    LEFT JOIN orders o ON o.id = q.order_id
    LEFT JOIN order_issues i ON i.id = q.issue_id
    WHERE b.deliveryman_id = :rider
        AND (b.status = 'assigned' OR (:show_closed = 1 AND b.status = 'closed'))
    ORDER BY b.created_at ASC, q.id ASC
");
$stmt->execute([':rider' => $deliveryman_id, ':show_closed' => $active_tab === 'delivered' ? 1 : 0]);
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$visible_shipments = array_values(array_filter($shipments, static function (array $item) use ($active_tab): bool {
    if ($active_tab === 'delivering') {
        return $item['status'] === 'delivering' && (int)$item['return_submitted'] === 0;
    }
    if ($active_tab === 'delivered') {
        return $item['status'] === 'delivered' || (int)$item['return_submitted'] === 1;
    }
    return false;
}));
$cash_stmt = $pdo->prepare('SELECT * FROM delivery_bag_cash_collections WHERE deliveryman_id = :rider ORDER BY id DESC');
$cash_stmt->execute([':rider' => $deliveryman_id]);
$collectors = $pdo->query('SELECT id, name FROM cash_collectors ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$cash_requests = [];
foreach ($cash_stmt->fetchAll(PDO::FETCH_ASSOC) as $cash_request) {
    $cash_requests[(int)$cash_request['bag_id']][] = $cash_request;
}
$items_by_shipment = [];
foreach ($visible_shipments as $shipment) {
    if ($shipment['shipment_type'] === 'order') {
        $line_stmt = $pdo->prepare("
            SELECT oi.product_code, oi.size, oi.quantity, oi.price, p.name
            FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :id ORDER BY oi.id
        ");
        $line_stmt->execute([':id' => (int)$shipment['order_id']]);
    } else {
        $line_stmt = $pdo->prepare("
            SELECT direction, product_name AS name, product_code, size, quantity, NULL AS price
            FROM order_issue_items WHERE issue_id = :id ORDER BY id
        ");
        $line_stmt->execute([':id' => (int)$shipment['issue_id']]);
    }
    $items_by_shipment[(int)$shipment['id']] = $line_stmt->fetchAll(PDO::FETCH_ASSOC);
}
$bag_ids = [];
foreach ($shipments as $shipment) {
    $bag_ids[(int)$shipment['bag_id']] = true;
}
$bag_totals = [];
foreach (array_keys($bag_ids) as $current_bag_id) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(cash_received), 0) AS cash_total,
            SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) AS pending_count
        FROM order_quantity WHERE bag_id = :bag
    ");
    $stmt->execute([':bag' => $current_bag_id]);
    $bag_totals[$current_bag_id] = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Delivery · AmarShop</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-3">
<header class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h4 mb-0">Delivery</h1><div class="small text-muted"><?= htmlspecialchars($deliveryman['customar_name'] . ' · ' . $deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?></div></div><a class="btn btn-outline-secondary btn-sm" href="logout.php">Log out</a></header>
<?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'delivering' ? 'active' : ''; ?>" href="index.php?tab=delivering">Delivering</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'delivered' ? 'active' : ''; ?>" href="index.php?tab=delivered">Delivered</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'cash-collection' ? 'active' : ''; ?>" href="index.php?tab=cash-collection">Cash Collection</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'bag-submit' ? 'active' : ''; ?>" href="index.php?tab=bag-submit">Bag Submit</a></li>
</ul>
<?php if (in_array($active_tab, ['delivering', 'delivered'], true) && !$visible_shipments): ?>
    <div class="card"><div class="card-body text-center text-muted py-5"><?= $active_tab === 'delivering' ? 'No shipments are assigned for delivery.' : 'No delivered shipments yet.'; ?></div></div>
<?php endif; ?>
<?php foreach ($visible_shipments as $shipment): ?>
    <article class="card shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between gap-2"><div><h2 class="h5 mb-1">#<?= htmlspecialchars($shipment['shipment_number'], ENT_QUOTES, 'UTF-8'); ?> <span class="badge text-bg-light"><?= $shipment['shipment_type'] === 'issue' ? 'Issue' : 'Order'; ?></span></h2><div class="small text-muted">Bag #<?= str_pad((string)$shipment['bag_id'], 2, '0', STR_PAD_LEFT); ?> · <?= htmlspecialchars($shipment['zone'], ENT_QUOTES, 'UTF-8'); ?></div></div>
            <span class="badge text-bg-<?= $shipment['status'] === 'delivered' ? 'success' : ($shipment['status'] === 'returned' ? 'secondary' : 'primary'); ?> align-self-start"><?= htmlspecialchars(ucfirst($shipment['status']), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="mt-3 fw-semibold"><?= htmlspecialchars($shipment['customer_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="small"><?= htmlspecialchars($shipment['customer_phone'], ENT_QUOTES, 'UTF-8'); ?></div><div class="text-break small mb-3"><?= htmlspecialchars($shipment['address'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Code / size</th><th>Quantity</th></tr></thead><tbody>
        <?php foreach ($items_by_shipment[(int)$shipment['id']] ?? [] as $line): ?><tr>
            <td><?= htmlspecialchars(($line['name'] ?? $line['product_code'] ?? 'Product') . (!empty($line['direction']) ? ' · ' . ($line['direction'] === 'give' ? 'Give' : 'Collect') : ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?= htmlspecialchars($line['product_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?><?= !empty($line['size']) ? ' · ' . htmlspecialchars($line['size'], ENT_QUOTES, 'UTF-8') : ''; ?></td>
            <td><?= (int)$line['quantity']; ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php if ($active_tab === 'delivering' && $shipment['status'] === 'delivering' && (int)$shipment['return_submitted'] === 0): ?>
            <?php $is_cod = $shipment['shipment_type'] === 'order' && strtoupper((string)$shipment['payment_method']) === 'COD'; ?>
            <?php $bag_cash_state = $cash_requests[(int)$shipment['bag_id']] ?? []; $latest_bag_cash = $bag_cash_state[0] ?? null; ?>
            <?php if (!$latest_bag_cash || !in_array($latest_bag_cash['status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" class="row g-2 align-items-end"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="deliver"><input type="hidden" name="item_id" value="<?= (int)$shipment['id']; ?>"><input type="hidden" name="bag_id" value="<?= (int)$shipment['bag_id']; ?>"><input type="hidden" name="return_tab" value="delivering">
                <?php if ($is_cod): ?><div class="col-sm-5"><label class="form-label" for="cash<?= (int)$shipment['id']; ?>">Cash received (৳<?= number_format((float)$shipment['expected_cash'], 2); ?>)</label><input class="form-control" id="cash<?= (int)$shipment['id']; ?>" name="cash_received" type="number" step="0.01" min="0" value="<?= number_format((float)$shipment['expected_cash'], 2, '.', ''); ?>" required></div><?php endif; ?>
                <div class="col-auto"><button class="btn btn-success" type="submit"><?= $is_cod ? 'Cash received & issue receipt' : 'Mark as delivered'; ?></button></div>
            </form>
            <?php else: ?><div class="text-muted small">Delivery actions are paused while cash collection is reviewed or after it is confirmed.</div><?php endif; ?>
        <?php elseif ($shipment['status'] === 'delivered'): ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="text-success small">Delivered<?= $shipment['cash_received'] !== null ? ' · Collected ৳' . number_format((float)$shipment['cash_received'], 2) : ''; ?></div>
                <?php if (!empty($shipment['cash_receipt_no'])): ?><a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="receipt.php?id=<?= (int)$shipment['id']; ?>">Cash receipt</a><?php endif; ?></div>
        <?php elseif ($shipment['status'] === 'returned'): ?><div class="text-muted small">Returned to Packed when bag #<?= str_pad((string)$shipment['bag_id'], 2, '0', STR_PAD_LEFT); ?> was closed.</div>
        <?php elseif ((int)$shipment['return_submitted'] === 1 && $shipment['bag_status'] === 'closed'): ?><div class="text-success small">This bag is closed.</div>
        <?php elseif ((int)$shipment['return_submitted'] === 1): ?><div class="alert alert-info py-2 mb-0">Bag return submitted; waiting for manager to close it.</div><?php endif; ?>
    </div></article>
<?php endforeach; ?>
<?php if ($active_tab === 'delivered'): foreach (array_keys($bag_ids) as $current_bag_id): ?>
    <?php
    $bag_shipments = array_values(array_filter($shipments, static fn(array $item): bool => (int)$item['bag_id'] === $current_bag_id));
    if (!$bag_shipments) { continue; }
    $bag = $bag_shipments[0];
    $total_cash = (float)($bag_totals[$current_bag_id]['cash_total'] ?? 0);
    $last_request = ($cash_requests[$current_bag_id] ?? [])[0] ?? null;
    ?>
    <section class="card mb-3"><div class="card-body">
        <h2 class="h6">Bag #<?= str_pad((string)$current_bag_id, 2, '0', STR_PAD_LEFT); ?> · <?= htmlspecialchars($bag['bag_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div>Delivered COD cash: <strong>৳<?= number_format($total_cash, 2); ?></strong></div>
        <?php if ($last_request): ?><p class="small mb-2">Collection request: <?= htmlspecialchars(ucfirst($last_request['status']), ENT_QUOTES, 'UTF-8'); ?> · ৳<?= number_format((float)$last_request['submitted_amount'], 2); ?><?= !empty($last_request['collector_name']) ? ' · Collector: ' . htmlspecialchars($last_request['collector_name'], ENT_QUOTES, 'UTF-8') : ''; ?><?= !empty($last_request['manager_note']) ? ' · ' . htmlspecialchars($last_request['manager_note'], ENT_QUOTES, 'UTF-8') : ''; ?></p><?php endif; ?>
        <?php if ((int)$bag['return_submitted'] === 1 && $bag['bag_status'] === 'closed'): ?>
            <div class="text-success small">Bag closed by manager.</div>
        <?php elseif ((int)$bag['return_submitted'] === 1): ?>
            <div class="text-success small">Bag submitted to manager; waiting for bag closure.</div>
        <?php endif; ?>
    </div></section>
<?php endforeach; endif; ?>

<?php if ($active_tab === 'cash-collection'): ?>
    <?php $cash_bags_count = 0; ?>
    <?php foreach (array_keys($bag_ids) as $current_bag_id): ?>
        <?php
        $bag_shipments = array_values(array_filter($shipments, static fn(array $item): bool => (int)$item['bag_id'] === $current_bag_id));
        if (!$bag_shipments) { continue; }
        $bag = $bag_shipments[0];
        $total_cash = (float)($bag_totals[$current_bag_id]['cash_total'] ?? 0);
        if ($total_cash <= 0) { continue; }
        $cash_bags_count++;
        $last_request = ($cash_requests[$current_bag_id] ?? [])[0] ?? null;
        ?>
        <section class="card mb-3"><div class="card-body">
            <h2 class="h6">Bag #<?= str_pad((string)$current_bag_id, 2, '0', STR_PAD_LEFT); ?> · <?= htmlspecialchars($bag['bag_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="mb-2">Delivered COD cash: <strong>৳<?= number_format($total_cash, 2); ?></strong></div>
            <?php if ($last_request): ?><p class="small mb-3">Latest submission: <?= htmlspecialchars(ucfirst($last_request['status']), ENT_QUOTES, 'UTF-8'); ?><?= !empty($last_request['collector_name']) ? ' · Collector: ' . htmlspecialchars($last_request['collector_name'], ENT_QUOTES, 'UTF-8') : ''; ?> · ৳<?= number_format((float)$last_request['submitted_amount'], 2); ?><?= !empty($last_request['manager_note']) ? ' · ' . htmlspecialchars($last_request['manager_note'], ENT_QUOTES, 'UTF-8') : ''; ?></p><?php endif; ?>
            <?php if ((int)$bag['return_submitted'] === 1): ?>
                <div class="text-muted small">This bag has already been submitted.</div>
            <?php elseif ($last_request && $last_request['status'] === 'confirmed'): ?>
                <div class="text-success small">Cash confirmed. Use the Bag Submit tab to submit this bag.</div>
            <?php elseif ($last_request && $last_request['status'] === 'pending'): ?>
                <div class="text-muted small">Waiting for manager to review the submitted cash.</div>
            <?php elseif ($collectors): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="submit_cash">
                    <input type="hidden" name="bag_id" value="<?= $current_bag_id; ?>">
                    <input type="hidden" name="return_tab" value="cash-collection">
                    <label class="form-label" for="collector<?= $current_bag_id; ?>">Cash collector</label>
                    <select class="form-select mb-2" id="collector<?= $current_bag_id; ?>" name="collector_id" required>
                        <option value="">Select collector</option>
                        <?php foreach ($collectors as $collector): ?><option value="<?= (int)$collector['id']; ?>"><?= htmlspecialchars($collector['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                    </select>
                    <label class="form-label" for="note<?= $current_bag_id; ?>">Note (optional)</label>
                    <input class="form-control mb-2" id="note<?= $current_bag_id; ?>" name="rider_note" maxlength="1000">
                    <button class="btn btn-outline-primary" type="submit">Submit cash collection</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning py-2 mb-0">No cash collectors are available. Contact the admin before submitting cash.</div>
            <?php endif; ?>
        </div></section>
    <?php endforeach; ?>
    <?php if ($cash_bags_count === 0): ?><div class="card"><div class="card-body text-center text-muted py-5">There is no collected COD cash to submit.</div></div><?php endif; ?>
<?php endif; ?>

<?php if ($active_tab === 'bag-submit'): ?>
    <?php $bags_to_submit_count = 0; ?>
    <?php foreach (array_keys($bag_ids) as $current_bag_id): ?>
        <?php
        $bag_shipments = array_values(array_filter($shipments, static fn(array $item): bool => (int)$item['bag_id'] === $current_bag_id));
        if (!$bag_shipments) { continue; }
        $bag = $bag_shipments[0];
        $total_cash = (float)($bag_totals[$current_bag_id]['cash_total'] ?? 0);
        $last_request = ($cash_requests[$current_bag_id] ?? [])[0] ?? null;
        $bags_to_submit_count++;
        ?>
        <section class="card mb-3"><div class="card-body">
            <h2 class="h6">Bag #<?= str_pad((string)$current_bag_id, 2, '0', STR_PAD_LEFT); ?> · <?= htmlspecialchars($bag['bag_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="mb-2">Delivered COD cash: <strong>৳<?= number_format($total_cash, 2); ?></strong></div>
            <?php if ((int)$bag['return_submitted'] === 1): ?>
                <div class="text-success small">Bag submitted to manager; waiting for bag closure.</div>
            <?php elseif ($total_cash > 0 && (!$last_request || $last_request['status'] !== 'confirmed')): ?>
                <div class="text-muted small">Submit the collected cash and wait for manager confirmation before submitting this bag.</div>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="submit_bag">
                    <input type="hidden" name="bag_id" value="<?= $current_bag_id; ?>">
                    <input type="hidden" name="return_tab" value="bag-submit">
                    <button class="btn btn-success" type="submit">Submit bag and undelivered parcels</button>
                </form>
            <?php endif; ?>
        </div></section>
    <?php endforeach; ?>
    <?php if ($bags_to_submit_count === 0): ?><div class="card"><div class="card-body text-center text-muted py-5">There are no bags to submit.</div></div><?php endif; ?>
<?php endif; ?>
</main></body></html>
