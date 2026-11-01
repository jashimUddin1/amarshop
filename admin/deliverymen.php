<?php
require_once 'auth/config.php';
require_admin();

$message = '';
$message_type = '';
$csrf_token = $_SESSION['deliverymen_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['deliverymen_csrf'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    if (!is_string($submitted_csrf) || !hash_equals($csrf_token, $submitted_csrf)) {
        http_response_code(400);
        exit('Invalid request token.');
    }

    try {
        if ($action === 'add') {
            $name = trim((string)($_POST['customar_name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            if ($name === '' || $phone === '' ||
                ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                throw new InvalidArgumentException('Enter a name, phone number, and valid email if provided.');
            }
            $stmt = $pdo->prepare('INSERT INTO deliveryman (customar_name, phone, email) VALUES (:name, :phone, :email)');
            $stmt->execute([':name' => $name, ':phone' => $phone, ':email' => $email !== '' ? $email : null]);
            $message = 'Deliveryman added successfully.';
            $message_type = 'success';
        } elseif ($action === 'toggle_status') {
            $deliveryman_id = filter_var($_POST['deliveryman_id'] ?? null, FILTER_VALIDATE_INT);
            if ($deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0) {
                throw new InvalidArgumentException('Choose a valid deliveryman.');
            }
            $stmt = $pdo->prepare("
                UPDATE deliveryman
                SET status = IF(status = 'active', 'inactive', 'active')
                WHERE id = :id
            ");
            $stmt->execute([':id' => $deliveryman_id]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Deliveryman not found or status did not change.');
            }
            $message = 'Deliveryman status updated.';
            $message_type = 'success';
        } else {
            throw new InvalidArgumentException('Invalid deliveryman action.');
        }
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
        $message_type = 'warning';
    } catch (Throwable $e) {
        error_log('Deliveryman record could not be updated: ' . $e->getMessage());
        $message = 'Deliveryman record could not be updated. Please try again.';
        $message_type = 'danger';
    }
}

$stmt = $pdo->query('SELECT id, customar_name, phone, email, status, created_at FROM deliveryman ORDER BY id DESC');
$deliverymen = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Deliverymen';
$current_page = 'deliverymen';
include_once 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Deliverymen</h1>
        <p class="small text-muted mb-0">Manage delivery contacts used for in-house order assignments.</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDeliverymanModal">
        <i class="bi bi-person-plus me-1"></i>Add Deliveryman
    </button>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$deliverymen): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No deliverymen have been added.</td></tr>
                    <?php else: ?>
                        <?php foreach ($deliverymen as $deliveryman): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($deliveryman['customar_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($deliveryman['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($deliveryman['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge <?= $deliveryman['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?= htmlspecialchars(ucfirst($deliveryman['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="text-end">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="deliveryman_id" value="<?= (int)$deliveryman['id']; ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $deliveryman['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="addDeliverymanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header"><h2 class="modal-title h5">Add Deliveryman</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="deliverymanName">Full name</label>
                    <input class="form-control mb-3" id="deliverymanName" name="customar_name" maxlength="180" required>
                    <label class="form-label" for="deliverymanPhone">Phone</label>
                    <input class="form-control mb-3" id="deliverymanPhone" name="phone" maxlength="40" required>
                    <label class="form-label" for="deliverymanEmail">Email (optional)</label>
                    <input class="form-control" id="deliverymanEmail" name="email" type="email" maxlength="191">
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Save Deliveryman</button></div>
            </form>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>
