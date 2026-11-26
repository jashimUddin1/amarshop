<?php
require_once __DIR__ . '/config.php';
require_admin();

$actor_role = (string)$_SESSION['user_role'];
$assignable_roles = admin_assignable_roles($actor_role);
$page_title = 'Add Admin Staff';
$current_page = 'admin-users';
$signup_csrf_token = $_SESSION['admin_signup_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_signup_csrf'] = $signup_csrf_token;
$signup_message = is_string($_SESSION['admin_signup_message'] ?? null) ? $_SESSION['admin_signup_message'] : '';
$signup_message_type = is_string($_SESSION['admin_signup_message_type'] ?? null) ? $_SESSION['admin_signup_message_type'] : 'info';
unset($_SESSION['admin_signup_message'], $_SESSION['admin_signup_message_type']);
$signup_old = is_array($_SESSION['admin_signup_old'] ?? null) ? $_SESSION['admin_signup_old'] : [];
unset($_SESSION['admin_signup_old']);

include_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 py-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Add Admin Staff</h1>
        <p class="small text-muted mb-0">Signed in as <?= htmlspecialchars($actor_role, ENT_QUOTES, 'UTF-8'); ?>. You can create staff roles below your role.</p>
    </div>
    <a class="btn btn-outline-secondary" href="../admin_users.php"><i class="bi bi-arrow-left me-1"></i>Back to Admin Staff</a>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($signup_message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($signup_message_type, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($signup_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!$assignable_roles): ?>
        <div class="alert alert-warning">আপনার বর্তমান role থেকে admin staff account তৈরি করার অনুমতি নেই।</div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="signup_core.php" method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($signup_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" maxlength="90" value="<?= htmlspecialchars((string)($signup_old['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">Last Name </label>
                        <input type="text" class="form-control" id="last_name" name="last_name" maxlength="90" value="<?= htmlspecialchars((string)($signup_old['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="191" value="<?= htmlspecialchars((string)($signup_old['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="40" value="<?= htmlspecialchars((string)($signup_old['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Choose a role</option>
                            <?php foreach ($assignable_roles as $role): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" <?= ($signup_old['role'] ?? '') === $role ? 'selected' : ''; ?>><?= htmlspecialchars(ucwords(strtolower($role)), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create Staff Account</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
