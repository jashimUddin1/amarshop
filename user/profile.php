<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/user_profile_picture.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
ensure_user_profile_pictures_table($pdo);
$message = '';
$message_type = '';
if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'save_profile';
    $name = trim((string)($_POST['customar_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if (!is_string($csrf) || !hash_equals($_SESSION['profile_csrf'], $csrf)) {
        http_response_code(400);
        exit('Invalid request token.');
    } elseif ($action === 'upload_profile_picture') {
        try {
            store_user_profile_picture($pdo, $user_id, $_FILES['profile_picture'] ?? []);
            $message = 'Profile picture updated successfully.';
            $message_type = 'success';
        } catch (InvalidArgumentException | RuntimeException $e) {
            $message = $e->getMessage();
            $message_type = 'warning';
        } catch (Throwable $e) {
            error_log('User profile picture update failed: ' . $e->getMessage());
            $message = 'Profile picture could not be updated. Please try again.';
            $message_type = 'danger';
        }
    } elseif ($action === 'remove_profile_picture') {
        try {
            clear_user_profile_picture($pdo, $user_id);
            $message = 'Profile picture removed.';
            $message_type = 'success';
        } catch (Throwable $e) {
            error_log('User profile picture removal failed: ' . $e->getMessage());
            $message = 'Profile picture could not be removed. Please try again.';
            $message_type = 'danger';
        }
    } elseif ($action !== 'save_profile') {
        http_response_code(400);
        exit('Invalid profile action.');
    } elseif ($name === '' || $phone === '') {
        $message = 'Name and phone number are required.';
        $message_type = 'warning';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'warning';
    } else {
        try {
            if ($email !== '') {
                $email_stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
                $email_stmt->execute([':email' => $email, ':id' => $user_id]);
                if ($email_stmt->fetchColumn()) {
                    throw new InvalidArgumentException('This email address is already used by another account.');
                }
            }
            $update_stmt = $pdo->prepare(
                'UPDATE users SET customar_name = :name, email = :email, phone = :phone WHERE id = :id'
            );
            $update_stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':id' => $user_id,
            ]);
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $message = 'Profile updated successfully.';
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log('User profile update failed: ' . $e->getMessage());
            $message = 'Profile could not be updated. Please try again.';
            $message_type = 'danger';
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            $message_type = 'warning';
        }
    }
}

$user_stmt = $pdo->prepare('SELECT customar_name, email, phone, address FROM users WHERE id = :id');
$user_stmt->execute([':id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('User profile not found.');
}
$user['profile_picture'] = get_user_profile_picture($pdo, $user_id);

$nav_items = [
    ['label' => 'My Profile', 'icon' => 'person-vcard', 'href' => 'profile.php', 'active' => true],
    ['label' => 'My Orders', 'icon' => 'box-seam', 'href' => 'my_orders.php'],
    ['label' => 'Transaction History', 'icon' => 'receipt', 'href' => 'transactions.php'],
    ['label' => 'Delivery Address', 'icon' => 'geo-alt', 'href' => 'addresses.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .account-nav .nav-link { color: #344054; border: 1px solid #e5e7eb; border-radius: .65rem; margin-bottom: .45rem; }
        .account-nav .nav-link.active { color: #087f79; background: #eff8f7; border-color: #e5efee; }
        .account-nav .nav-link:hover { background: #f6f8fa; }
    </style>
</head>
<body class="bg-light py-3 py-md-4">
<main class="container">
    <div class="d-flex align-items-center gap-2 text-muted small mb-3">
        <a href="../index.php" class="link-secondary text-decoration-none">Home</a>
        <i class="bi bi-chevron-right"></i><span>My Profile</span>
    </div>
    <div class="row g-3 g-lg-4">
        <aside class="col-12 col-lg-3">
            <div class="card border shadow-sm mb-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <?php if ($user['profile_picture']): ?>
                        <img src="../uploads/<?= htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="rounded-circle border object-fit-cover flex-shrink-0" style="width:64px;height:64px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" style="width:64px;height:64px;">
                            <?= htmlspecialchars(strtoupper(mb_substr((string)$user['customar_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-width-0">
                        <div class="fw-bold text-truncate"><?= htmlspecialchars($user['customar_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
            <nav class="account-nav d-grid" aria-label="Account navigation">
                <?php foreach ($nav_items as $item): ?>
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="nav-link px-3 py-2 <?= !empty($item['active']) ? 'active' : ''; ?>">
                        <i class="bi bi-<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        <i class="bi bi-chevron-right float-end mt-1 small"></i>
                    </a>
                <?php endforeach; ?>
                <a href="../auth/logout.php" class="nav-link px-3 py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </nav>
        </aside>

        <section class="col-12 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 fw-bold mb-0">My Profile</h1>
                <a href="addresses.php" class="btn btn-success btn-sm"><i class="bi bi-geo-alt me-1"></i>Manage Addresses</a>
            </div>
            <div class="card border shadow-sm">
                <div class="card-header bg-white fw-semibold">Personal Information</div>
                <div class="card-body p-3 p-md-4">
                    <?php if ($message !== ''): ?>
                        <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="upload_profile_picture">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG, or WEBP. Maximum 3 MB.</div>
                            <button type="submit" class="btn btn-outline-success btn-sm mt-2">Upload picture</button>
                        </div>
                    </form>
                    <?php if ($user['profile_picture']): ?>
                        <form method="post" class="mb-4" onsubmit="return confirm('Remove your profile picture?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="remove_profile_picture">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove picture</button>
                        </form>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="save_profile">
                        <div class="mb-3">
                            <label for="customar_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="customar_name" name="customar_name" value="<?= htmlspecialchars($user['customar_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Shipping Address</label>
                            <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="form-text">Change or manage saved addresses from Delivery Address.</div>
                        </div>
                        <button type="submit" class="btn btn-success px-4">Save Changes</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>
</body>
</html>
