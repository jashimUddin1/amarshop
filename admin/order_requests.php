<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_admin.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

ensure_order_requests_table($pdo);
$status_csrf_token = $_SESSION['order_status_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['order_status_csrf'] = $status_csrf_token;
$filter = $_GET['status'] ?? 'pending';
$filter = is_string($filter) && in_array($filter, ['pending', 'approved', 'rejected', 'all'], true) ? $filter : 'pending';
$query = "
    SELECT r.*, o.order_number, o.order_status, o.fullname, u.customar_name AS account_name
    FROM order_requests r
    JOIN orders o ON o.id = r.order_id
    LEFT JOIN users u ON u.id = r.user_id
";
$params = [];
if ($filter !== 'all') {
    $query .= ' WHERE r.status = :status';
    $params[':status'] = $filter;
}
$query .= ' ORDER BY (r.status = "pending") DESC, r.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Order Requests';
$current_page = 'order-requests';
include_once 'includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-2 py-3">
    <h1 class="h4 fw-bold mb-0">Customer Order Requests</h1>
    <form method="get" class="d-flex gap-2">
        <select class="form-select form-select-sm" name="status" aria-label="Filter requests">
            <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label): ?>
                <option value="<?= $value; ?>" <?= $filter === $value ? 'selected' : ''; ?>><?= $label; ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-success">Filter</button>
    </form>
</div>
<div id="requestAlert" class="px-2" aria-live="polite"></div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if (!$requests): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">No requests found.</div></div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($requests as $request): ?>
                <?php
                $type_label = match ($request['request_type']) {
                    'add_item' => 'Add item request',
                    'update' => 'Order update request',
                    'cancel' => 'Cancellation request',
                    default => ucfirst($request['request_type']),
                };
                ?>
                <article class="card shadow-sm">
                    <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div><strong><?= htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'); ?></strong>
                            · Order <a href="order_details.php?id=<?= (int)$request['order_id']; ?>">#<?= htmlspecialchars($request['order_number'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                        <span class="badge <?= $request['status'] === 'pending' ? 'text-bg-warning' : ($request['status'] === 'approved' ? 'text-bg-success' : 'text-bg-secondary'); ?>"><?= htmlspecialchars(ucfirst($request['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            Customer: <?= htmlspecialchars($request['fullname'] ?? $request['account_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?>
                            · Current order status: <?= htmlspecialchars(order_status_label((string)$request['order_status']), ENT_QUOTES, 'UTF-8'); ?>
                            · <?= !empty($request['created_at']) ? date('d M Y, h:i A', strtotime($request['created_at'])) : ''; ?>
                        </div>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($request['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php if ($request['request_type'] === 'update' && $request['status'] === 'pending'): ?>
                            <div class="small text-primary mb-2">Review the requested change in the order details, apply it if appropriate, then approve this request.</div>
                        <?php endif; ?>
                        <?php if ($request['admin_note']): ?><div class="alert alert-light border py-2 mb-0">Admin note: <?= nl2br(htmlspecialchars($request['admin_note'], ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
                    </div>
                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="card-footer bg-body">
                            <form class="js-resolve-request d-flex flex-wrap gap-2 align-items-center">
                                <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input name="admin_note" class="form-control form-control-sm flex-grow-1" placeholder="Optional note to customer">
                                <button class="btn btn-sm btn-success" name="decision" value="approve" type="submit">Approve</button>
                                <button class="btn btn-sm btn-outline-danger" name="decision" value="reject" type="submit">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.js-resolve-request').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const submitter = event.submitter;
        if (!submitter) return;
        const data = new FormData(form);
        data.set('decision', submitter.value);
        try {
            const response = await fetch('core/resolve_order_request.php', { method: 'POST', body: data });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Request could not be resolved.');
            window.location.reload();
        } catch (error) {
            const alertContainer = document.getElementById('requestAlert');
            alertContainer.replaceChildren();
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.setAttribute('role', 'alert');
            alert.append(document.createTextNode(error.message));
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            closeButton.setAttribute('aria-label', 'Close');
            alert.append(closeButton);
            alertContainer.append(alert);
        }
    });
});
</script>
<?php include_once 'includes/footer.php'; ?>
