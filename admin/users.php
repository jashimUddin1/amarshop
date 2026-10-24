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

        if (!empty($name) && !empty($password)) {
            // Check if email already exists (if provided)
            $checkEmail = false;
            if (!empty($email)) {
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $checkStmt->execute([':email' => $email]);
                $checkEmail = ($checkStmt->rowCount() > 0);
            }

            // Check if phone already exists
            $checkPhone = false;
            if (!empty($phone)) {
                $checkPhoneStmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone");
                $checkPhoneStmt->execute([':phone' => $phone]);
                $checkPhone = ($checkPhoneStmt->rowCount() > 0);
            }

            if ($checkEmail) {
                $message = "Email address already exists!";
                $message_type = "danger";
            } elseif ($checkPhone) {
                $message = "Phone number already exists!";
                $message_type = "danger";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $created_at      = date('Y-m-d H:i:s');

                $insertStmt = $pdo->prepare("INSERT INTO users (customar_name, email, phone, password, role, verify_status, phone_verified, email_verified, created_at, reset_count, pwd_up_count) VALUES (:name, :email, :phone, :password, :role, :verify_status, 0, 0, :created_at, 0, 0)");
                
                $result = $insertStmt->execute([
                    ':name'          => $name,
                    ':email'         => $email !== '' ? $email : null,
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

    // 3. SET / UPDATE PHONE OTP & PHONE VERIFICATION
    if (isset($_POST['action']) && $_POST['action'] === 'set_phone_otp') {
        $user_id        = (int)($_POST['user_id'] ?? 0);
        $phone_otp      = trim($_POST['phone_otp'] ?? '');
        $phone_verified = isset($_POST['phone_verified']) ? 1 : 0;
        $submitted_csrf = $_POST['csrf_token'] ?? '';

        if ($user_id > 0 && hash_equals($csrf_token, $submitted_csrf)) {
            $stmt = $pdo->prepare("UPDATE users SET phone_otp = :phone_otp, phone_verified = :phone_verified WHERE id = :id");
            if ($stmt->execute([
                ':phone_otp'      => $phone_otp !== '' ? $phone_otp : null,
                ':phone_verified' => $phone_verified,
                ':id'             => $user_id
            ])) {
                $message = "Phone OTP and verification status updated successfully! You can now send this code to customer.";
                $message_type = "success";
            } else {
                $message = "Failed to update phone OTP.";
                $message_type = "danger";
            }
        }
    }

    // 4. DELETE CUSTOMER ACCOUNT
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
$query  = "SELECT id, customar_name, email, phone, role, verify_status, phone_verified, phone_otp, email_verified, created_at FROM users WHERE 1=1";
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
    error_log('Admin users list could not be loaded: ' . $e->getMessage());
    $users = [];
    $message = 'Users could not be loaded. Please try again.';
    $message_type = 'danger';
}

include_once 'includes/header.php';
?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Manage Users</h1>
        <small class="text-muted">Total Users: <?= count($users); ?></small>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New User
    </button>
</div>

<!-- Alerts -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
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
                        <th class="bg-success text-white">Phone & Verification</th>
                        <th class="bg-success text-white">Email</th>
                        <th class="bg-success text-white">Role</th>
                        <th class="bg-success text-white">Account Status</th>
                        <th class="bg-success text-white">Joined Date</th>
                        <th class="bg-success text-white text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-bold">#<?= $user['id']; ?></td>
                        <td>
                            <div class="fw-semibold text-white"><?= htmlspecialchars($user['customar_name'] ?? ''); ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                            <?php if ((int)($user['phone_verified'] ?? 0) === 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.75rem;">
                                    <i class="bi bi-check-circle me-1"></i>Phone Verified
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.75rem;">
                                    <i class="bi bi-x-circle me-1"></i>Phone Unverified
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($user['phone_otp'])): ?>
                                <div class="small mt-1 text-muted">
                                    OTP: <span class="badge bg-light text-primary border fw-bold"><?= htmlspecialchars($user['phone_otp']); ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($user['email'] ?? 'N/A'); ?></div>
                            <?php if (!empty($user['email'])): ?>
                                <?php if ((int)($user['email_verified'] ?? 0) === 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.75rem;">
                                        <i class="bi bi-check-circle me-1"></i>Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.75rem;">Unverified</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
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
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
                        <td class="text-end text-nowrap">
                            <!-- Phone OTP / Verify Button -->
                            <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#otpModal<?= $user['id']; ?>" title="Phone OTP & Verification">
                                <i class="bi bi-phone"></i> OTP / Verify
                            </button>

                            <!-- Edit Access Button -->
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['id']; ?>">
                                <i class="bi bi-pencil-square"></i> Edit
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

                    <!-- Phone OTP & Verification Modal -->
                    <div class="modal fade" id="otpModal<?= $user['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                                <form method="POST" action="users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>">
                                    <input type="hidden" name="action" value="set_phone_otp">
                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-phone text-success me-2"></i>Phone OTP & Verification</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded border">
                                            <div><strong>Customer:</strong> <?= htmlspecialchars($user['customar_name'] ?? ''); ?></div>
                                            <div><strong>Phone Number:</strong> <span class="text-primary fw-bold fs-6"><?= htmlspecialchars($user['phone'] ?? ''); ?></span></div>
                                            <div class="mt-1">
                                                <strong>Current Phone Status:</strong> 
                                                <?= (int)($user['phone_verified'] ?? 0) === 1 
                                                    ? '<span class="badge bg-success">Verified</span>' 
                                                    : '<span class="badge bg-warning text-dark">Unverified</span>'; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Set / Generate OTP Code</label>
                                            <div class="input-group">
                                                <input type="text" name="phone_otp" id="otpInput<?= $user['id']; ?>" class="form-control fs-5 fw-bold text-center tracking-wider" placeholder="e.g. 123456" maxlength="10" value="<?= htmlspecialchars($user['phone_otp'] ?? ''); ?>">
                                                <button type="button" class="btn btn-outline-secondary" onclick="generateOtp(<?= $user['id']; ?>)">
                                                    <i class="bi bi-shuffle"></i> Random OTP
                                                </button>
                                            </div>
                                            <div class="form-text">এখানে একটি OTP কোড লিখুন বা র্যান্ডম জেনারেট করুন এবং সেভ করুন। কাস্টমার এই কোডটি দিয়ে ভেরিফাই করবে।</div>
                                        </div>

                                        <!-- SMS Template Helper -->
                                        <div class="mb-3 p-2 bg-light rounded border">
                                            <label class="form-label fw-bold small text-secondary mb-1">SMS Template (কপি করে কাস্টমারকে পাঠান):</label>
                                            <div class="p-2 bg-white rounded border small text-dark" id="smsText<?= $user['id']; ?>">AmarShop: Your verification code is <?= !empty($user['phone_otp']) ? htmlspecialchars($user['phone_otp']) : '______'; ?>. Use this code to verify your phone number.</div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="copySmsText(<?= $user['id']; ?>)">
                                                <i class="bi bi-clipboard me-1"></i> Copy SMS Text
                                            </button>
                                        </div>

                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" role="switch" name="phone_verified" value="1" id="pvSwitch<?= $user['id']; ?>" <?= (int)($user['phone_verified'] ?? 0) === 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="pvSwitch<?= $user['id']; ?>">
                                                Mark Phone as Verified Directly
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success">Save OTP & Status</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Role & Status Modal -->
                    <div class="modal fade" id="editUserModal<?= $user['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                                <form method="POST" action="users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>">
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
                                            <label class="form-label fw-bold">Account Status</label>
                                            <select name="verify_status" class="form-select" required>
                                                <option value="1" <?= (int)$user['verify_status'] === 1 ? 'selected' : ''; ?>>Active (Can Login)</option>
                                                <option value="0" <?= (int)$user['verify_status'] === 0 ? 'selected' : ''; ?>>Inactive (Blocked)</option>
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
                        <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email (optional)">
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
                        <label class="form-label fw-bold">Account Status <span class="text-danger">*</span></label>
                        <select name="verify_status" class="form-select" required>
                            <option value="1" selected>Active (Can Login)</option>
                            <option value="0">Inactive (Blocked)</option>
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

<script>
function generateOtp(userId) {
    const code = Math.floor(100000 + Math.random() * 900000);
    const input = document.getElementById('otpInput' + userId);
    if (input) {
        input.value = code;
        const smsEl = document.getElementById('smsText' + userId);
        if (smsEl) {
            smsEl.textContent = 'AmarShop: Your verification code is ' + code + '. Use this code to verify your phone number.';
        }
    }
}

function copySmsText(userId) {
    const smsEl = document.getElementById('smsText' + userId);
    if (!smsEl) return;
    const text = smsEl.textContent.trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            alert('SMS template copied to clipboard!');
        });
    } else {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('SMS template copied to clipboard!');
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>
