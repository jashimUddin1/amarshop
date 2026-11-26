<?php
require_once __DIR__ . '/common.php';

if (pos_current_user()) {
    header('Location: dashboard.php');
    exit;
}

$is_first_run = (int)$pdo->query('SELECT is_complete FROM offline_pos_installation WHERE id = 1')->fetchColumn() === 0;
$setup_available = $is_first_run && POS_SETUP_KEY !== '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pos_verify_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $username = trim((string)($_POST['username'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($action === 'setup' && $setup_available) {
        $full_name = trim((string)($_POST['full_name'] ?? ''));
        $setup_key = is_string($_POST['setup_key'] ?? null) ? $_POST['setup_key'] : '';
        if (!hash_equals(POS_SETUP_KEY, $setup_key)) {
            $error = 'The setup key is incorrect.';
        } elseif ($full_name === '' || strlen($full_name) > 180 ||
            !preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username) || strlen($password) < 10) {
            $error = 'Enter a name, username (3-80 letters/numbers) and a password of at least 10 characters.';
        } else {
            try {
                $pdo->beginTransaction();
                $setup_stmt = $pdo->query('SELECT is_complete FROM offline_pos_installation WHERE id = 1 FOR UPDATE');
                if ((int)$setup_stmt->fetchColumn() !== 0) {
                    throw new RuntimeException('POS setup has already been completed.');
                }
                $stmt = $pdo->prepare("
                    INSERT INTO offline_pos_users (full_name, username, password_hash, role)
                    VALUES (:name, :username, :password, 'owner')
                ");
                $stmt->execute([
                    ':name' => $full_name,
                    ':username' => $username,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $user_id = (int)$pdo->lastInsertId();
                $pdo->exec('UPDATE offline_pos_installation SET is_complete = 1 WHERE id = 1');
                $pdo->commit();
                session_regenerate_id(true);
                $_SESSION['offline_pos_user_id'] = $user_id;
                $_SESSION['offline_pos_csrf'] = bin2hex(random_bytes(32));
                header('Location: dashboard.php');
                exit;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Offline POS owner setup failed: ' . $exception->getMessage());
                $error = 'The owner account could not be created. Check that the username is unique and try again.';
            }
        }
    } elseif ($action === 'login') {
        $locked_until = (int)($_SESSION['offline_pos_login_locked_until'] ?? 0);
        if ($locked_until > time()) {
            $error = 'Too many sign-in attempts. Try again in a few minutes.';
        } elseif ($username === '' || strlen($username) > 191 || $password === '') {
            $error = 'Username or password is incorrect.';
        } else {
            $user = false;
            if (preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
                $stmt = $pdo->prepare('
                    SELECT id, password_hash
                    FROM offline_pos_users
                    WHERE username = :username AND is_active = 1 AND admin_user_id IS NULL
                    LIMIT 1
                ');
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();
            }
            if ($user && password_verify($password, (string)$user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['offline_pos_user_id'] = (int)$user['id'];
                $_SESSION['offline_pos_csrf'] = bin2hex(random_bytes(32));
                unset($_SESSION['offline_pos_login_failures'], $_SESSION['offline_pos_login_locked_until']);
                header('Location: dashboard.php');
                exit;
            } elseif (pos_login_with_admin($username, $password)) {
                unset($_SESSION['offline_pos_login_failures'], $_SESSION['offline_pos_login_locked_until']);
                header('Location: dashboard.php');
                exit;
            }
            $failures = (int)($_SESSION['offline_pos_login_failures'] ?? 0) + 1;
            $_SESSION['offline_pos_login_failures'] = $failures;
            if ($failures >= 5) {
                $_SESSION['offline_pos_login_locked_until'] = time() + 300;
                $_SESSION['offline_pos_login_failures'] = 0;
                $error = 'Too many sign-in attempts. Try again in a few minutes.';
            } else {
                $error = 'Username or password is incorrect.';
            }
        }
    } else {
        $error = 'This request is not available.';
    }
}

$page_heading = $is_first_run && $setup_available ? 'Set up your counter' : 'Sign in to your counter';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= pos_escape($page_heading); ?> · <?= pos_escape(POS_STORE_NAME); ?> POS</title>
  <style>
    * { box-sizing: border-box; }
    body { min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 1.25rem; background: #f3f7f4; color: #17211b; font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
    .login-card { width: min(100%, 440px); padding: clamp(1.5rem, 5vw, 2.5rem); background: white; border: 1px solid #e0e9e3; border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(20, 50, 30, .08); }
    .brand { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.75rem; color: #145a37; font-weight: 800; font-size: 1.1rem; }
    .brand-mark { width: 2.5rem; height: 2.5rem; display: grid; place-items: center; border-radius: .75rem; background: #e4f2e9; font-size: 1.3rem; }
    h1 { margin: 0; font-size: 1.8rem; letter-spacing: -.04em; }
    p { margin: .5rem 0 1.5rem; color: #64736a; line-height: 1.5; }
    label { display: block; margin: 1rem 0 .4rem; font-size: .9rem; font-weight: 700; }
    input { width: 100%; min-height: 46px; padding: .65rem .75rem; border: 1px solid #cbd6ce; border-radius: .55rem; font: inherit; }
    input:focus { outline: 3px solid #b7dec5; border-color: #157347; }
    button { width: 100%; min-height: 46px; margin-top: 1.3rem; border: 0; border-radius: .55rem; background: #157347; color: white; font: inherit; font-weight: 750; cursor: pointer; }
    button:hover { background: #105a38; }
    .error { padding: .75rem; border-radius: .5rem; background: #fff0ef; color: #8d1d16; font-size: .9rem; }
    .note { margin-top: 1rem; font-size: .82rem; }
  </style>
</head>
<body>
  <main class="login-card">
    <div class="brand"><span class="brand-mark" aria-hidden="true">▦</span><span><?= pos_escape(POS_STORE_NAME); ?> · Counter POS</span></div>
    <h1><?= pos_escape($page_heading); ?></h1>
    <?php if ($is_first_run && $setup_available): ?>
      <p>Create the owner account to begin. This one-time setup is locked after the account is created.</p>
    <?php elseif ($is_first_run): ?>
      <p>Owner setup is protected. Set <code>POS_SETUP_KEY</code> in this folder's <code>config.php</code>, then reload this page.</p>
    <?php else: ?>
      <p>Use your POS username or Admin email and password. Admin sign-in works in both panels.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?= pos_escape($error); ?></div><?php endif; ?>
    <?php if (!$is_first_run || $setup_available): ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
        <input type="hidden" name="action" value="<?= $setup_available ? 'setup' : 'login'; ?>">
        <?php if ($setup_available): ?>
          <label for="setupKey">One-time setup key</label>
          <input id="setupKey" name="setup_key" type="password" required autocomplete="off">
          <label for="fullName">Owner name</label>
          <input id="fullName" name="full_name" maxlength="180" required autocomplete="name">
        <?php endif; ?>
        <label for="username"><?= $setup_available ? 'POS username' : 'POS username or Admin email'; ?></label>
        <input id="username" name="username" maxlength="<?= $setup_available ? '80' : '191'; ?>" required autocomplete="username" autofocus>
        <label for="password">Password<?= $setup_available ? ' (at least 10 characters)' : ''; ?></label>
        <input id="password" name="password" type="password" minlength="<?= $setup_available ? '10' : '1'; ?>" required autocomplete="<?= $setup_available ? 'new-password' : 'current-password'; ?>">
        <button type="submit"><?= $setup_available ? 'Create owner account' : 'Sign in'; ?></button>
      </form>
    <?php endif; ?>
    <?php if ($is_first_run): ?>
      <hr>
      <p class="note">Already have an Admin account? Sign in here to open POS and Admin with the same login.</p>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
        <input type="hidden" name="action" value="login">
        <label for="adminEmail">Admin email</label>
        <input id="adminEmail" name="username" type="email" maxlength="191" required autocomplete="username">
        <label for="adminPassword">Admin password</label>
        <input id="adminPassword" name="password" type="password" required autocomplete="current-password">
        <button type="submit">Sign in with Admin account</button>
      </form>
    <?php endif; ?>
    <p class="note">Your store inventory stays in the configured shared products database. Counter bills are recorded separately.</p>
  </main>
</body>
</html>
