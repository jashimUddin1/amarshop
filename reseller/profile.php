<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload and try again.');
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $current_password = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new_password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        if ($name === '' || strlen($name) > 180 || $phone === '' || strlen($phone) > 40 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
            throw new InvalidArgumentException('Enter a valid name, phone and email.');
        }
        $duplicate = $pdo->prepare('SELECT id FROM reseller_accounts WHERE (email = :email OR phone = :phone) AND id <> :id LIMIT 1');
        $duplicate->execute([':email' => $email, ':phone' => $phone, ':id' => $reseller_id]);
        if ($duplicate->fetchColumn()) {
            throw new InvalidArgumentException('That email or phone is already used by another reseller account.');
        }
        if ($new_password !== '') {
            $password_stmt = $pdo->prepare('SELECT password_hash FROM reseller_accounts WHERE id = :id');
            $password_stmt->execute([':id' => $reseller_id]);
            if (strlen($new_password) < 8 || !password_verify($current_password, (string)$password_stmt->fetchColumn())) {
                throw new InvalidArgumentException('Enter your current password and a new password of at least 8 characters.');
            }
            $update = $pdo->prepare('UPDATE reseller_accounts SET name = :name, phone = :phone, email = :email, password_hash = :password WHERE id = :id');
            $update->execute([
                ':name' => $name, ':phone' => $phone, ':email' => $email,
                ':password' => password_hash($new_password, PASSWORD_DEFAULT), ':id' => $reseller_id,
            ]);
        } else {
            $update = $pdo->prepare('UPDATE reseller_accounts SET name = :name, phone = :phone, email = :email WHERE id = :id');
            $update->execute([':name' => $name, ':phone' => $phone, ':email' => $email, ':id' => $reseller_id]);
        }
        $_SESSION['reseller_name'] = $name;
        $_SESSION['reseller_flash'] = ['type' => 'success', 'message' => 'Your profile was updated.'];
        header('Location: profile.php');
        exit;
    } catch (InvalidArgumentException $e) {
        $notice = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        error_log('Reseller profile update failed: ' . $e->getMessage());
        $notice = ['type' => 'danger', 'message' => 'Your profile could not be updated. Please try again.'];
    }
}

$page_title = 'Profile';
$current_page = 'profile';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 fw-bold mb-4">Profile</h1>
<div class="card border-0 shadow-sm" style="max-width:720px"><div class="card-body p-4">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= reseller_h($csrf_token); ?>">
        <label class="form-label" for="profileName">Full name</label><input class="form-control mb-3" id="profileName" name="name" maxlength="180" value="<?= reseller_h($reseller['name']); ?>" required>
        <label class="form-label" for="profilePhone">Phone</label><input class="form-control mb-3" id="profilePhone" name="phone" type="tel" maxlength="40" value="<?= reseller_h($reseller['phone']); ?>" required>
        <label class="form-label" for="profileEmail">Email</label><input class="form-control mb-4" id="profileEmail" name="email" type="email" maxlength="191" value="<?= reseller_h($reseller['email']); ?>" required>
        <hr><h2 class="h6 fw-semibold">Change password (optional)</h2>
        <label class="form-label" for="currentPassword">Current password</label><input class="form-control mb-3" id="currentPassword" name="current_password" type="password" autocomplete="current-password">
        <label class="form-label" for="newPassword">New password</label><input class="form-control mb-4" id="newPassword" name="new_password" type="password" minlength="8" autocomplete="new-password">
        <button class="btn btn-success" type="submit">Save profile</button>
    </form>
    <div class="mt-4"><div class="small text-muted">Referral code</div><code><?= reseller_h($reseller['referral_code']); ?></code></div>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
