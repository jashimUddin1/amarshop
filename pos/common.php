<?php
require_once __DIR__ . '/config.php';

function pos_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pos_csrf_token(): string
{
    if (!isset($_SESSION['offline_pos_csrf'])) {
        $_SESSION['offline_pos_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['offline_pos_csrf'];
}

function pos_verify_csrf(): void
{
    $submitted_token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expected_token = is_string($_SESSION['offline_pos_csrf'] ?? null) ? $_SESSION['offline_pos_csrf'] : '';
    if ($expected_token === '' || !hash_equals($expected_token, $submitted_token)) {
        http_response_code(400);
        exit('Request verification failed. Reload the page and try again.');
    }
}

function pos_admin_database(): PDO
{
    global $pdo;
    static $admin_database = null;
    if ($admin_database instanceof PDO) return $admin_database;
    $pos_database = $pdo;
    require_once __DIR__ . '/../admin/auth/config.php';
    $admin_database = $pdo;
    ensure_admin_account_tables($admin_database);
    $pdo = $pos_database;
    return $admin_database;
}

function pos_admin_account(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $admin_database = pos_admin_database();
    $stmt = $admin_database->prepare('SELECT id, customar_name, email, role FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        unset($_SESSION['user_id'], $_SESSION['admin_user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['email'], $_SESSION['admin_allowed_tabs']);
        return null;
    }
    $role = strtoupper(trim((string)$admin['role']));
    $custom_roles = $admin_database->query('SELECT role FROM admin_custom_roles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($role, array_merge(admin_default_staff_roles(), $custom_roles), true)) {
        unset($_SESSION['user_id'], $_SESSION['admin_user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['email'], $_SESSION['admin_allowed_tabs']);
        return null;
    }
    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = (string)$admin['customar_name'];
    $_SESSION['email'] = (string)$admin['email'];
    return $admin;
}

function pos_admin_cashier(array $admin): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, full_name, username, role FROM offline_pos_users WHERE admin_user_id = :admin_user_id LIMIT 1');
    $stmt->execute([':admin_user_id' => (int)$admin['id']]);
    $pos_user = $stmt->fetch();
    if (!$pos_user) {
        $username = 'admin-' . (int)$admin['id'] . '-' . bin2hex(random_bytes(6));
        $insert_stmt = $pdo->prepare("INSERT INTO offline_pos_users (full_name, username, password_hash, role, is_active, admin_user_id) VALUES (:name, :username, :password, 'cashier', 1, :admin_user_id)");
        try {
            $insert_stmt->execute([':name' => (string)$admin['customar_name'], ':username' => $username, ':password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), ':admin_user_id' => (int)$admin['id']]);
        } catch (PDOException $e) {
            $stmt->execute([':admin_user_id' => (int)$admin['id']]);
            $pos_user = $stmt->fetch();
            if (!$pos_user) throw $e;
        }
        if (!$pos_user) {
            $stmt->execute([':admin_user_id' => (int)$admin['id']]);
            $pos_user = $stmt->fetch();
        }
    }
    $pdo->prepare('UPDATE offline_pos_users SET is_active = 1 WHERE id = :id')->execute([':id' => (int)$pos_user['id']]);
    $pos_user['role'] = 'cashier';
    return $pos_user;
}

function pos_current_user(): ?array
{
    global $pdo;
    $admin = pos_admin_account();
    if ($admin) {
        $pos_user = pos_admin_cashier($admin);
        $_SESSION['offline_pos_user_id'] = (int)$pos_user['id'];
        return $pos_user;
    }
    if (empty($_SESSION['offline_pos_user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id, full_name, username, role, admin_user_id FROM offline_pos_users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['offline_pos_user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['offline_pos_user_id']);
        return null;
    }
    if ($user['admin_user_id'] !== null) {
        unset($_SESSION['offline_pos_user_id']);
        return null;
    }
    return $user;
}

function pos_login_with_admin(string $email, string $password): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') return false;
    $admin_database = pos_admin_database();
    $stmt = $admin_database->prepare('SELECT id, customar_name, email, password, role FROM admin_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, (string)$admin['password'])) return false;
    $role = strtoupper(trim((string)$admin['role']));
    $custom_roles = $admin_database->query('SELECT role FROM admin_custom_roles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($role, array_merge(admin_default_staff_roles(), $custom_roles), true)) return false;
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$admin['id'];
    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['email'] = (string)$admin['email'];
    $_SESSION['user_name'] = (string)$admin['customar_name'];
    $_SESSION['user_role'] = $role;
    $_SESSION['admin_allowed_tabs'] = admin_effective_tab_keys($admin_database, (int)$admin['id'], $role);
    $pos_user = pos_admin_cashier($admin);
    $_SESSION['offline_pos_user_id'] = (int)$pos_user['id'];
    $_SESSION['offline_pos_csrf'] = bin2hex(random_bytes(32));
    return true;
}

function pos_require_login(): array
{
    $user = pos_current_user();
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

function pos_flash(string $type, string $message): void
{
    $_SESSION['offline_pos_flash'] = ['type' => $type, 'message' => $message];
}

function pos_take_flash(): ?array
{
    $flash = $_SESSION['offline_pos_flash'] ?? null;
    unset($_SESSION['offline_pos_flash']);
    return is_array($flash) ? $flash : null;
}

function pos_header(string $title, string $active_page, array $user): void
{
    $flash = pos_take_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="color-scheme" content="light">
      <title><?= pos_escape($title); ?> · <?= pos_escape(POS_STORE_NAME); ?> POS</title>
      <style>
        :root { color-scheme: light; --ink:#17211b; --muted:#64736a; --line:#e2e9e4; --surface:#fff; --canvas:#f4f7f5; --green:#157347; --green-dark:#105a38; --green-soft:#e4f2e9; --red:#b42318; --amber:#916000; font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--canvas); color:var(--ink); }
        body.pos-drawer-open { overflow:hidden; }
        a { color:inherit; }
        button,input,select { font:inherit; }
        .pos-topbar { min-height:68px; display:flex; align-items:center; gap:1.5rem; padding:0 1.5rem; background:var(--surface); border-bottom:1px solid var(--line); }
        .pos-brand { font-size:1.1rem; font-weight:800; text-decoration:none; white-space:nowrap; }
        .pos-menu-toggle { display:none; }
        .pos-nav { display:flex; align-items:center; gap:.35rem; flex:1; }
        .pos-nav-header { display:none; }
        .pos-nav-links { display:contents; }
        .pos-nav a { padding:.55rem .8rem; border-radius:.55rem; text-decoration:none; color:var(--muted); font-weight:650; }
        .pos-nav a:hover,.pos-nav a.active { color:var(--green-dark); background:var(--green-soft); }
        .pos-user { display:flex; align-items:center; gap:.75rem; color:var(--muted); font-size:.9rem; }
        .pos-user form { margin:0; }
        .pos-main { max-width:1440px; margin:0 auto; padding:1.5rem; }
        .pos-page-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
        .pos-page-heading h1 { margin:0; font-size:clamp(1.45rem,3vw,2rem); letter-spacing:-.035em; }
        .pos-page-heading p { margin:.4rem 0 0; color:var(--muted); }
        .pos-card { background:var(--surface); border:1px solid var(--line); border-radius:.9rem; box-shadow:0 2px 6px rgba(20,40,28,.035); }
        .pos-card-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.15rem; border-bottom:1px solid var(--line); }
        .pos-card-header h2,.pos-card-header h3 { margin:0; font-size:1rem; }
        .pos-card-body { padding:1.15rem; }
        .pos-grid { display:grid; gap:1rem; }
        .pos-grid-4 { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .pos-grid-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .pos-metric { padding:1.1rem; }
        .pos-metric-label { color:var(--muted); font-size:.9rem; }
        .pos-metric-value { margin-top:.5rem; font-size:1.8rem; font-weight:800; letter-spacing:-.04em; }
        .pos-metric-note { margin-top:.25rem; color:var(--muted); font-size:.82rem; }
        .pos-button { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:40px; padding:.55rem .85rem; border:1px solid transparent; border-radius:.55rem; background:var(--green); color:#fff; font-weight:700; text-decoration:none; cursor:pointer; }
        .pos-button:hover { background:var(--green-dark); }
        .pos-button:disabled { opacity:.55; cursor:not-allowed; }
        .pos-button-secondary { background:#fff; color:var(--ink); border-color:var(--line); }
        .pos-button-secondary:hover { background:#f7faf8; }
        .pos-button-danger { background:#fff; color:var(--red); border-color:#f1cbc8; }
        .pos-button-danger:hover { background:#fff3f2; }
        .pos-input,.pos-select { width:100%; min-height:42px; padding:.6rem .7rem; border:1px solid #cbd6ce; border-radius:.55rem; background:#fff; color:var(--ink); }
        .pos-input:focus,.pos-select:focus { outline:3px solid #b7dec5; border-color:var(--green); }
        .pos-label { display:block; margin-bottom:.35rem; font-size:.86rem; font-weight:700; }
        .pos-help { color:var(--muted); font-size:.82rem; }
        .pos-form-row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.8rem; }
        .pos-alert { margin-bottom:1rem; padding:.8rem 1rem; border:1px solid var(--line); border-radius:.65rem; background:#fff; }
        .pos-alert-success { color:#155e37; background:#e8f5ed; border-color:#cbe8d5; }
        .pos-alert-error { color:#8d1d16; background:#fff0ef; border-color:#f1cbc8; }
        .pos-table-wrap { overflow-x:auto; }
        .pos-table { width:100%; border-collapse:collapse; }
        .pos-table th,.pos-table td { padding:.8rem 1rem; border-bottom:1px solid var(--line); text-align:left; vertical-align:middle; }
        .pos-table th { background:#f8faf8; color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
        .pos-table tbody tr:last-child td { border-bottom:0; }
        .pos-table .right { text-align:right; }
        .pos-badge { display:inline-flex; padding:.25rem .55rem; border-radius:999px; background:var(--green-soft); color:var(--green-dark); font-size:.8rem; font-weight:700; white-space:nowrap; }
        .pos-badge-warning { color:var(--amber); background:#fff4d6; }
        .pos-empty { padding:2rem 1rem; text-align:center; color:var(--muted); }
        .pos-muted { color:var(--muted); }
        .pos-inline { display:inline; }
        .pos-summary { display:flex; justify-content:space-between; gap:1rem; padding:.45rem 0; }
        .pos-summary-total { margin-top:.4rem; padding-top:.8rem; border-top:1px solid var(--line); font-size:1.2rem; font-weight:800; }
        .pos-stack { display:grid; gap:1rem; }
        .pos-actions { display:flex; flex-wrap:wrap; gap:.6rem; }
        @media (max-width:900px) { .pos-grid-4 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:640px) {
          .pos-topbar { position:relative; flex-wrap:nowrap; gap:.75rem; min-height:64px; padding:.65rem 1rem; }
          .pos-menu-toggle { display:inline-flex; align-items:center; justify-content:center; flex:0 0 42px; width:42px; height:42px; padding:0; border:0; border-radius:.35rem; background:transparent; color:#718078; cursor:pointer; -webkit-tap-highlight-color:transparent; }
          .pos-menu-toggle:hover,.pos-menu-toggle:focus-visible { background:#f0f4f1; outline:2px solid #b7dec5; outline-offset:2px; }
          .pos-menu-icon { display:grid; gap:5px; width:23px; }
          .pos-menu-icon span { display:block; width:23px; height:1.5px; border-radius:2px; background:currentColor; transition:transform .22s ease,opacity .18s ease; }
          .pos-menu-toggle[aria-expanded="true"] .pos-menu-icon span:first-child { transform:translateY(6.5px) rotate(45deg); }
          .pos-menu-toggle[aria-expanded="true"] .pos-menu-icon span:nth-child(2) { opacity:0; }
          .pos-menu-toggle[aria-expanded="true"] .pos-menu-icon span:last-child { transform:translateY(-6.5px) rotate(-45deg); }
          .pos-nav-backdrop { position:fixed; z-index:99; inset:0; background:rgba(15,24,19,.48); opacity:0; visibility:hidden; transition:opacity .25s ease,visibility .25s ease; }
          .pos-nav-backdrop.is-visible { opacity:1; visibility:visible; }
          .pos-nav { position:fixed; z-index:100; inset:0 auto 0 0; display:flex; width:min(82vw,320px); height:100vh; height:100dvh; padding:0; flex-direction:column; align-items:stretch; gap:0; overflow-y:auto; background:var(--surface); box-shadow:12px 0 36px rgba(13,30,20,.18); transform:translateX(-105%); transition:transform .28s cubic-bezier(.22,.61,.36,1); }
          .pos-nav.is-open { display:flex; transform:translateX(0); }
          .pos-nav-header { display:flex; align-items:center; justify-content:space-between; min-height:76px; padding:1rem 1.15rem; border-bottom:1px solid var(--line); }
          .pos-nav-brand { color:var(--green-dark); font-size:1.05rem; font-weight:800; letter-spacing:-.02em; }
          .pos-nav-brand small { display:block; margin-top:.15rem; color:var(--muted); font-size:.72rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; }
          .pos-nav-close { display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border:1px solid var(--line); border-radius:50%; background:#fff; color:var(--muted); font-size:1.35rem; line-height:1; cursor:pointer; }
          .pos-nav-links { display:grid; gap:.35rem; padding:1rem .75rem; }
          .pos-nav a { display:flex; align-items:center; min-height:48px; padding:.75rem .9rem; border-radius:.7rem; color:#47564c; }
          .pos-nav a:hover,.pos-nav a.active { color:var(--green-dark); background:var(--green-soft); }
          .pos-nav a.active { font-weight:750; }
          .pos-user { margin-left:auto; gap:.45rem; font-size:.82rem; }
          .pos-user > span { max-width:90px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
          .pos-user .pos-button { min-height:38px; padding:.45rem .6rem; }
          .pos-main { padding:1rem; }
          .pos-grid-2,.pos-form-row { grid-template-columns:1fr; }
          .pos-page-heading { flex-direction:column; }
        }
        @media (prefers-reduced-motion:reduce) { .pos-nav,.pos-nav-backdrop,.pos-menu-icon span { transition:none !important; } }
      </style>
    </head>
    <body>
      <header class="pos-topbar">
        <!-- <a class="pos-brand" href="dashboard.php"><?= pos_escape(POS_STORE_NAME); ?><span class="pos-muted"> POS</span></a> -->
        <button class="pos-menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="posNavigation">
          <span class="pos-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
        <nav class="pos-nav" id="posNavigation" aria-label="Point of sale navigation" aria-hidden="true">
          <div class="pos-nav-header">
            <div class="pos-nav-brand"><?= pos_escape(POS_STORE_NAME); ?><small>Point of sale</small></div>
            <button class="pos-nav-close" type="button" aria-label="Close navigation menu">&times;</button>
          </div>
          <div class="pos-nav-links">
            <a class="<?= $active_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">Overview</a>
            <a class="<?= $active_page === 'sale' ? 'active' : ''; ?>" href="sale.php">New bill</a>
            <a class="<?= $active_page === 'sales' ? 'active' : ''; ?>" href="sales.php">Sales history</a>
            <?php if ($user['role'] === 'owner'): ?><a class="<?= $active_page === 'users' ? 'active' : ''; ?>" href="users.php">Cashiers</a><?php endif; ?>
            <a href="../admin/index.php">Admin</a>
          </div>
        </nav>
        <div class="pos-nav-backdrop" data-pos-menu-dismiss></div>
        <div class="pos-user">
          <span><?= pos_escape($user['full_name']); ?></span>
          <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()); ?>">
            <button type="submit" class="pos-button pos-button-secondary">Log out</button>
          </form>
        </div>
      </header>
      <main class="pos-main">
        <?php if ($flash): ?>
          <div class="pos-alert <?= $flash['type'] === 'success' ? 'pos-alert-success' : 'pos-alert-error'; ?>" role="status"><?= pos_escape($flash['message']); ?></div>
        <?php endif; ?>
    <?php
}

function pos_footer(): void
{
    ?>
      </main>
      <script>
        (() => {
          const toggle = document.querySelector('.pos-menu-toggle');
          const nav = document.getElementById('posNavigation');
          const backdrop = document.querySelector('.pos-nav-backdrop');
          const closeButton = document.querySelector('.pos-nav-close');
          if (!toggle || !nav || !backdrop) return;
          const isMobile = () => window.matchMedia('(max-width: 640px)').matches;
          const setMenuOpen = (open) => {
            const shouldOpen = open && isMobile();
            nav.classList.toggle('is-open', shouldOpen);
            backdrop.classList.toggle('is-visible', shouldOpen);
            document.body.classList.toggle('pos-drawer-open', shouldOpen);
            toggle.setAttribute('aria-expanded', String(shouldOpen));
            toggle.setAttribute('aria-label', shouldOpen ? 'Close navigation menu' : 'Open navigation menu');
            nav.setAttribute('aria-hidden', String(isMobile() && !shouldOpen));
          };
          toggle.addEventListener('click', () => setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true'));
          closeButton?.addEventListener('click', () => { setMenuOpen(false); toggle.focus(); });
          backdrop.addEventListener('click', () => { setMenuOpen(false); toggle.focus(); });
          nav.addEventListener('click', (event) => { if (event.target.closest('a')) setMenuOpen(false); });
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { setMenuOpen(false); toggle.focus(); }
          });
          window.addEventListener('resize', () => setMenuOpen(false));
          setMenuOpen(false);
        })();
      </script>
    </body>
    </html>
    <?php
}