<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/user_addresses.php';
require_once __DIR__ . '/../core/user_profile_picture.php';
require_admin();

$page_title = "Manage Users";
$current_page = "users";

$message = '';
$message_type = '';
$staff_created = ($_GET['staff'] ?? '') === 'created';
$can_manage_staff = function_exists('admin_can_manage_staff')
    && admin_can_manage_staff((string)($_SESSION['user_role'] ?? ''));
if ($staff_created) {
    $message = 'Admin staff account created successfully.';
    $message_type = 'success';
}
$csrf_token = $_SESSION['users_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['users_csrf_token'] = $csrf_token;

// Handling Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted_csrf) || !hash_equals($csrf_token, $submitted_csrf)) {
        http_response_code(400);
        exit('Invalid request token.');
    }

    // 1. ADD CUSTOMER
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $name          = trim($_POST['customar_name'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $verify_status = (int)($_POST['verify_status'] ?? 1);
        $password      = trim($_POST['password'] ?? '');

        if (!empty($name) && !empty($phone) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $checkStmt->execute([':email' => $email]);
            $checkEmail = (bool)$checkStmt->fetchColumn();

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

                $insertStmt = $pdo->prepare("INSERT INTO users (customar_name, email, phone, password, role, verify_status, phone_verified, email_verified, created_at, reset_count, pwd_up_count) VALUES (:name, :email, :phone, :password, 'user', :verify_status, 0, 0, :created_at, 0, 0)");
                
                $result = $insertStmt->execute([
                    ':name'          => $name,
                    ':email'         => $email,
                    ':phone'         => $phone,
                    ':password'      => $hashed_password,
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
            $message = "Enter a name, phone, valid email address, and password.";
            $message_type = "warning";
        }
    }

    // 2. CHANGE ROLE & VERIFY STATUS
    if (isset($_POST['action']) && $_POST['action'] === 'update_user_access') {
        $user_id       = (int)($_POST['user_id'] ?? 0);
        $verify_status = (int)($_POST['verify_status'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));

        if ($user_id > 0 && in_array($verify_status, [0, 1], true) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $duplicateEmailStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id <> :id LIMIT 1');
            $duplicateEmailStmt->execute([':email' => $email, ':id' => $user_id]);
            if ($duplicateEmailStmt->fetchColumn()) {
                $message = 'That email address is already assigned to another account.';
                $message_type = 'danger';
            } else {
                $currentUserStmt = $pdo->prepare("
                    SELECT email, email_verified, otp_code, otp_expires_at, remember_token
                    FROM users
                    WHERE id = :id
                        AND LOWER(COALESCE(role, 'user')) NOT IN ('admin', 'manager', 'ceo', 'md', 'assistant manager', 'asst. manager', 'ass. manager', 'salesman')
                ");
                $currentUserStmt->execute([':id' => $user_id]);
                $currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
                if (!$currentUser) {
                    $message = 'Customer account not found.';
                    $message_type = 'danger';
                } else {
                    $emailChanged = $currentUser['email'] === null || strcasecmp((string)$currentUser['email'], $email) !== 0;
                    $emailVerified = isset($_POST['email_verified']);
                    $verificationChanged = (int)$currentUser['email_verified'] !== (int)$emailVerified;
                    $updateStmt = $pdo->prepare("
                        UPDATE users
                        SET role = 'user', verify_status = :verify_status, email = :email,
                            email_verified = :email_verified, otp_code = :otp_code,
                            otp_expires_at = :otp_expires_at, remember_token = :remember_token
                        WHERE id = :id
                            AND LOWER(COALESCE(role, 'user')) NOT IN ('admin', 'manager', 'ceo', 'md', 'assistant manager', 'asst. manager', 'ass. manager', 'salesman')
                    ");
                    $result = $updateStmt->execute([
                        ':verify_status' => $verify_status,
                        ':email' => $email,
                        ':email_verified' => (int)$emailVerified,
                        ':otp_code' => ($emailChanged || $verificationChanged) ? null : $currentUser['otp_code'],
                        ':otp_expires_at' => ($emailChanged || $verificationChanged) ? null : $currentUser['otp_expires_at'],
                        ':remember_token' => ($emailChanged || !$emailVerified) ? null : $currentUser['remember_token'],
                        ':id' => $user_id,
                    ]);
                    if ($result) {
                        if ($emailChanged && !$emailVerified) {
                            $message = 'Customer email updated. The customer must verify the new address before logging in.';
                        } elseif ($emailVerified && ($emailChanged || $verificationChanged)) {
                            $message = 'Customer email marked as verified successfully.';
                        } elseif (!$emailVerified && $verificationChanged) {
                            $message = 'Customer email verification removed successfully.';
                        } else {
                            $message = 'Customer account status updated successfully!';
                        }
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update user access.';
                        $message_type = 'danger';
                    }
                }
            }
        } else {
            $message = 'Enter a valid customer email address and account status.';
            $message_type = 'warning';
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
                } elseif (in_array(strtolower((string)$target_user['role']), ['admin', 'manager', 'ceo', 'md', 'assistant manager', 'asst. manager', 'ass. manager', 'salesman'], true)) {
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
$query  = "SELECT id, customar_name, email, phone, role, verify_status, phone_verified, phone_otp, email_verified, created_at FROM users WHERE LOWER(COALESCE(role, 'user')) NOT IN ('admin', 'manager', 'ceo', 'md', 'assistant manager', 'asst. manager', 'ass. manager', 'salesman')";
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
    <div class="d-flex gap-2">
    <?php if ($can_manage_staff): ?><a class="btn btn-outline-primary" href="auth/signup.php"><i class="bi bi-person-badge me-1"></i>Add Admin Staff</a><?php endif; ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New User
    </button>
    </div>
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
                            <div class="fw-semibold"><?= htmlspecialchars($user['customar_name'] ?? ''); ?></div>
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
                            <span class="badge bg-primary">Customer</span>
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

                            <form method="POST" action="users.php<?= $search !== '' ? '?search=' . rawurlencode($search) : ''; ?>" class="d-inline" onsubmit="return confirm('Delete this customer account? Saved addresses will be removed, but order history and request records will remain.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
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
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Manage Customer - <?= htmlspecialchars($user['customar_name'] ?? ''); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <div class="form-text">Staff roles are managed separately from customer accounts.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Customer email address</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                            <div class="form-text">You can mark this email as verified directly. Changing the address clears any pending OTP.</div>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" name="email_verified" value="1" id="evSwitch<?= (int)$user['id']; ?>" <?= (int)($user['email_verified'] ?? 0) === 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="evSwitch<?= (int)$user['id']; ?>">
                                                Mark Email as Verified Directly
                                            </label>
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
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
                        <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
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
