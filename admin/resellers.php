<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/reseller.php';
require_admin();
ensure_reseller_tables($pdo);

$csrf_token = $_SESSION['resellers_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['resellers_csrf'] = $csrf_token;
$notice = is_array($_SESSION['resellers_notice'] ?? null) ? $_SESSION['resellers_notice'] : null;
unset($_SESSION['resellers_notice']);
$active_status = is_string($_GET['status'] ?? null) &&
    in_array($_GET['status'], ['pending', 'approved', 'hold', 'rejected'], true)
    ? $_GET['status']
    : 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }
        $reseller_id = filter_var($_POST['reseller_id'] ?? null, FILTER_VALIDATE_INT);
        $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($reseller_id === false || $reseller_id === null || $reseller_id <= 0 ||
            !in_array($status, ['approved', 'hold', 'rejected'], true) ||
            strlen($reason) > 2000 || ($status === 'rejected' && $reason === '')) {
            throw new InvalidArgumentException('Choose a valid status; a rejection reason is required when rejecting an application.');
        }
        $stmt = $pdo->prepare("
            UPDATE reseller_accounts
            SET status = :status, rejection_reason = :reason,
                reviewed_by_admin_id = :admin_id, reviewed_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':reason' => $status === 'rejected' ? $reason : null,
            ':admin_id' => (int)$_SESSION['admin_user_id'],
            ':id' => $reseller_id,
        ]);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM reseller_accounts WHERE id = :id');
            $exists->execute([':id' => $reseller_id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Reseller application not found.');
            }
        }
        $_SESSION['resellers_notice'] = ['type' => 'success', 'message' => 'Reseller status updated to ' . $status . '.'];
    } catch (InvalidArgumentException $e) {
        $_SESSION['resellers_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        error_log('Reseller application review failed: ' . $e->getMessage());
        $_SESSION['resellers_notice'] = ['type' => 'danger', 'message' => 'Reseller status could not be updated. Please try again.'];
    }
    header('Location: resellers.php?status=' . rawurlencode($active_status));
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, phone, email, status, rejection_reason, referral_code, created_at, reviewed_at
    FROM reseller_accounts WHERE status = :status
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([':status' => $active_status]);
$resellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Resellers';
$current_page = 'resellers';
include_once __DIR__ . '/includes/header.php';
?>
<div class="px-2 pt-3 pb-2"><h1 class="h4 fw-bold mb-1">Reseller Applications</h1><p class="small text-muted mb-0">Review requests and manage reseller account access.</p></div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <ul class="nav nav-tabs mb-3">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'hold' => 'On Hold', 'rejected' => 'Rejected'] as $status => $label): ?>
            <li class="nav-item"><a class="nav-link <?= $active_status === $status ? 'active' : ''; ?>" href="resellers.php?status=<?= $status; ?>"><?= $label; ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Applicant</th><th>Contact</th><th>Requested</th><th>Rejection reason</th><th class="text-end">Action</th></tr></thead>
        <tbody>
            <?php if (!$resellers): ?><tr><td colspan="5" class="text-center text-muted py-4">No <?= htmlspecialchars($active_status, ENT_QUOTES, 'UTF-8'); ?> reseller applications.</td></tr>
            <?php else: foreach ($resellers as $reseller): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($reseller['name'], ENT_QUOTES, 'UTF-8'); ?><div class="small text-muted"><?= htmlspecialchars($reseller['referral_code'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                    <td><?= htmlspecialchars($reseller['phone'], ENT_QUOTES, 'UTF-8'); ?><div class="small text-muted"><?= htmlspecialchars($reseller['email'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                    <td><?= !empty($reseller['created_at']) ? date('d M Y, h:i A', strtotime($reseller['created_at'])) : ''; ?></td>
                    <td><?= htmlspecialchars($reseller['rejection_reason'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end"><div class="d-flex justify-content-end flex-wrap gap-2">
                        <?php if ($active_status !== 'approved'): ?>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="reseller_id" value="<?= (int)$reseller['id']; ?>"><button class="btn btn-sm btn-success" name="status" value="approved" type="submit">Approve</button></form>
                        <?php endif; ?>
                        <?php if ($active_status !== 'hold'): ?>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="reseller_id" value="<?= (int)$reseller['id']; ?>"><button class="btn btn-sm btn-outline-warning" name="status" value="hold" type="submit">Hold</button></form>
                        <?php endif; ?>
                        <?php if ($active_status !== 'rejected'): ?>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectReseller<?= (int)$reseller['id']; ?>">Reject</button>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table></div></div>
    <?php if ($active_status !== 'rejected'): foreach ($resellers as $reseller): ?>
        <div class="modal fade" id="rejectReseller<?= (int)$reseller['id']; ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
            <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="reseller_id" value="<?= (int)$reseller['id']; ?>"><input type="hidden" name="status" value="rejected">
                <div class="modal-header"><h2 class="modal-title h5">Reject reseller application</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body"><label class="form-label" for="reason<?= (int)$reseller['id']; ?>">Reason (required)</label><textarea class="form-control" id="reason<?= (int)$reseller['id']; ?>" name="rejection_reason" rows="3" maxlength="2000" required></textarea></div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Reject application</button></div>
            </form>
        </div></div></div>
    <?php endforeach; endif; ?>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
