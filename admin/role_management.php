<?php
require_once __DIR__ . '/auth/config.php';
require_admin();

$actor_role = strtoupper(trim((string)($_SESSION['user_role'] ?? '')));
if (!admin_can_manage_role_permissions($actor_role)) {
    http_response_code(403);
    exit('You do not have permission to manage role permissions.');
}

$manageable_roles = array_values(array_diff(admin_assignable_roles($actor_role), ['CEO', 'MD']));
$tab_options = admin_permission_tabs();
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
    $submitted_tabs = $_POST['tabs'] ?? [];
    $submitted_tabs = is_array($submitted_tabs) ? $submitted_tabs : [];
    $selected_tabs = [];
    $invalid_tab = false;
    foreach ($submitted_tabs as $tab) {
        if (!is_string($tab) || !array_key_exists($tab, $tab_options)) {
            $invalid_tab = true;
            break;
        }
        $selected_tabs[] = $tab;
    }
    $selected_tabs = array_values(array_unique($selected_tabs));

    if ($invalid_tab) {
        $message = 'Invalid tab selection.';
        $message_type = 'warning';
    } elseif ($action === 'create_role') {
        $role_input = is_string($_POST['role_name'] ?? null) ? $_POST['role_name'] : '';
        $role = strtoupper(trim((string)preg_replace('/\s+/', ' ', $role_input)));
        if (!preg_match('/^[A-Z0-9][A-Z0-9 ._-]{0,39}$/D', $role) ||
            in_array($role, admin_default_staff_roles(), true) ||
            in_array($role, admin_custom_staff_roles(), true)) {
            $message = 'Enter a unique role name (up to 40 letters, numbers, spaces, dots, underscores, or hyphens).';
            $message_type = 'warning';
        } else {
            $selected_tabs = array_values(array_diff($selected_tabs, ['admin-users']));
            $pdo->beginTransaction();
            try {
                $create_stmt = $pdo->prepare('
                    INSERT INTO admin_custom_roles (role, created_by_admin_id)
                    VALUES (:role, :admin_id)
                ');
                $create_stmt->execute([
                    ':role' => $role,
                    ':admin_id' => (int)($_SESSION['user_id'] ?? 0),
                ]);
                $setting_stmt = $pdo->prepare('INSERT INTO admin_role_permission_settings (role) VALUES (:role)');
                $setting_stmt->execute([':role' => $role]);
                $insert_stmt = $pdo->prepare('INSERT INTO admin_role_tab_permissions (role, tab_key) VALUES (:role, :tab_key)');
                foreach ($selected_tabs as $tab) {
                    $insert_stmt->execute([':role' => $role, ':tab_key' => $tab]);
                }
                $pdo->commit();
                $manageable_roles[] = $role;
                $message = 'Role created. Select it when creating or editing an admin staff account.';
                $message_type = 'success';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    } elseif ($action === 'save_role') {
        $role = strtoupper(trim(is_string($_POST['role'] ?? null) ? $_POST['role'] : ''));
        if (!in_array($role, $manageable_roles, true)) {
            http_response_code(403);
            exit('You do not have permission to change this role.');
        }

        if (!admin_can_manage_staff($role)) {
            $selected_tabs = array_values(array_diff($selected_tabs, ['admin-users']));
        }
        $pdo->beginTransaction();
        try {
            $delete_stmt = $pdo->prepare('DELETE FROM admin_role_tab_permissions WHERE role = :role');
            $delete_stmt->execute([':role' => $role]);
            $insert_stmt = $pdo->prepare('INSERT INTO admin_role_tab_permissions (role, tab_key) VALUES (:role, :tab_key)');
            foreach ($selected_tabs as $tab) {
                $insert_stmt->execute([':role' => $role, ':tab_key' => $tab]);
            }
            $setting_stmt = $pdo->prepare('INSERT IGNORE INTO admin_role_permission_settings (role) VALUES (:role)');
            $setting_stmt->execute([':role' => $role]);
            $pdo->commit();
            $message = 'Permissions saved for ' . $role . '.';
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    } else {
        $message = 'Unsupported action.';
        $message_type = 'warning';
    }
}

$role_permissions = [];
$role_permission_stmt = $pdo->query('SELECT role, tab_key FROM admin_role_tab_permissions');
foreach ($role_permission_stmt->fetchAll(PDO::FETCH_ASSOC) as $permission) {
    $role_permissions[(string)$permission['role']][] = (string)$permission['tab_key'];
}

$search = trim((string)($_GET['search'] ?? ''));
$role_placeholders = [];
$params = [];
foreach ($manageable_roles as $index => $role) {
    $placeholder = ':role' . $index;
    $role_placeholders[] = $placeholder;
    $params[$placeholder] = $role;
}
$staff_members = [];
if ($role_placeholders) {
    $staff_query = 'SELECT id, customar_name, email, role FROM admin_users WHERE UPPER(role) IN (' . implode(', ', $role_placeholders) . ')';
    if ($search !== '') {
        $staff_query .= ' AND (customar_name LIKE :search OR email LIKE :search OR phone LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    $staff_query .= ' ORDER BY customar_name, id';
    $staff_stmt = $pdo->prepare($staff_query);
    $staff_stmt->execute($params);
    $staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$user_custom_access = [];
if ($staff_members) {
    $staff_ids = array_map(static function (array $staff): int {
        return (int)$staff['id'];
    }, $staff_members);
    $id_placeholders = implode(', ', array_fill(0, count($staff_ids), '?'));
    $user_setting_stmt = $pdo->prepare('SELECT admin_user_id FROM admin_user_permission_settings WHERE custom_access = 1 AND admin_user_id IN (' . $id_placeholders . ')');
    $user_setting_stmt->execute($staff_ids);
    foreach ($user_setting_stmt->fetchAll(PDO::FETCH_COLUMN) as $user_id) {
        $user_custom_access[(int)$user_id] = true;
    }
}

$page_title = 'Role Management';
$current_page = 'role-management';
include __DIR__ . '/includes/header.php';
?>

<div class="flex-grow-1 overflow-y-auto pb-3" style="min-height: 0;">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Role Management</h1>
        <p class="small text-muted mb-0">Choose default admin tabs for each role, then optionally customize access for individual staff.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body fw-semibold">Add a role</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="create_role">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" for="new-role-name">Role name</label>
                    <input class="form-control" id="new-role-name" name="role_name" maxlength="40" required placeholder="e.g. INVENTORY MANAGER">
                    <div class="form-text">Custom roles can be assigned by CEO and MD. Existing default roles remain unchanged.</div>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-success" type="submit"><i class="bi bi-plus-lg me-1"></i>Create role</button>
                </div>
            </div>
            <div class="fw-semibold small mb-2">Initial tab access</div>
            <div class="row g-2">
                <?php foreach ($tab_options as $tab_key => $tab_label): ?>
                    <?php $staff_tab_available = $tab_key !== 'admin-users'; ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="tabs[]" value="<?= htmlspecialchars($tab_key, ENT_QUOTES, 'UTF-8'); ?>" <?= $staff_tab_available ? 'checked' : ''; ?> <?= $staff_tab_available ? '' : 'disabled'; ?>>
                            <span class="form-check-label"><?= htmlspecialchars($tab_label, ENT_QUOTES, 'UTF-8'); ?><?= $staff_tab_available ? '' : ' (restricted)'; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-body fw-semibold">Default permissions by role</div>
    <div class="card-body">
        <p class="small text-muted">Role permissions apply automatically to staff unless that account has custom access. For custom-access accounts, open their details and clear the override to inherit this role. CEO and MD retain full access and are managed separately.</p>
        <?php foreach ($manageable_roles as $role): ?>
            <?php $granted_tabs = $role_permissions[$role] ?? []; ?>
            <form method="post" class="border rounded p-3 mb-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_role">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h6 fw-bold mb-0"><?= htmlspecialchars(ucwords(strtolower($role)), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <button class="btn btn-sm btn-primary" type="submit">Save role tabs</button>
                </div>
                <div class="row g-2">
                    <?php foreach ($tab_options as $tab_key => $tab_label): ?>
                        <?php $staff_tab_available = $tab_key !== 'admin-users' || admin_can_manage_staff($role); ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="tabs[]" value="<?= htmlspecialchars($tab_key, ENT_QUOTES, 'UTF-8'); ?>" <?= $staff_tab_available && in_array($tab_key, $granted_tabs, true) ? 'checked' : ''; ?> <?= $staff_tab_available ? '' : 'disabled'; ?>>
                                <span class="form-check-label"><?= htmlspecialchars($tab_label, ENT_QUOTES, 'UTF-8'); ?><?= $staff_tab_available ? '' : ' (restricted)'; ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-body fw-semibold">Custom access by staff account</div>
    <div class="card-body">
        <p class="small text-muted">Select a staff member to inspect current permissions and edit custom tab access.</p>
        <form method="get" action="role_management.php" class="row g-2 mb-3">
            <div class="col-md-9">
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search staff by name, email, or phone">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Search</button>
                <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="role_management.php">Reset</a><?php endif; ?>
            </div>
        </form>

        <?php if ($staff_members): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Access source</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_members as $staff): ?>
                            <?php $staff_id = (int)$staff['id']; ?>
                            <tr>
                                <td><a class="fw-semibold text-decoration-none" href="admin_staff_permissions.php?id=<?= $staff_id; ?>"><?= htmlspecialchars((string)$staff['customar_name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?= htmlspecialchars((string)$staff['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge text-bg-primary"><?= htmlspecialchars(strtoupper((string)$staff['role']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?= isset($user_custom_access[$staff_id]) ? 'Custom user access' : 'Inherits role'; ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="admin_staff_permissions.php?id=<?= $staff_id; ?>">View / Edit permissions</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No staff accounts match this search.</p>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
