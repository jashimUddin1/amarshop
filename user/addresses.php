<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/user_addresses.php';
require_once __DIR__ . '/../core/user_profile_picture.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
ensure_user_addresses_table($pdo);
ensure_user_profile_pictures_table($pdo);

$user_stmt = $pdo->prepare('SELECT customar_name, phone, address FROM users WHERE id = :id');
$user_stmt->execute([':id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit('User account not found.');
}
$user['profile_picture'] = get_user_profile_picture($pdo, $user_id);
seed_user_address($pdo, $user_id, $user['address'] ?? null);

if (empty($_SESSION['address_csrf'])) {
    $_SESSION['address_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['address_csrf'], $token)) {
        http_response_code(400);
        exit('Invalid request token.');
    }

    $action = $_POST['action'] ?? '';
    $address_id = filter_var($_POST['address_id'] ?? null, FILTER_VALIDATE_INT);
    $label = trim((string)($_POST['label'] ?? 'Home Address'));
    $address = trim((string)($_POST['address'] ?? ''));

    try {
        $pdo->beginTransaction();
        if ($action === 'save' && $label !== '' && $address !== '') {
            if ($address_id !== false && $address_id !== null && $address_id > 0) {
                $existing_stmt = $pdo->prepare('SELECT is_default FROM user_addresses WHERE id = :id AND user_id = :user_id FOR UPDATE');
                $existing_stmt->execute([':id' => $address_id, ':user_id' => $user_id]);
                $existing_address = $existing_stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existing_address) {
                    throw new RuntimeException('The selected address could not be found.');
                }
                $stmt = $pdo->prepare('UPDATE user_addresses SET label = :label, address = :address WHERE id = :id AND user_id = :user_id');
                $stmt->execute([':label' => $label, ':address' => $address, ':id' => $address_id, ':user_id' => $user_id]);
                if ((int)$existing_address['is_default'] === 1) {
                    $pdo->prepare('UPDATE users SET address = :address WHERE id = :user_id')->execute([
                        ':address' => $address, ':user_id' => $user_id,
                    ]);
                }
                $_SESSION['address_notice'] = 'Address updated successfully.';
            } else {
                $is_first_stmt = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
                $is_first_stmt->execute([':user_id' => $user_id]);
                $is_first = (int)$is_first_stmt->fetchColumn() === 0;
                $stmt = $pdo->prepare('INSERT INTO user_addresses (user_id, label, address, is_default) VALUES (:user_id, :label, :address, :is_default)');
                $stmt->execute([':user_id' => $user_id, ':label' => $label, ':address' => $address, ':is_default' => $is_first ? 1 : 0]);
                if ($is_first) {
                    $pdo->prepare('UPDATE users SET address = :address WHERE id = :user_id')->execute([
                        ':address' => $address, ':user_id' => $user_id,
                    ]);
                }
                $_SESSION['address_notice'] = 'Address added successfully.';
            }
        } elseif ($action === 'default' && $address_id !== false && $address_id !== null && $address_id > 0) {
            $find_stmt = $pdo->prepare('SELECT address FROM user_addresses WHERE id = :id AND user_id = :user_id FOR UPDATE');
            $find_stmt->execute([':id' => $address_id, ':user_id' => $user_id]);
            $selected = $find_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selected) {
                throw new RuntimeException('The selected address could not be found.');
            }
            $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id')->execute([':user_id' => $user_id]);
            $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id')->execute([':id' => $address_id, ':user_id' => $user_id]);
            $pdo->prepare('UPDATE users SET address = :address WHERE id = :user_id')->execute([':address' => $selected['address'], ':user_id' => $user_id]);
            $_SESSION['address_notice'] = 'Default shipping address updated.';
        } elseif ($action === 'delete' && $address_id !== false && $address_id !== null && $address_id > 0) {
            $find_stmt = $pdo->prepare('SELECT is_default FROM user_addresses WHERE id = :id AND user_id = :user_id FOR UPDATE');
            $find_stmt->execute([':id' => $address_id, ':user_id' => $user_id]);
            $selected = $find_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selected) {
                throw new RuntimeException('The selected address could not be found.');
            }
            $pdo->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id')->execute([':id' => $address_id, ':user_id' => $user_id]);
            if ((int)$selected['is_default'] === 1) {
                $next_stmt = $pdo->prepare('SELECT id, address FROM user_addresses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
                $next_stmt->execute([':user_id' => $user_id]);
                $next = $next_stmt->fetch(PDO::FETCH_ASSOC);
                if ($next) {
                    $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id')->execute([':id' => $next['id']]);
                    $pdo->prepare('UPDATE users SET address = :address WHERE id = :user_id')->execute([':address' => $next['address'], ':user_id' => $user_id]);
                } else {
                    $pdo->prepare("UPDATE users SET address = '' WHERE id = :user_id")->execute([':user_id' => $user_id]);
                }
            }
            $_SESSION['address_notice'] = 'Address deleted successfully.';
        } else {
            throw new RuntimeException('Please provide a valid address and action.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Saved address update failed: ' . $e->getMessage());
        $_SESSION['address_error'] = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Address could not be saved. Please try again.';
    }
    header('Location: addresses.php');
    exit;
}

