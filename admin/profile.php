<?php
require_once 'auth/config.php';
require_admin();

$page_title = 'Admin Profile';
$current_page = 'profile';
$message = '';
$message_type = '';
$user_id = (int)$_SESSION['user_id'];
$csrf_token = $_SESSION['admin_profile_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_profile_csrf'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    $name_input = $_POST['customar_name'] ?? '';
    $email_input = $_POST['email'] ?? '';
    $phone_input = $_POST['phone'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = is_string($name_input) ? trim($name_input) : '';
    $email = is_string($email_input) ? trim($email_input) : '';
    $phone = is_string($phone_input) ? trim($phone_input) : '';
    $current_password = is_string($current_password) ? $current_password : '';
    $new_password = is_string($new_password) ? $new_password : '';
    $confirm_password = is_string($confirm_password) ? $confirm_password : '';

    if (!is_string($submitted_token) || !hash_equals($csrf_token, $submitted_token)) {
        http_response_code(400);
        $message = 'This request could not be verified. Please reload the page and try again.';
        $message_type = 'danger';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/^[0-9+\s().-]+$/', $phone) !== 1 || strlen(preg_replace('/\D/', '', $phone)) < 8) {
        $message = 'Enter a valid name, email address, and phone number.';
        $message_type = 'warning';
    } elseif (($new_password !== '' || $confirm_password !== '' || $current_password !== '') && ($new_password === '' || strlen($new_password) < 8 || !hash_equals($new_password, $confirm_password))) {
        $message = 'To change your password, enter matching new passwords with at least 8 characters.';
        $message_type = 'warning';
    } else {
        try {
            $email_check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $email_check->execute([':email' => $email, ':id' => $user_id]);

            if ($email_check->fetchColumn()) {
                $message = 'That email address is already used by another account.';
                $message_type = 'warning';
            } else {
                $update_password = $new_password !== '';
                $current_user = null;

                if ($update_password) {
                    $password_stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
                    $password_stmt->execute([':id' => $user_id]);
                    $current_user = $password_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$current_user || !password_verify($current_password, $current_user['password'])) {
                        $message = 'Your current password is incorrect.';
                        $message_type = 'warning';
                    }
                }

                if ($message === '') {
                    $sql = 'UPDATE users SET customar_name = :name, email = :email, phone = :phone';
                    $params = [
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':id' => $user_id,
                    ];

                    if ($update_password) {
                        $sql .= ', password = :password, remember_token = NULL';
                        $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    }

                    $sql .= ' WHERE id = :id';
                    $update = $pdo->prepare($sql);
                    $update->execute($params);

                    $_SESSION['user_name'] = $name;
                    $_SESSION['email'] = $email;
                    $message = 'Profile updated successfully.';
                    $message_type = 'success';
                }
            }
        } catch (PDOException $e) {
            error_log('Admin profile update failed: ' . $e->getMessage());
            $message = 'Your profile could not be updated. Please try again.';
            $message_type = 'danger';
        }
    }
}

$profile_stmt = $pdo->prepare('SELECT customar_name, email, phone, role FROM users WHERE id = :id LIMIT 1');
$profile_stmt->execute([':id' => $user_id]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    exit('Admin account not found.');
}

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
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                <i class="bi bi-person-fill fs-2"></i>
            </div>
            <div>
                <h2 class="h5 mb-1"><?= htmlspecialchars($profile['customar_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <span class="badge text-bg-primary"><?= htmlspecialchars(ucfirst($profile['role']), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>

        <form method="post" action="profile.php" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-md-6">
                <label for="customar_name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="customar_name" name="customar_name" value="<?= htmlspecialchars($profile['customar_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone number</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
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
