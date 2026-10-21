<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/user_addresses.php';
require_once __DIR__ . '/../core/user_profile_picture.php';
require_admin();

$page_title = "Manage Users";
$current_page = "users";

$message = '';
$message_type = '';
$csrf_token = $_SESSION['users_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['users_csrf_token'] = $csrf_token;

// Handling Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD USER / ADMIN
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $name          = trim($_POST['customar_name'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $role          = trim($_POST['role'] ?? 'user');
        $verify_status = (int)($_POST['verify_status'] ?? 1);
        $password      = trim($_POST['password'] ?? '');

        if (!empty($name) && !empty($email) && !empty($password)) {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $checkStmt->execute([':email' => $email]);

            if ($checkStmt->rowCount() > 0) {
                $message = "Email address already exists!";
                $message_type = "danger";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $created_at      = date('Y-m-d H:i:s');

                $insertStmt = $pdo->prepare("INSERT INTO users (customar_name, email, phone, password, role, verify_status, created_at, reset_count, pwd_up_count) VALUES (:name, :email, :phone, :password, :role, :verify_status, :created_at, 0, 0)");
                
                $result = $insertStmt->execute([
                    ':name'          => $name,
                    ':email'         => $email,
                    ':phone'         => $phone,
                    ':password'      => $hashed_password,
                    ':role'          => $role,
                    ':verify_status' => $verify_status,
                    ':created_at'    => $created_at
                ]);

                if ($result) {
                    $message = "User created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to create user.";
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Please fill in all required fields.";
            $message_type = "warning";
        }
    }

    // 2. CHANGE ROLE & VERIFY STATUS
    if (isset($_POST['action']) && $_POST['action'] === 'update_user_access') {
        $user_id       = (int)($_POST['user_id'] ?? 0);
        $new_role      = trim($_POST['role'] ?? '');
        $verify_status = (int)($_POST['verify_status'] ?? 0);

        if ($user_id > 0 && !empty($new_role)) {
            $updateStmt = $pdo->prepare("UPDATE users SET role = :role, verify_status = :verify_status WHERE id = :id");
            if ($updateStmt->execute([':role' => $new_role, ':verify_status' => $verify_status, ':id' => $user_id])) {
                $message = "User role and status updated successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to update user access.";
                $message_type = "danger";
            }
        }
    }

    // 3. DELETE CUSTOMER ACCOUNT
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $submitted_csrf = $_POST['csrf_token'] ?? '';
        if ($user_id === false || $user_id === null || $user_id <= 0 ||
            !is_string($submitted_csrf) || !hash_equals($csrf_token, $submitted_csrf)) {
            $message = 'Invalid delete request. Refresh the page and try again.';
            $message_type = 'danger';
        } elseif ($user_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot delete your own account while signed in.';
            $message_type = 'warning';
        } else {
            try {
                ensure_user_addresses_table($pdo);
                ensure_user_profile_pictures_table($pdo);
                $pdo->beginTransaction();
                $target_stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id FOR UPDATE');
                $target_stmt->execute([':id' => $user_id]);
                $target_user = $target_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$target_user) {
                    $pdo->rollBack();
                    $message = 'User not found.';
                    $message_type = 'warning';
                } elseif (in_array(strtolower((string)$target_user['role']), ['admin', 'manager'], true)) {
                    $pdo->rollBack();
                    $message = 'Admin and manager accounts cannot be deleted here.';
                    $message_type = 'warning';
                } else {
                    $address_stmt = $pdo->prepare('DELETE FROM user_addresses WHERE user_id = :user_id');
                    $address_stmt->execute([':user_id' => $user_id]);
                    $picture_stmt = $pdo->prepare('SELECT image_path FROM user_profile_pictures WHERE user_id = :user_id');
                    $picture_stmt->execute([':user_id' => $user_id]);
                    $removed_picture = $picture_stmt->fetchColumn();
                    $pdo->prepare('DELETE FROM user_profile_pictures WHERE user_id = :user_id')
                        ->execute([':user_id' => $user_id]);

                    $delete_stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                    $delete_stmt->execute([':id' => $user_id]);
                    if ($delete_stmt->rowCount() !== 1) {
                        throw new RuntimeException('The user account could not be deleted.');
                    }
                    $pdo->commit();
                    if (is_string($removed_picture)) {
                        remove_managed_user_profile_picture_file($removed_picture);
                    }
                    $message = 'User account deleted. Order history and request records were retained.';
                    $message_type = 'success';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Admin user deletion failed: ' . $e->getMessage());
                $message = 'User account could not be deleted. Please try again.';
                $message_type = 'danger';
            }
        }
    }
}

// Search and Filter Setup
$search = trim($_GET['search'] ?? '');
$query  = "SELECT id, customar_name, email, phone, role, verify_status, created_at FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (customar_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center my-3 px-2 flex-shrink-0 flex-wrap gap-2">
    <div>
        <h2 class="h3 mb-0">User Management</h2>
        <p class="text-muted small mb-0">Add users, admins, manage roles, and status verification.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New User / Admin
    </button>
</div>

<!-- Alert Display -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?= $message_type; ?> alert-dismissible fade show mx-2" role="alert">
    <?= htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Search Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="users.php" class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
                <?php if (!empty($search)): ?>
                <a href="users.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
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
                        <th class="bg-success text-white">Joined Date</th>
                        <th class="bg-success text-white text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-bold">#<?= $user['id']; ?></td>
                        <td><?= htmlspecialchars($user['customar_name'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($user['email'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $role = strtolower($user['role'] ?? 'user');
                            $badgeClass = match($role) {
                                'admin'   => 'bg-danger',
                                'manager' => 'bg-warning text-dark',
                                default   => 'bg-primary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass; ?> text-capitalize">
                                <?= htmlspecialchars($user['role'] ?? 'user'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$user['verify_status'] === 1): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i> Unverified</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['id']; ?>">
                                <i class="bi bi-pencil-square"></i> Edit Role & Status
                            </button>
                            <?php if (!in_array($role, ['admin', 'manager'], true) && (int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" action="users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>" class="d-inline" onsubmit="return confirm('Delete this user account? Saved addresses will be removed, but order history and request records will remain.');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Edit Role & Status Modal -->
                    <div class="modal fade" id="editUserModal<?= $user['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                                <form method="POST" action="users.php">
                                    <input type="hidden" name="action" value="update_user_access">
                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Manage Access - <?= htmlspecialchars($user['customar_name'] ?? ''); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Select Role</label>
                                            <select name="role" class="form-select" required>
                                                <option value="user" <?= $role === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="manager" <?= $role === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Verify Status</label>
                                            <select name="verify_status" class="form-select" required>
                                                <option value="1" <?= (int)$user['verify_status'] === 1 ? 'selected' : ''; ?>>Verified (Active)</option>
                                                <option value="0" <?= (int)$user['verify_status'] === 0 ? 'selected' : ''; ?>>Unverified (Inactive)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User / Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="customar_name" class="form-control" placeholder="Enter name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="user" selected>User</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Verify Status <span class="text-danger">*</span></label>
                        <select name="verify_status" class="form-select" required>
                            <option value="1" selected>Verified (Active)</option>
                            <option value="0">Unverified (Inactive)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>