<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);

$bag_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($bag_id === false || $bag_id === null || $bag_id <= 0) {
    http_response_code(400);
    exit('Invalid bag ID.');
}
$can_review = in_array($_SESSION['user_role'] ?? '', ['CEO', 'MD', 'MANAGER'], true);
$csrf_token = $_SESSION['bag_details_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['bag_details_csrf'] = $csrf_token;
$notice = is_array($_SESSION['bag_details_notice'] ?? null) ? $_SESSION['bag_details_notice'] : null;
unset($_SESSION['bag_details_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload and try again.');
        }
        if (!$can_review) {
            http_response_code(403);
            throw new InvalidArgumentException('Only a manager or senior administrator can review cash and close a bag.');
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $pdo->beginTransaction();
        $bag_stmt = $pdo->prepare('SELECT * FROM delivery_bags WHERE id = :id FOR UPDATE');
        $bag_stmt->execute([':id' => $bag_id]);
        $bag = $bag_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bag) {
            throw new InvalidArgumentException('Bag not found.');
        }
        if ($action === 'review_cash') {
            $collection_id = filter_var($_POST['collection_id'] ?? null, FILTER_VALIDATE_INT);
            $decision = is_string($_POST['decision'] ?? null) ? $_POST['decision'] : '';
            $manager_note = trim((string)($_POST['manager_note'] ?? ''));
            if ($collection_id === false || $collection_id === null || $collection_id <= 0 ||
                !in_array($decision, ['confirmed', 'rejected'], true) || strlen($manager_note) > 1000) {
                throw new InvalidArgumentException('Choose a valid cash collection and decision.');
            }
            $collection_stmt = $pdo->prepare("SELECT * FROM delivery_bag_cash_collections WHERE id = :id AND bag_id = :bag AND status = 'pending' FOR UPDATE");
            $collection_stmt->execute([':id' => $collection_id, ':bag' => $bag_id]);
            $collection = $collection_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$collection) {
                throw new InvalidArgumentException('This cash collection is no longer awaiting review.');
            }
            if ($decision === 'confirmed') {
                $total_stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_received), 0) FROM order_quantity WHERE bag_id = :bag AND status = 'delivered'");
                $total_stmt->execute([':bag' => $bag_id]);
                $actual_total = (float)$total_stmt->fetchColumn();
                if (abs($actual_total - (float)$collection['submitted_amount']) > 0.01) {
                    throw new InvalidArgumentException('Submitted amount does not match the recorded delivery receipts. Reject this request and reconcile it.');
                }
            }
            $pdo->prepare("
                UPDATE delivery_bag_cash_collections
                SET status = :status, manager_note = :note, reviewed_by_admin_id = :admin_id, reviewed_at = NOW()
                WHERE id = :id
            ")->execute([
                ':status' => $decision,
                ':note' => $manager_note !== '' ? $manager_note : null,
                ':admin_id' => (int)$_SESSION['admin_user_id'],
                ':id' => $collection_id,
            ]);
            $pdo->commit();
            $_SESSION['bag_details_notice'] = ['type' => 'success', 'message' => 'Cash collection ' . $decision . '.'];
        } elseif ($action === 'close_bag') {
            if ($bag['status'] !== 'assigned' || (int)$bag['return_submitted'] !== 1) {
                throw new InvalidArgumentException('The rider must submit this assigned bag before it can be closed.');
            }
            $cash_stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_received), 0) FROM order_quantity WHERE bag_id = :bag AND status = 'delivered'");
            $cash_stmt->execute([':bag' => $bag_id]);
            $cash_total = (float)$cash_stmt->fetchColumn();
            if ($cash_total > 0) {
                $collection_stmt = $pdo->prepare('SELECT status FROM delivery_bag_cash_collections WHERE bag_id = :bag ORDER BY id DESC LIMIT 1');
                $collection_stmt->execute([':bag' => $bag_id]);
                if ($collection_stmt->fetchColumn() !== 'confirmed') {
                    throw new InvalidArgumentException('Confirm the rider cash collection before closing the bag.');
                }
            }
            $manifest_stmt = $pdo->prepare("SELECT id, shipment_type, order_id, issue_id FROM order_quantity WHERE bag_id = :bag AND status IN ('in_bag', 'delivering') FOR UPDATE");
            $manifest_stmt->execute([':bag' => $bag_id]);
            $return_items = $manifest_stmt->fetchAll(PDO::FETCH_ASSOC);
            $mark_returned = $pdo->prepare("UPDATE order_quantity SET status = 'returned', returned_at = NOW() WHERE id = :id");
            $pack_order = $pdo->prepare("UPDATE orders SET order_status = 'packed' WHERE id = :id AND order_status <> 'packed'");
            $history_order = $pdo->prepare("INSERT INTO order_status_history (order_id, status, admin_id, admin_name) VALUES (:id, 'packed', :admin_id, :admin_name)");
            $admin_id = (int)($_SESSION['admin_user_id'] ?? $_SESSION['user_id']);
            $admin_name = trim((string)($_SESSION['user_name'] ?? ''));
            if ($admin_name === '') {
                $admin_name = 'Admin #' . $admin_id;
            }
            $delete_order_assignment = $pdo->prepare('DELETE FROM order_delivery_assignments WHERE order_id = :id');
            $pack_issue = $pdo->prepare("UPDATE order_issues SET shipment_status = 'packed' WHERE id = :id AND shipment_status <> 'packed'");
            $history_issue = $pdo->prepare("INSERT INTO order_issue_shipment_history (issue_id, shipment_status) VALUES (:id, 'packed')");
            $delete_issue_assignment = $pdo->prepare('DELETE FROM order_issue_delivery_assignments WHERE issue_id = :id');
            foreach ($return_items as $return_item) {
                $mark_returned->execute([':id' => (int)$return_item['id']]);
                if ($return_item['shipment_type'] === 'order') {
                    $pack_order->execute([':id' => (int)$return_item['order_id']]);
                    if ($pack_order->rowCount() > 0) {
                        $history_order->execute([
                            ':id' => (int)$return_item['order_id'],
                            ':admin_id' => $admin_id,
                            ':admin_name' => $admin_name,
                        ]);
                    }
                    $delete_order_assignment->execute([':id' => (int)$return_item['order_id']]);
                } else {
                    $pack_issue->execute([':id' => (int)$return_item['issue_id']]);
                    if ($pack_issue->rowCount() > 0) {
                        $history_issue->execute([':id' => (int)$return_item['issue_id']]);
                    }
                    $delete_issue_assignment->execute([':id' => (int)$return_item['issue_id']]);
                }
            }
            $pdo->prepare("UPDATE delivery_bags SET status = 'closed', closed_at = NOW() WHERE id = :id")
                ->execute([':id' => $bag_id]);
            $pdo->commit();
            $_SESSION['bag_details_notice'] = ['type' => 'success', 'message' => 'Bag closed. Undelivered shipments were returned to Packed.'];
        } else {
            throw new InvalidArgumentException('Invalid bag action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['bag_details_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Bag review action failed: ' . $e->getMessage());
        $_SESSION['bag_details_notice'] = ['type' => 'danger', 'message' => 'The bag action could not be completed. Please try again.'];
    }
    header('Location: bag_details.php?id=' . (int)$bag_id);
    exit;
}

