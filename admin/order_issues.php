<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/order_admin.php';
require_once __DIR__ . '/../core/order_status.php';
require_admin();

ensure_order_issues_table($pdo);
$status_csrf_token = $_SESSION['order_status_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['order_status_csrf'] = $status_csrf_token;
$filter = $_GET['status'] ?? 'pending';
$filter = is_string($filter) && in_array($filter, ['pending', 'resolved', 'rejected', 'all'], true) ? $filter : 'pending';
$query = "
    SELECT i.*, o.order_number, o.fullname
    FROM order_issues i
    JOIN orders o ON o.id = i.order_id
";
$params = [];
if ($filter !== 'all') {
    $query .= ' WHERE i.status = :status';
    $params[':status'] = $filter;
}
$query .= ' ORDER BY (i.status = "pending") DESC, i.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Order Issues';
$current_page = 'order-issues';
include_once 'includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-2 py-3">
    <h1 class="h4 fw-bold mb-0">Delivered Order Issues</h1>
    <form method="get" class="d-flex gap-2">
        <select class="form-select form-select-sm" name="status" aria-label="Filter issues">
            <?php foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label): ?>
                <option value="<?= $value; ?>" <?= $filter === $value ? 'selected' : ''; ?>><?= $label; ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-success">Filter</button>
    </form>
</div>
<div id="issueAlert" class="px-2" aria-live="polite"></div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if (!$issues): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">No issues found.</div></div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($issues as $issue): ?>
                <article class="card shadow-sm">
                    <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong><?= htmlspecialchars(match ($issue['issue_type']) { 'return' => 'Return issue', 'missing' => 'Missing item', 'replacement' => 'Replacement', default => ucfirst($issue['issue_type']) }, ENT_QUOTES, 'UTF-8'); ?></strong>
                            · Shipment #<?= htmlspecialchars($issue['shipment_number'] ?: order_shipment_number((string)$issue['order_number'], 'B'), ENT_QUOTES, 'UTF-8'); ?>
                            · Order <a href="order_details.php?id=<?= (int)$issue['order_id']; ?>">#<?= htmlspecialchars(order_shipment_number((string)$issue['order_number']), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                        <span class="badge <?= $issue['status'] === 'pending' ? 'text-bg-warning' : ($issue['status'] === 'resolved' ? 'text-bg-success' : 'text-bg-secondary'); ?>"><?= htmlspecialchars(ucfirst($issue['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            Customer: <?= htmlspecialchars($issue['fullname'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?>
                            · <?= !empty($issue['created_at']) ? date('d M Y, h:i A', strtotime($issue['created_at'])) : ''; ?>
                        </div>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($issue['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php if (!empty($issue['admin_note'])): ?><div class="alert alert-light border py-2 mb-0">Admin note: <?= nl2br(htmlspecialchars($issue['admin_note'], ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
                    </div>
                    <?php if ($issue['status'] === 'pending'): ?>
                        <div class="card-footer bg-body">
                            <form class="js-resolve-issue-form d-flex flex-wrap gap-2 align-items-center">
                                <input type="hidden" name="issue_id" value="<?= (int)$issue['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($status_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input name="admin_note" class="form-control form-control-sm flex-grow-1" maxlength="3000" placeholder="Optional note to customer">
                                <button class="btn btn-sm btn-success" name="decision" value="resolve" type="submit">Resolve</button>
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
document.querySelectorAll('.js-resolve-issue-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const submitter = event.submitter;
        if (!submitter) return;
        const data = new FormData(form);
        data.set('decision', submitter.value);
        try {
            const response = await fetch('core/resolve_order_issue.php', { method: 'POST', body: data });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Issue could not be updated.');
            }
            window.location.reload();
        } catch (error) {
            const alertContainer = document.getElementById('issueAlert');
            alertContainer.replaceChildren();
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.textContent = error.message || 'Issue could not be updated.';
            alertContainer.append(alert);
        }
    });
});
</script>
<?php include_once 'includes/footer.php'; ?>
