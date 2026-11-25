<?php
require_once __DIR__ . '/common.php';
$user = pos_require_login();
if ($user['role'] !== 'owner') {
    http_response_code(403);
    exit('Only the POS owner can manage cashiers.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pos_verify_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        if ($action === 'change_own_password') {
            $current_password = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
            $new_password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
            $stmt = $pdo->prepare('SELECT password_hash FROM offline_pos_users WHERE id = :id AND is_active = 1');
            $stmt->execute([':id' => (int)$user['id']]);
            $password_hash = $stmt->fetchColumn();
            if (!is_string($password_hash) || !password_verify($current_password, $password_hash) ||
                strlen($new_password) < 10) {
                throw new InvalidArgumentException('Enter your current password and choose a new password of at least 10 characters.');
            }
            $update = $pdo->prepare('UPDATE offline_pos_users SET password_hash = :password WHERE id = :id');
            $update->execute([
                ':password' => password_hash($new_password, PASSWORD_DEFAULT),
                ':id' => (int)$user['id'],
            ]);
            pos_flash('success', 'Your password has been changed.');
        } elseif ($action === 'create_cashier') {
            $full_name = trim((string)($_POST['full_name'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if ($full_name === '' || strlen($full_name) > 180 ||
                !preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username) || strlen($password) < 10) {
                throw new InvalidArgumentException('Enter a name, username (3-80 letters/numbers) and a password of at least 10 characters.');
            }
            $stmt = $pdo->prepare("
                INSERT INTO offline_pos_users (full_name, username, password_hash, role)
                VALUES (:name, :username, :password, 'cashier')
            ");
            $stmt->execute([
                ':name' => $full_name,
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            pos_flash('success', 'Cashier account created.');
        } elseif ($action === 'update_cashier') {
            $cashier_id = filter_var($_POST['cashier_id'] ?? null, FILTER_VALIDATE_INT);
            $is_active = filter_var($_POST['is_active'] ?? null, FILTER_VALIDATE_INT);
            $new_password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
            if ($cashier_id === false || $cashier_id === null || $cashier_id <= 0 ||
                !in_array($is_active, [0, 1], true) ||
                ($new_password !== '' && strlen($new_password) < 10)) {
                throw new InvalidArgumentException('Choose an active state and a password of at least 10 characters if resetting a password.');
            }
            $stmt = $pdo->prepare('SELECT id FROM offline_pos_users WHERE id = :id AND role = \'cashier\'');
            $stmt->execute([':id' => $cashier_id]);
            if (!$stmt->fetchColumn()) {
                throw new InvalidArgumentException('Cashier account not found.');
            }
            if ($new_password !== '') {
                $update = $pdo->prepare('
                    UPDATE offline_pos_users
                    SET is_active = :active, password_hash = :password
                    WHERE id = :id AND role = \'cashier\'
                ');
                $update->execute([
                    ':active' => $is_active,
                    ':password' => password_hash($new_password, PASSWORD_DEFAULT),
                    ':id' => $cashier_id,
                ]);
            } else {
                $update = $pdo->prepare('UPDATE offline_pos_users SET is_active = :active WHERE id = :id AND role = \'cashier\'');
                $update->execute([':active' => $is_active, ':id' => $cashier_id]);
            }
            pos_flash('success', 'Cashier account updated.');
        } else {
            throw new InvalidArgumentException('Unknown account action.');
        }
    } catch (InvalidArgumentException $exception) {
        pos_flash('error', $exception->getMessage());
    } catch (PDOException $exception) {
        error_log('Offline POS cashier management failed: ' . $exception->getMessage());
        pos_flash('error', 'The account could not be saved. Check that the username is unique and try again.');
    } catch (Throwable $exception) {
        error_log('Offline POS cashier management failed: ' . $exception->getMessage());
        pos_flash('error', 'The cashier account could not be updated. Please try again.');
    }
    header('Location: users.php');
    exit;
}

$cashiers = $pdo->query("
    SELECT id, full_name, username, role, is_active, created_at
    FROM offline_pos_users
    ORDER BY CASE WHEN role = 'owner' THEN 0 ELSE 1 END, full_name
")->fetchAll();
$page_title = 'Cashiers';
pos_header($page_title, 'users', $user);
?>
<section class="pos-page-heading">
  <div><h1>POS accounts</h1><p>Create cashier logins or reset/deactivate existing cashier accounts.</p></div>
</section>
<section class="pos-grid pos-grid-2" style="align-items:start">
  <article class="pos-card">
    <div class="pos-card-header"><h2>Add cashier</h2></div>
    <form method="post" class="pos-card-body pos-stack">
      <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
      <input type="hidden" name="action" value="create_cashier">
      <div><label class="pos-label" for="cashierName">Cashier name</label><input class="pos-input" id="cashierName" name="full_name" maxlength="180" required></div>
      <div><label class="pos-label" for="cashierUsername">Username</label><input class="pos-input" id="cashierUsername" name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9._-]+" autocomplete="off" required></div>
      <div><label class="pos-label" for="cashierPassword">Temporary password</label><input class="pos-input" id="cashierPassword" type="password" name="password" minlength="10" autocomplete="new-password" required><div class="pos-help">At least 10 characters. Share it securely with the cashier.</div></div>
      <button class="pos-button" type="submit">Create cashier</button>
    </form>
    <div class="pos-card-header"><h2>Change your password</h2></div>
    <form method="post" class="pos-card-body pos-stack">
      <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
      <input type="hidden" name="action" value="change_own_password">
      <div><label class="pos-label" for="currentPassword">Current password</label><input class="pos-input" id="currentPassword" type="password" name="current_password" autocomplete="current-password" required></div>
      <div><label class="pos-label" for="newOwnerPassword">New password</label><input class="pos-input" id="newOwnerPassword" type="password" name="new_password" minlength="10" autocomplete="new-password" required></div>
      <button class="pos-button pos-button-secondary" type="submit">Change password</button>
    </form>
  </article>
  <article class="pos-card">
    <div class="pos-card-header"><h2>Existing accounts</h2></div>
    <div class="pos-table-wrap">
      <table class="pos-table">
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($cashiers as $cashier): ?>
            <tr>
              <td><strong><?= pos_escape($cashier['full_name']); ?></strong>
                <?php if ($cashier['role'] === 'cashier'): ?>
                  <form method="post" class="pos-stack" style="margin-top:.65rem;gap:.45rem">
                    <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_cashier">
                    <input type="hidden" name="cashier_id" value="<?= (int)$cashier['id']; ?>">
                    <select class="pos-select" name="is_active" aria-label="Cashier status">
                      <option value="1" <?= (int)$cashier['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                      <option value="0" <?= (int)$cashier['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <input class="pos-input" type="password" name="new_password" minlength="10" autocomplete="new-password" placeholder="Optional new password">
                    <button class="pos-button pos-button-secondary" type="submit">Save account</button>
                  </form>
                <?php endif; ?>
              </td>
              <td><?= pos_escape($cashier['username']); ?></td>
              <td><?= pos_escape(ucfirst($cashier['role'])); ?></td>
              <td><span class="pos-badge <?= (int)$cashier['is_active'] ? '' : 'pos-badge-warning'; ?>"><?= (int)$cashier['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
<?php pos_footer(); ?>
