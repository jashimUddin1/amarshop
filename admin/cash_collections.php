<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);

$is_collector_manage_page = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'collector_manage.php';
$active_tab = $is_collector_manage_page ? 'collectors' : 'collections';
$csrf_token = $_SESSION['cash_collections_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['cash_collections_csrf'] = $csrf_token;
$notice = is_array($_SESSION['cash_collections_notice'] ?? null) ? $_SESSION['cash_collections_notice'] : null;
unset($_SESSION['cash_collections_notice']);

function cash_collections_valid_name(string $name): bool
{
    $character_count = preg_match_all('/./us', $name);
    return $name !== '' && $character_count !== false && $character_count <= 180;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $redirect_page = $is_collector_manage_page ? 'collector_manage.php' : 'cash_collections.php';
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }

        if ($action === 'add_collector') {
            $name = trim((string)($_POST['name'] ?? ''));
            if (!cash_collections_valid_name($name)) {
                throw new InvalidArgumentException('Enter a collector name (up to 180 characters).');
            }
            $stmt = $pdo->prepare('INSERT INTO cash_collectors (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
            $_SESSION['cash_collections_notice'] = ['type' => 'success', 'message' => 'Cash collector added.'];
        } elseif ($action === 'edit_collector') {
            $collector_id = filter_var($_POST['collector_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string)($_POST['name'] ?? ''));
            if ($collector_id === false || $collector_id === null || $collector_id <= 0 ||
                !cash_collections_valid_name($name)) {
                throw new InvalidArgumentException('Choose a collector and enter a valid name (up to 180 characters).');
            }
            $stmt = $pdo->prepare('UPDATE cash_collectors SET name = :name WHERE id = :id');
            $stmt->execute([':name' => $name, ':id' => $collector_id]);
            if ($stmt->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT 1 FROM cash_collectors WHERE id = :id');
                $exists->execute([':id' => $collector_id]);
                if (!$exists->fetchColumn()) {
                    throw new InvalidArgumentException('Cash collector not found.');
                }
            }
            $_SESSION['cash_collections_notice'] = ['type' => 'success', 'message' => 'Cash collector updated.'];
        } elseif ($action === 'delete_collector') {
            $collector_id = filter_var($_POST['collector_id'] ?? null, FILTER_VALIDATE_INT);
            if ($collector_id === false || $collector_id === null || $collector_id <= 0) {
                throw new InvalidArgumentException('Choose a valid cash collector.');
            }
            $pdo->beginTransaction();
            $collector_stmt = $pdo->prepare('SELECT name FROM cash_collectors WHERE id = :id FOR UPDATE');
            $collector_stmt->execute([':id' => $collector_id]);
            $collector_name = $collector_stmt->fetchColumn();
            if (!is_string($collector_name)) {
                throw new InvalidArgumentException('Cash collector not found.');
            }
            $pdo->prepare("
                UPDATE delivery_bag_cash_collections
                SET collector_name = COALESCE(NULLIF(collector_name, ''), :name), collector_id = NULL
                WHERE collector_id = :id
            ")->execute([':name' => $collector_name, ':id' => $collector_id]);
            $pdo->prepare('DELETE FROM cash_collectors WHERE id = :id')->execute([':id' => $collector_id]);
            $pdo->commit();
            $_SESSION['cash_collections_notice'] = ['type' => 'success', 'message' => 'Cash collector deleted. Past submissions keep the recorded name.'];
        } else {
            throw new InvalidArgumentException('Invalid cash collection action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['cash_collections_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Cash collection management failed: ' . $e->getMessage());
        $_SESSION['cash_collections_notice'] = ['type' => 'danger', 'message' => 'The cash collection change could not be saved. Please try again.'];
    }
    header('Location: ' . $redirect_page);
    exit;
}

$collections = $pdo->query("
    SELECT c.id, c.bag_id, c.submitted_amount, c.status, c.rider_note, c.manager_note, c.submitted_at,
        COALESCE(c.collector_name, cc.name) AS collector_name,
        d.customar_name AS deliveryman_name, b.bag_code
    FROM delivery_bag_cash_collections c
    JOIN deliveryman d ON d.id = c.deliveryman_id
    JOIN delivery_bags b ON b.id = c.bag_id
    LEFT JOIN cash_collectors cc ON cc.id = c.collector_id
    ORDER BY CASE WHEN c.status = 'pending' THEN 0 ELSE 1 END, c.submitted_at DESC, c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$collectors = $pdo->query('SELECT id, name, created_at FROM cash_collectors ORDER BY name ASC, id ASC')
    ->fetchAll(PDO::FETCH_ASSOC);

$page_title = $active_tab === 'collectors' ? 'Collector Manage' : 'Cash Collection';
$current_page = $active_tab === 'collectors' ? 'collector-manage' : 'cash-collections';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($active_tab === 'collections'): ?>
        <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Submitted</th><th>Deliveryman</th><th>Collector</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$collections): ?><tr><td colspan="8" class="text-center text-muted py-4">No cash collections submitted yet.</td></tr>
                <?php else: foreach ($collections as $collection): ?>
                    <tr>
                        <td><?= !empty($collection['submitted_at']) ? date('d M Y, h:i A', strtotime($collection['submitted_at'])) : ''; ?></td>
                        <td><?= htmlspecialchars($collection['deliveryman_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($collection['collector_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        
                        <td>৳<?= number_format((float)$collection['submitted_amount'], 2); ?></td>
                        
                        <td><span class="badge text-bg-<?= $collection['status'] === 'pending' ? 'warning' : ($collection['status'] === 'confirmed' ? 'success' : 'secondary'); ?>"><?= htmlspecialchars(ucfirst($collection['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table></div></div>
    <?php else: ?>
        <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Collector name</th><th>Added</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (!$collectors): ?><tr><td colspan="3" class="text-center text-muted py-4">No cash collectors have been added.</td></tr>
                <?php else: foreach ($collectors as $collector): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($collector['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= !empty($collector['created_at']) ? date('d M Y', strtotime($collector['created_at'])) : ''; ?></td>
                        <td><div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCollector<?= (int)$collector['id']; ?>">Edit</button>
                            <form method="post" onsubmit="return confirm('Delete this cash collector? Past submissions will keep the recorded name.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="collector_id" value="<?= (int)$collector['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete_collector">Delete</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table></div></div>
        <?php foreach ($collectors as $collector): ?>
            <div class="modal fade" id="editCollector<?= (int)$collector['id']; ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="edit_collector">
                    <input type="hidden" name="collector_id" value="<?= (int)$collector['id']; ?>">
                    <div class="modal-header"><h2 class="modal-title h5">Edit Collector</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body"><label class="form-label" for="collectorName<?= (int)$collector['id']; ?>">Collector name</label><input class="form-control" id="collectorName<?= (int)$collector['id']; ?>" name="name" maxlength="180" value="<?= htmlspecialchars($collector['name'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                    <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Save changes</button></div>
                </form>
            </div></div></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php if ($active_tab === 'collectors'): ?>
    <div class="modal fade" id="addCollectorModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="add_collector">
            <div class="modal-header"><h2 class="modal-title h5">Add Cash Collector</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><label class="form-label" for="newCollectorName">Collector name</label><input class="form-control" id="newCollectorName" name="name" maxlength="180" required></div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Add Collector</button></div>
        </form>
    </div></div></div>
<?php endif; ?>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