$addresses_stmt = $pdo->prepare('SELECT id, label, address, is_default FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC');
$addresses_stmt->execute([':user_id' => $user_id]);
$addresses = $addresses_stmt->fetchAll(PDO::FETCH_ASSOC);
$notice = $_SESSION['address_notice'] ?? '';
$error = $_SESSION['address_error'] ?? '';
unset($_SESSION['address_notice'], $_SESSION['address_error']);
$nav_items = [
    ['label' => 'My Profile', 'icon' => 'person-vcard', 'href' => 'profile.php'],
    ['label' => 'My Orders', 'icon' => 'box-seam', 'href' => 'my_orders.php'],
    ['label' => 'Transaction History', 'icon' => 'receipt', 'href' => 'transactions.php'],
    ['label' => 'Delivery Address', 'icon' => 'geo-alt', 'href' => 'addresses.php', 'active' => true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Address</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .account-nav .nav-link { color: #344054; border: 1px solid #e5e7eb; border-radius: .65rem; margin-bottom: .45rem; }
        .account-nav .nav-link.active { color: #087f79; background: #eff8f7; border-color: #e5efee; }
    </style>
</head>
<body class="bg-light py-3 py-md-4">
<main class="container">
    <div class="d-flex align-items-center gap-2 text-muted small mb-3">
        <a href="../index.php" class="link-secondary text-decoration-none">Home</a>
        <i class="bi bi-chevron-right"></i><span>Delivery Address</span>
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
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h1 class="h4 fw-bold mb-0">Manage Address</h1>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="prepareAddressForm()">
                    <i class="bi bi-plus-lg me-1"></i>Add New Address
                </button>
            </div>
            <?php if ($notice !== ''): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <?php if (!$addresses): ?>
                <div class="card border shadow-sm"><div class="card-body text-center py-5">
                    <i class="bi bi-geo-alt fs-1 text-muted"></i><h2 class="h6 mt-3">No saved addresses</h2>
                    <p class="text-muted small">Add an address to use it during checkout.</p>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="prepareAddressForm()">Add New Address</button>
                </div></div>
            <?php else: ?>
                <div class="d-grid gap-3">
                    <?php foreach ($addresses as $saved_address): ?>
                        <article class="card border shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <?= htmlspecialchars($saved_address['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ((int)$saved_address['is_default'] === 1): ?>
                                        <span class="badge rounded-pill bg-info-subtle text-info-emphasis ms-1">Default</span>
                                        <span class="text-success ms-1">(Shipping Address)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-secondary" data-bs-toggle="dropdown" aria-label="Address actions"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button type="button" class="dropdown-item" onclick='editAddress(<?= json_encode(['id' => (int)$saved_address['id'], 'label' => $saved_address['label'], 'address' => $saved_address['address']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Edit</button></li>
                                        <?php if ((int)$saved_address['is_default'] !== 1): ?>
                                            <li><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['address_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="default"><input type="hidden" name="address_id" value="<?= (int)$saved_address['id']; ?>"><button class="dropdown-item" type="submit">Set as default</button></form></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><form method="post" onsubmit="return confirm('Delete this saved address?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['address_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?= (int)$saved_address['id']; ?>"><button class="dropdown-item text-danger" type="submit">Delete</button></form></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center" style="width:42px;height:42px;"><i class="bi bi-house-door fs-5"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?= htmlspecialchars($user['customar_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="small text-secondary"><?= nl2br(htmlspecialchars($saved_address['address'], ENT_QUOTES, 'UTF-8')); ?></div>
                                </div>
                                <?php if ((int)$saved_address['is_default'] === 1): ?><i class="bi bi-check-circle-fill text-success fs-5" title="Default address"></i><?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['address_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="default"><input type="hidden" name="address_id" value="<?= (int)$saved_address['id']; ?>"><button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Set as default" title="Set as default"><i class="bi bi-circle"></i></button></form><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h2 class="modal-title fs-5" id="addressModalTitle">Add New Address</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['address_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="address_id" id="addressId">
                <div class="mb-3"><label for="addressLabel" class="form-label">Address Label</label><input type="text" name="label" id="addressLabel" class="form-control" maxlength="80" value="Home Address" required></div>
                <div><label for="addressText" class="form-label">Full Address</label><textarea name="address" id="addressText" class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-success">Save Address</button></div>
        </form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function prepareAddressForm() {
    document.getElementById('addressModalTitle').textContent = 'Add New Address';
    document.getElementById('addressId').value = '';
    document.getElementById('addressLabel').value = 'Home Address';
    document.getElementById('addressText').value = '';
}
function editAddress(address) {
    document.getElementById('addressModalTitle').textContent = 'Edit Address';
    document.getElementById('addressId').value = address.id;
    document.getElementById('addressLabel').value = address.label;
    document.getElementById('addressText').value = address.address;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addressModal')).show();
}
</script>
</body>
</html>