$bag_stmt = $pdo->prepare("
    SELECT b.*, d.customar_name AS deliveryman_name, d.phone AS deliveryman_phone
    FROM delivery_bags b LEFT JOIN deliveryman d ON d.id = b.deliveryman_id
    WHERE b.id = :id
");
$bag_stmt->execute([':id' => $bag_id]);
$bag = $bag_stmt->fetch(PDO::FETCH_ASSOC);
if (!$bag) {
    http_response_code(404);
    exit('Bag not found.');
}
$manifest_stmt = $pdo->prepare("
    SELECT q.*, o.order_number, o.id AS main_order_id, i.order_id AS issue_order_id
    FROM order_quantity q
    LEFT JOIN orders o ON o.id = q.order_id
    LEFT JOIN order_issues i ON i.id = q.issue_id
    WHERE q.bag_id = :bag ORDER BY q.id
");
$manifest_stmt->execute([':bag' => $bag_id]);
$manifest = $manifest_stmt->fetchAll(PDO::FETCH_ASSOC);
$cash_stmt = $pdo->prepare("
    SELECT c.*, COALESCE(c.collector_name, cc.name) AS collector_name,
        d.customar_name AS deliveryman_name
    FROM delivery_bag_cash_collections c
    JOIN deliveryman d ON d.id = c.deliveryman_id
    LEFT JOIN cash_collectors cc ON cc.id = c.collector_id
    WHERE c.bag_id = :bag ORDER BY c.id DESC
");
$cash_stmt->execute([':bag' => $bag_id]);
$collections = $cash_stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_cash_stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_received), 0) FROM order_quantity WHERE bag_id = :bag AND status = 'delivered'");
$actual_cash_stmt->execute([':bag' => $bag_id]);
$actual_cash = (float)$actual_cash_stmt->fetchColumn();
$page_title = 'Bag Details';
$current_page = 'assign';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center px-2 py-3"><div><h1 class="h4 fw-bold mb-1">Bag #<?= str_pad((string)$bag_id, 2, '0', STR_PAD_LEFT); ?></h1><div class="text-muted"><?= htmlspecialchars($bag['bag_name'] . ' · ' . $bag['zone'], ENT_QUOTES, 'UTF-8'); ?></div></div><a class="btn btn-outline-secondary" href="assign.php?tab=bags">Back to bags</a></div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-4">
        <div><span class="text-muted">Status</span><div class="fw-semibold"><?= htmlspecialchars(ucfirst($bag['status']) . ((int)$bag['return_submitted'] ? ' · Rider submitted' : ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><span class="text-muted">Zone</span><div class="fw-semibold"><?= htmlspecialchars($bag['zone'], ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><span class="text-muted">Shipment count</span><div class="fw-semibold"><?= (int)$bag['order_quantity']; ?></div></div>
        <div><span class="text-muted">Deliveryman</span><div class="fw-semibold"><?= htmlspecialchars(($bag['deliveryman_name'] ?? 'Not assigned') . (!empty($bag['deliveryman_phone']) ? ' · ' . $bag['deliveryman_phone'] : ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><span class="text-muted">Recorded COD</span><div class="fw-semibold">৳<?= number_format($actual_cash, 2); ?></div></div>
    </div></div>
    <div class="card shadow-sm mb-3"><div class="card-header fw-semibold">Bag shipments</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Shipment</th><th>Customer / address</th><th>Type</th><th>Status</th><th>COD received</th><th></th></tr></thead><tbody>
        <?php foreach ($manifest as $item): ?>
            <?php $parent_order_id = (int)($item['order_id'] ?: $item['issue_order_id']); ?>
            <tr><td class="fw-semibold">#<?= htmlspecialchars($item['shipment_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($item['customer_name'] . ' · ' . $item['customer_phone'], ENT_QUOTES, 'UTF-8'); ?><div class="small text-muted"><?= htmlspecialchars($item['address'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                <td><?= ucfirst(htmlspecialchars($item['shipment_type'], ENT_QUOTES, 'UTF-8')); ?></td><td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'))); ?></td>
                <td><?= $item['cash_received'] !== null ? '৳' . number_format((float)$item['cash_received'], 2) : '—'; ?></td><td><a class="btn btn-sm btn-outline-primary" href="order_details.php?id=<?= $parent_order_id; ?>">Order view</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$manifest): ?><tr><td colspan="6" class="text-center text-muted py-4">This bag has no shipments.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    <div class="card shadow-sm mb-3"><div class="card-header fw-semibold">Cash collection review</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Deliveryman</th><th>Collector</th><th>Amount</th><th>Submitted</th><th>Status</th><th>Note / decision</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($collections as $collection): ?><tr><td><?= htmlspecialchars($collection['deliveryman_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars($collection['collector_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td><td>৳<?= number_format((float)$collection['submitted_amount'], 2); ?></td>
            <td><?= !empty($collection['submitted_at']) ? date('d M Y, h:i A', strtotime($collection['submitted_at'])) : ''; ?></td><td><?= ucfirst(htmlspecialchars($collection['status'], ENT_QUOTES, 'UTF-8')); ?></td>
            <td><?= htmlspecialchars(trim(($collection['rider_note'] ?? '') . (!empty($collection['manager_note']) ? ' · ' . $collection['manager_note'] : '')) ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php if ($can_review && $collection['status'] === 'pending'): ?><form method="post" class="d-flex flex-wrap gap-1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="review_cash"><input type="hidden" name="collection_id" value="<?= (int)$collection['id']; ?>">
                <input class="form-control form-control-sm" name="manager_note" maxlength="1000" placeholder="Manager note">
                <button class="btn btn-sm btn-success" name="decision" value="confirmed">Confirm</button><button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Reject</button>
            </form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
        <?php if (!$collections): ?><tr><td colspan="7" class="text-center text-muted py-4">No cash collections submitted.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    <?php if ($can_review && $bag['status'] === 'assigned' && (int)$bag['return_submitted'] === 1): ?>
        <div class="card border-warning"><div class="card-body"><h2 class="h6">Close bag</h2><p class="mb-3">Undelivered parcels will return to Packed and become available for a new bag.</p>
        <form method="post" onsubmit="return confirm('Close this bag and return all undelivered parcels to Packed?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="close_bag"><button class="btn btn-warning" type="submit">Close bag and return undelivered shipments</button></form></div></div>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
