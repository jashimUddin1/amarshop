<?php
require_once 'auth/config.php';
require_once __DIR__ . '/../core/user_profile_picture.php';
require_admin();

$page_title = 'Admin Profile';
$current_page = 'profile';
$message = '';
$message_type = '';
$user_id = (int)$_SESSION['user_id'];
ensure_admin_profile_pictures_table($pdo);
$csrf_token = $_SESSION['admin_profile_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_profile_csrf'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    $name_input = $_POST['customar_name'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = is_string($name_input) ? trim($name_input) : '';
    $current_password = is_string($current_password) ? $current_password : '';
    $new_password = is_string($new_password) ? $new_password : '';
    $confirm_password = is_string($confirm_password) ? $confirm_password : '';
    $action = $_POST['action'] ?? 'save_profile';

    if (!is_string($submitted_token) || !hash_equals($csrf_token, $submitted_token)) {
        http_response_code(400);
        $message = 'This request could not be verified. Please reload the page and try again.';
        $message_type = 'danger';
    } elseif ($action === 'upload_profile_picture') {
        try {
            store_user_profile_picture($pdo, $user_id, $_FILES['profile_picture'] ?? [], true);
            $message = 'Profile picture updated successfully.';
            $message_type = 'success';
        } catch (InvalidArgumentException | RuntimeException $e) {
            $message = $e->getMessage();
            $message_type = 'warning';
        } catch (Throwable $e) {
            error_log('Admin profile picture update failed: ' . $e->getMessage());
            $message = 'Profile picture could not be updated. Please try again.';
            $message_type = 'danger';
        }
    } elseif ($action === 'remove_profile_picture') {
        try {
            clear_user_profile_picture($pdo, $user_id, true);
            $message = 'Profile picture removed.';
            $message_type = 'success';
        } catch (Throwable $e) {
            error_log('Admin profile picture removal failed: ' . $e->getMessage());
            $message = 'Profile picture could not be removed. Please try again.';
            $message_type = 'danger';
        }
    } elseif ($action !== 'save_profile') {
        http_response_code(400);
        exit('Invalid profile action.');
    } elseif ($name === '') {
        $message = 'Enter a valid name.';
        $message_type = 'warning';
    } elseif (($new_password !== '' || $confirm_password !== '' || $current_password !== '') && ($new_password === '' || strlen($new_password) < 8 || !hash_equals($new_password, $confirm_password))) {
        $message = 'To change your password, enter matching new passwords with at least 8 characters.';
        $message_type = 'warning';
    } else {
        try {
            $update_password = $new_password !== '';
            $current_user = null;

            if ($update_password) {
                $password_stmt = $pdo->prepare('SELECT password FROM admin_users WHERE id = :id');
                $password_stmt->execute([':id' => $user_id]);
                $current_user = $password_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$current_user || !password_verify($current_password, $current_user['password'])) {
                    $message = 'Your current password is incorrect.';
                    $message_type = 'warning';
                }
            }

            if ($message === '') {
                $sql = 'UPDATE admin_users SET customar_name = :name';
                $params = [':name' => $name, ':id' => $user_id];

                if ($update_password) {
                    $sql .= ', password = :password';
                    $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
                }

                $sql .= ' WHERE id = :id';
                $update = $pdo->prepare($sql);
                $update->execute($params);

                $_SESSION['user_name'] = $name;
                $message = 'Profile updated successfully.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            error_log('Admin profile update failed: ' . $e->getMessage());
            $message = 'Your profile could not be updated. Please try again.';
            $message_type = 'danger';
        }
    }
}

$profile_stmt = $pdo->prepare('SELECT customar_name, email, phone, role FROM admin_users WHERE id = :id LIMIT 1');
$profile_stmt->execute([':id' => $user_id]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    exit('Admin account not found.');
}
$profile['profile_picture'] = get_user_profile_picture($pdo, $user_id, true);

include_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0 pt-4 px-2">
    <div>
        <h1 class="h3 mb-1">Admin Profile</h1>
        <p class="text-muted mb-0">Manage your admin account details and password.</p>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-y-auto mb-3">
    <div class="card-body p-4">
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-3 mb-4">
            <?php if ($profile['profile_picture']): ?>
                <img src="../uploads/<?= htmlspecialchars($profile['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="rounded-circle border object-fit-cover" style="width: 64px; height: 64px;">
            <?php else: ?>
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-person-fill fs-2"></i>
                </div>
            <?php endif; ?>
            <div>
                <h2 class="h5 mb-1"><?= htmlspecialchars($profile['customar_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <span class="badge text-bg-primary"><?= htmlspecialchars(ucfirst($profile['role']), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>

        <form method="post" action="profile.php" enctype="multipart/form-data" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="upload_profile_picture">
            <label for="profile_picture" class="form-label">Profile picture</label>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" required style="max-width: 420px;">
                <button type="submit" class="btn btn-outline-primary">Upload picture</button>
            </div>
            <div class="form-text">JPG, PNG, or WEBP. Maximum 3 MB.</div>
        </form>
        <?php if ($profile['profile_picture']): ?>
            <form method="post" action="profile.php" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="remove_profile_picture">
                <button type="submit" class="btn btn-sm btn-outline-danger">Remove picture</button>
            </form>
        <?php endif; ?>

        <form method="post" action="profile.php" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-md-6">
                <label for="customar_name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="customar_name" name="customar_name" value="<?= htmlspecialchars($profile['customar_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <div class="form-text">Contact the account administrator to update your email address.</div>
            </div>
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone number</label>
                <input type="tel" class="form-control" id="phone" value="<?= htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <div class="form-text">Contact the account administrator to update your phone number.</div>
            </div>

            <div class="col-12"><hr class="my-2"></div>
            <div class="col-12">
                <h3 class="h5 mb-1">Change password <span class="text-muted fw-normal fs-6">(optional)</span></h3>
                <p class="text-muted small mb-0">Leave these fields empty to keep your current password.</p>
            </div>
            <div class="col-md-4">
                <label for="current_password" class="form-label">Current password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
            </div>
            <div class="col-md-4">
                <label for="new_password" class="form-label">New password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" autocomplete="new-password">
            </div>
            <div class="col-md-4">
                <label for="confirm_password" class="form-label">Confirm new password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save profile</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
