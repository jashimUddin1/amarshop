<?php
require_once __DIR__ . '/auth/config.php';
require_admin();

$actor_role = strtoupper(trim((string)($_SESSION['user_role'] ?? '')));
if (!admin_can_manage_staff($actor_role)) {
    http_response_code(403);
    exit('You do not have permission to manage admin staff.');
}

$manageable_roles = admin_assignable_roles($actor_role);
$csrf = csrf_token();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted_csrf) || !hash_equals($csrf, $submitted_csrf)) {
        http_response_code(400);
        exit('Invalid request token.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $staff_id = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);

    if ($staff_id === false || $staff_id === null || $staff_id <= 0 || $staff_id === (int)($_SESSION['user_id'] ?? 0)) {
        $message = 'Invalid staff account.';
        $message_type = 'warning';
    } else {
        $target_stmt = $pdo->prepare('SELECT id, role FROM admin_users WHERE id = :id LIMIT 1');
        $target_stmt->execute([':id' => $staff_id]);
        $target = $target_stmt->fetch(PDO::FETCH_ASSOC);
        $target_role = strtoupper(trim((string)($target['role'] ?? '')));

        if (!$target || !in_array($target_role, $manageable_roles, true)) {
            http_response_code(403);
            exit('You do not have permission to manage this staff account.');
        }

        if ($action === 'update_staff') {
            $name = trim((string)($_POST['customar_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $role = strtoupper(trim((string)($_POST['role'] ?? '')));
            $verify_status = filter_var($_POST['verify_status'] ?? null, FILTER_VALIDATE_INT);

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' ||
                !in_array($role, $manageable_roles, true) || !in_array($verify_status, [0, 1], true)) {
                $message = 'Enter valid staff details and choose a role you are allowed to assign.';
                $message_type = 'warning';
            } else {
                $duplicate_stmt = $pdo->prepare('
                    SELECT id FROM admin_users
                    WHERE (LOWER(email) = LOWER(:email) OR phone = :phone) AND id <> :id
                    LIMIT 1
                ');
                $duplicate_stmt->execute([':email' => $email, ':phone' => $phone, ':id' => $staff_id]);
                if ($duplicate_stmt->fetchColumn()) {
                    $message = 'That email address or phone number is already assigned to another account.';
                    $message_type = 'danger';
                } else {
                    $update_stmt = $pdo->prepare('
                        UPDATE admin_users
                        SET customar_name = :name, email = :email, phone = :phone,
                            role = :role, verify_status = :verify_status
                        WHERE id = :id
                    ');
                    $update_stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':role' => $role,
                        ':verify_status' => $verify_status,
                        ':id' => $staff_id,
                    ]);
                    $message = 'Admin staff account updated successfully.';
                    $message_type = 'success';
                }
            }
        } elseif ($action === 'delete_staff') {
            $delete_stmt = $pdo->prepare('DELETE FROM admin_users WHERE id = :id AND role = :role');
            $delete_stmt->execute([':id' => $staff_id, ':role' => $target['role']]);
            if ($delete_stmt->rowCount() === 1) {
                $message = 'Admin staff account deleted.';
                $message_type = 'success';
            } else {
                $message = 'Admin staff account could not be deleted.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Unsupported action.';
            $message_type = 'warning';
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$visible_roles = array_values(array_unique(array_merge($manageable_roles, [$actor_role])));
$role_placeholders = [];
$params = [];
foreach ($visible_roles as $index => $role) {
    $placeholder = ':role' . $index;
    $role_placeholders[] = $placeholder;
    $params[$placeholder] = $role;
}

$query = 'SELECT id, customar_name, email, phone, role, verify_status, created_at
          FROM admin_users WHERE UPPER(role) IN (' . implode(', ', $role_placeholders) . ')';
if ($search !== '') {
    $query .= ' AND (customar_name LIKE :search OR email LIKE :search OR phone LIKE :search OR role LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
$query .= ' ORDER BY id DESC';
$list_stmt = $pdo->prepare($query);
$list_stmt->execute($params);
$staff_members = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Manage Admin Staff';
$current_page = 'admin-users';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Manage Admin Staff</h1>
        <small class="text-muted">Staff accounts available to your role: <?= count($staff_members); ?></small>
    </div>
    <a class="btn btn-success" href="auth/signup.php"><i class="bi bi-person-plus me-1"></i>Add Admin Staff</a>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="admin_users.php" class="row g-2">
            <div class="col-md-9">
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search name, email, phone, or role">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Search</button>
                <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="admin_users.php">Reset</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
    <div class="card-body p-0 h-100">
        <div class="table-responsive h-100 overflow-y-auto">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success position-sticky top-0 z-2">
                    <tr>
                        <th class="bg-success text-white">ID</th>
                        <th class="bg-success text-white">Name</th>
                        <th class="bg-success text-white">Email</th>
                        <th class="bg-success text-white">Phone</th>
                        <th class="bg-success text-white">Role</th>
                        <th class="bg-success text-white">Status</th>
                        <th class="bg-success text-white">Created</th>
                        <th class="bg-success text-white text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staff_members): ?>
                        <?php foreach ($staff_members as $staff): ?>
                            <?php
                            $staff_id = (int)$staff['id'];
                            $staff_role = strtoupper(trim((string)$staff['role']));
                            $can_edit = $staff_id !== (int)($_SESSION['user_id'] ?? 0) && in_array($staff_role, $manageable_roles, true);
                            ?>
                            <tr>
                                <td class="fw-semibold">#<?= $staff_id; ?></td>
                                <td>
                                    <?php if ($can_edit && admin_can_manage_role_permissions($actor_role)): ?>
                                        <a class="fw-semibold text-decoration-none" href="admin_staff_permissions.php?id=<?= $staff_id; ?>"><?= htmlspecialchars($staff['customar_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($staff['customar_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($staff['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($staff['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($staff_role, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <?php if ((int)$staff['verify_status'] === 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($staff['created_at']) ? htmlspecialchars(date('d M Y', strtotime($staff['created_at'])), ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
                                <td class="text-end text-nowrap">
                                    <?php if ($can_edit): ?>
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editStaffModal"
                                            data-staff-id="<?= $staff_id; ?>"
                                            data-name="<?= htmlspecialchars($staff['customar_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?= htmlspecialchars($staff['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-phone="<?= htmlspecialchars($staff['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-role="<?= htmlspecialchars($staff_role, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-status="<?= (int)$staff['verify_status']; ?>"
                                        ><i class="bi bi-pencil-square"></i> Edit</button>
                                        <form method="post" action="admin_users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>" class="d-inline" onsubmit="return confirm('Delete this admin staff account?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_staff">
                                            <input type="hidden" name="staff_id" value="<?= $staff_id; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Current account</span>
                                    <?php endif; ?>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More options">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" type="button"
                                                    onclick="openHistoryModal('staff', <?= $staff_id; ?>, <?= htmlspecialchars(json_encode($staff['customar_name']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="bi bi-clock-history me-2 text-secondary"></i>History
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No admin staff accounts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="admin_users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_staff">
                <input type="hidden" name="staff_id" id="edit-staff-id">
                <div class="modal-header">
                    <h2 class="modal-title fs-5">Edit Admin Staff</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="edit-staff-name">Full name</label>
                    <input class="form-control mb-3" id="edit-staff-name" name="customar_name" maxlength="180" required>
                    <label class="form-label" for="edit-staff-email">Email</label>
                    <input class="form-control mb-3" type="email" id="edit-staff-email" name="email" maxlength="191" required>
                    <label class="form-label" for="edit-staff-phone">Phone</label>
                    <input class="form-control mb-3" id="edit-staff-phone" name="phone" maxlength="40" required>
                    <label class="form-label" for="edit-staff-role">Role</label>
                    <select class="form-select mb-3" id="edit-staff-role" name="role" required>
                        <?php foreach ($manageable_roles as $role): ?>
                            <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(ucwords(strtolower($role)), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label" for="edit-staff-status">Account status</label>
                    <select class="form-select" id="edit-staff-status" name="verify_status" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('editStaffModal')?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!(button instanceof HTMLElement)) return;
    document.getElementById('edit-staff-id').value = button.dataset.staffId || '';
    document.getElementById('edit-staff-name').value = button.dataset.name || '';
    document.getElementById('edit-staff-email').value = button.dataset.email || '';
    document.getElementById('edit-staff-phone').value = button.dataset.phone || '';
    document.getElementById('edit-staff-role').value = button.dataset.role || '';
    document.getElementById('edit-staff-status').value = button.dataset.status || '0';
});
</script>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Activity History — <span id="historyModalName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyModalBody">
                <div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
function openHistoryModal(type, id, name) {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    document.getElementById('historyModalName').textContent = name;
    document.getElementById('historyModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>';
    modal.show();
    fetch('core/get_history.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (data.error) { document.getElementById('historyModalBody').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>'; return; }
            const rows = data.rows || [];
            if (!rows.length) { document.getElementById('historyModalBody').innerHTML = '<p class="text-muted text-center py-3">No activity history found.</p>'; return; }
            const colorMap = {success:'text-success',warning:'text-warning',danger:'text-danger',primary:'text-primary',info:'text-info',secondary:'text-secondary'};
            let html = '<div class="timeline">';
            rows.forEach(row => {
                const c = colorMap[row.color] || 'text-secondary';
                const t = row.time ? new Date(row.time).toLocaleString('en-BD', {dateStyle:'medium',timeStyle:'short'}) : '—';
                html += `<div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                    <div class="flex-shrink-0 mt-1"><i class="bi bi-${row.icon} fs-5 ${c}"></i></div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong class="${c}">${row.action}</strong>
                            <small class="text-muted ms-2 text-nowrap">${t}</small>
                        </div>
                        <div class="small text-muted">${row.detail}</div>
                        ${row.by && row.by !== '—' ? `<div class="small"><i class="bi bi-person me-1"></i>${row.by}</div>` : ''}
                    </div>
                </div>`;
            });
            html += '</div>';
            document.getElementById('historyModalBody').innerHTML = html;
        })
        .catch(() => { document.getElementById('historyModalBody').innerHTML = '<div class="alert alert-danger">Failed to load history.</div>'; });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
