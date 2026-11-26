<?php
require_once __DIR__ . '/auth/config.php';
require_admin();

$actor_role = strtoupper(trim((string)($_SESSION['user_role'] ?? '')));
if (!admin_can_manage_role_permissions($actor_role)) {
    http_response_code(403);
    exit('You do not have permission to manage staff permissions.');
}

$manageable_roles = array_values(array_diff(admin_assignable_roles($actor_role), ['CEO', 'MD']));
$staff_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($staff_id === false || $staff_id === null || $staff_id <= 0) {
    http_response_code(400);
    exit('Invalid staff account.');
}

$staff_stmt = $pdo->prepare('SELECT id, customar_name, email, phone, role FROM admin_users WHERE id = :id LIMIT 1');
$staff_stmt->execute([':id' => $staff_id]);
$staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
$staff_role = strtoupper(trim((string)($staff['role'] ?? '')));
if (!$staff || !in_array($staff_role, $manageable_roles, true)) {
    http_response_code(404);
    exit('Admin staff account not found.');
}

$tab_options = admin_permission_tabs();
$csrf = csrf_token();
$message = is_string($_SESSION['staff_permissions_message'] ?? null) ? $_SESSION['staff_permissions_message'] : '';
unset($_SESSION['staff_permissions_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted_csrf) || !hash_equals($csrf, $submitted_csrf)) {
        http_response_code(400);
        exit('Invalid request token.');
    }

    $submitted_tabs = $_POST['tabs'] ?? [];
    $submitted_tabs = is_array($submitted_tabs) ? $submitted_tabs : [];
    $selected_tabs = [];
    foreach ($submitted_tabs as $tab) {
        if (!is_string($tab) || !array_key_exists($tab, $tab_options)) {
            http_response_code(400);
            exit('Invalid tab selection.');
        }
        $selected_tabs[] = $tab;
    }
    $selected_tabs = array_values(array_unique($selected_tabs));
    if (!admin_can_manage_staff($staff_role)) {
        $selected_tabs = array_values(array_diff($selected_tabs, ['admin-users']));
    }
    $custom_access = ($_POST['custom_access'] ?? '') === '1';

    $pdo->beginTransaction();
    try {
        $delete_stmt = $pdo->prepare('DELETE FROM admin_user_tab_permissions WHERE admin_user_id = :id');
        $delete_stmt->execute([':id' => $staff_id]);
        if ($custom_access) {
            $setting_stmt = $pdo->prepare('
                INSERT INTO admin_user_permission_settings (admin_user_id, custom_access)
                VALUES (:id, 1)
                ON DUPLICATE KEY UPDATE custom_access = 1
            ');
            $setting_stmt->execute([':id' => $staff_id]);
            $insert_stmt = $pdo->prepare('INSERT INTO admin_user_tab_permissions (admin_user_id, tab_key) VALUES (:id, :tab_key)');
            foreach ($selected_tabs as $tab) {
                $insert_stmt->execute([':id' => $staff_id, ':tab_key' => $tab]);
            }
        } else {
            $setting_stmt = $pdo->prepare('DELETE FROM admin_user_permission_settings WHERE admin_user_id = :id');
            $setting_stmt->execute([':id' => $staff_id]);
        }
        $pdo->commit();
        $_SESSION['staff_permissions_message'] = $custom_access
            ? 'Custom permissions saved.'
            : 'Custom permissions cleared; this account now inherits its role permissions.';
        header('Location: admin_staff_permissions.php?id=' . $staff_id);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$custom_stmt = $pdo->prepare('SELECT custom_access FROM admin_user_permission_settings WHERE admin_user_id = :id LIMIT 1');
$custom_stmt->execute([':id' => $staff_id]);
$custom_access = (int)$custom_stmt->fetchColumn() === 1;
$effective_tabs = admin_effective_tab_keys($pdo, $staff_id, $staff_role);
$effective_tab_set = array_fill_keys($effective_tabs, true);
$form_tabs = $effective_tabs;
if ($custom_access) {
    $form_stmt = $pdo->prepare('SELECT tab_key FROM admin_user_tab_permissions WHERE admin_user_id = :id');
    $form_stmt->execute([':id' => $staff_id]);
    $form_tabs = $form_stmt->fetchAll(PDO::FETCH_COLUMN);
}

$page_title = 'Staff Permissions';
$current_page = 'role-management';
include __DIR__ . '/includes/header.php';
?>

<div class="flex-grow-1 overflow-y-auto pb-3" style="min-height: 0;">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Staff Permissions</h1>
        <p class="small text-muted mb-0">Review effective access and set tab permissions for this account.</p>
    </div>
    <a class="btn btn-outline-secondary" href="role_management.php"><i class="bi bi-arrow-left me-1"></i>Back to Role Management</a>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars((string)$staff['customar_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="text-muted"><?= htmlspecialchars((string)$staff['email'], ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars((string)$staff['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="mt-2">Role: <span class="badge text-bg-primary"><?= htmlspecialchars($staff_role, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="mt-2">Access source: <strong><?= $custom_access ? 'Custom user permissions' : 'Role permissions'; ?></strong></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body fw-semibold">Effective permissions</div>
    <div class="card-body">
        <p class="small text-muted">These are the tabs this user can currently access. My Profile is always available; Role Management is reserved for CEO and MD.</p>
        <div class="row g-2">
            <?php foreach ($tab_options as $tab_key => $tab_label): ?>
                <?php $has_access = isset($effective_tab_set[$tab_key]); ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <span class="badge <?= $has_access ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                        <i class="bi <?= $has_access ? 'bi-check-lg' : 'bi-dash-lg'; ?> me-1"></i><?= htmlspecialchars($tab_label, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <div class="col-sm-6 col-lg-4 col-xl-3"><span class="badge text-bg-success"><i class="bi bi-check-lg me-1"></i>My Profile</span></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body fw-semibold">Edit user access</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="custom-access" name="custom_access" value="1" <?= $custom_access ? 'checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="custom-access">Use custom permissions for this user</label>
                <div class="form-text">When off, the user automatically gets the permissions assigned to the <?= htmlspecialchars($staff_role, ENT_QUOTES, 'UTF-8'); ?> role.</div>
            </div>
            <div class="row g-2" id="permission-options">
                <?php foreach ($tab_options as $tab_key => $tab_label): ?>
                    <?php $staff_tab_available = $tab_key !== 'admin-users' || admin_can_manage_staff($staff_role); ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="tabs[]" value="<?= htmlspecialchars($tab_key, ENT_QUOTES, 'UTF-8'); ?>" <?= $staff_tab_available && in_array($tab_key, $form_tabs, true) ? 'checked' : ''; ?> <?= $staff_tab_available ? '' : 'disabled'; ?>>
                            <span class="form-check-label"><?= htmlspecialchars($tab_label, ENT_QUOTES, 'UTF-8'); ?><?= $staff_tab_available ? '' : ' (restricted)'; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="role_management.php">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save permissions</button>
            </div>
        </form>
    </div>
</div>

<script>
const customAccessToggle = document.getElementById('custom-access');
const permissionOptions = document.getElementById('permission-options');
function updatePermissionInputs() {
    permissionOptions.querySelectorAll('input[type="checkbox"]').forEach(input => {
        input.disabled = !customAccessToggle.checked;
    });
}
customAccessToggle.addEventListener('change', updatePermissionInputs);
updatePermissionInputs();
</script>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
