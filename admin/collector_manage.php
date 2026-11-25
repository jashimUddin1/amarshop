<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
require_admin();
ensure_delivery_logistics_tables($pdo);

$csrf_token = $_SESSION['collector_manage_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['collector_manage_csrf'] = $csrf_token;
$notice = is_array($_SESSION['collector_manage_notice'] ?? null) ? $_SESSION['collector_manage_notice'] : null;
unset($_SESSION['collector_manage_notice']);

function collector_manage_valid_name(string $name): bool
{
    $character_count = preg_match_all('/./us', $name);
    return $name !== '' && $character_count !== false && $character_count <= 180;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }

        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'add_collector') {
            $name = trim((string)($_POST['name'] ?? ''));
            if (!collector_manage_valid_name($name)) {
                throw new InvalidArgumentException('Enter a collector name (up to 180 characters).');
            }
            $stmt = $pdo->prepare('INSERT INTO cash_collectors (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
            $_SESSION['collector_manage_notice'] = ['type' => 'success', 'message' => 'Cash collector added.'];
        } elseif ($action === 'edit_collector') {
            $collector_id = filter_var($_POST['collector_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string)($_POST['name'] ?? ''));
            if ($collector_id === false || $collector_id === null || $collector_id <= 0 ||
                !collector_manage_valid_name($name)) {
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
            $_SESSION['collector_manage_notice'] = ['type' => 'success', 'message' => 'Cash collector updated.'];
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
            $_SESSION['collector_manage_notice'] = ['type' => 'success', 'message' => 'Cash collector deleted. Past submissions keep the recorded name.'];
        } else {
            throw new InvalidArgumentException('Invalid collector management action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['collector_manage_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Cash collector could not be managed: ' . $e->getMessage());
        $_SESSION['collector_manage_notice'] = ['type' => 'danger', 'message' => 'The cash collector could not be saved. Please try again.'];
    }
    header('Location: collector_manage.php');
    exit;
}

$stmt = $pdo->query('SELECT id, name, created_at FROM cash_collectors ORDER BY name ASC, id ASC');
$collectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Collector Manage';
$current_page = 'collector-manage';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Collector Manage</h1>
        <p class="small text-muted mb-0">Add, edit, or delete cash collectors.</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCollectorModal">
        <i class="bi bi-person-plus me-1"></i>Add Collector
    </button>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?>
        <div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Collector name</th><th>Added</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (!$collectors): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No cash collectors have been added.</td></tr>
                    <?php else: foreach ($collectors as $collector): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($collector['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= !empty($collector['created_at']) ? date('d M Y', strtotime($collector['created_at'])) : ''; ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCollector<?= (int)$collector['id']; ?>">Edit</button>
                                    <form method="post" onsubmit="return confirm('Delete this cash collector? Past submissions will keep the recorded name.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="collector_id" value="<?= (int)$collector['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete_collector">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php foreach ($collectors as $collector): ?>
        <div class="modal fade" id="editCollector<?= (int)$collector['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="edit_collector">
                    <input type="hidden" name="collector_id" value="<?= (int)$collector['id']; ?>">
                    <div class="modal-header"><h2 class="modal-title h5">Edit Collector</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="collectorName<?= (int)$collector['id']; ?>">Collector name</label>
                        <input class="form-control" id="collectorName<?= (int)$collector['id']; ?>" name="name" maxlength="180" value="<?= htmlspecialchars($collector['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Save changes</button></div>
                </form>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
<div class="modal fade" id="addCollectorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="add_collector">
            <div class="modal-header"><h2 class="modal-title h5">Add Cash Collector</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label" for="newCollectorName">Collector name</label>
                <input class="form-control" id="newCollectorName" name="name" maxlength="180" required>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Add Collector</button></div>
        </form>
    </div></div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
