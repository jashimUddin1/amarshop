<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    $session_secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('AMARSHOP_ADMIN_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $session_secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ================= TIMEZONE ================= */
date_default_timezone_set('Asia/Dhaka');

$host = getenv('AMARSHOP_DB_HOST') ?: 'localhost';
$db   = getenv('AMARSHOP_DB_NAME') ?: 'amarshop';
$user = getenv('AMARSHOP_DB_USER') ?: 'root';
$pass = getenv('AMARSHOP_DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


/* ================= BASE URL ================= */
$script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
$admin_marker = '/admin';
$admin_position = strpos($script_path, $admin_marker);
$admin_base = $admin_position === false ? '/admin' : substr($script_path, 0, $admin_position + strlen($admin_marker));
define('BASE_URL', rtrim($admin_base, '/') ?: '/admin');

function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }
}

function ensure_admin_account_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customar_name VARCHAR(180) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(40) NOT NULL DEFAULT '',
            password VARCHAR(255) NOT NULL,
            role VARCHAR(40) NOT NULL,
            verify_status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_admin_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deliveryman (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customar_name VARCHAR(180) NOT NULL,
            phone VARCHAR(40) NOT NULL DEFAULT '',
            email VARCHAR(191) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_deliveryman_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        INSERT IGNORE INTO admin_users
            (id, customar_name, email, phone, password, role, verify_status, created_at)
        SELECT id, customar_name, email, COALESCE(phone, ''), password,
            CASE LOWER(role)
                WHEN 'admin' THEN 'CEO'
                WHEN 'manager' THEN 'MANAGER'
                WHEN 'hr admin' THEN 'HR ADMIN'
                WHEN 'hr administrator' THEN 'HR ADMIN'
                WHEN 'assistant manager' THEN 'ASS. MANAGER'
                WHEN 'asst. manager' THEN 'ASS. MANAGER'
                WHEN 'ass. manager' THEN 'ASS. MANAGER'
                WHEN 'data entry operator' THEN 'DATA ENTRY OPERATOR'
                ELSE UPPER(role)
            END,
            COALESCE(verify_status, 1), COALESCE(created_at, CURRENT_TIMESTAMP)
        FROM users
        WHERE email IS NOT NULL AND email <> ''
            AND LOWER(role) IN ('admin', 'manager', 'ceo', 'md', 'hr admin', 'hr administrator', 'assistant manager', 'asst. manager', 'ass. manager', 'data entry operator', 'salesman')
    ");

    ensure_admin_permission_tables($pdo);
}

function admin_default_staff_roles(): array
{
    return ['CEO', 'MD', 'HR ADMIN', 'MANAGER', 'ASS. MANAGER', 'DATA ENTRY OPERATOR', 'SALESMAN'];
}

function admin_custom_staff_roles(): array
{
    global $pdo;

    $stmt = $pdo->query('SELECT role FROM admin_custom_roles ORDER BY role');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function admin_staff_roles(): array
{
    return array_values(array_unique(array_merge(admin_default_staff_roles(), admin_custom_staff_roles())));
}

function admin_permission_tabs(): array
{
    return [
        'dashboard' => 'Dashboard',
        'process' => 'Process',
        'orders' => 'Orders',
        'assign' => 'Assign',
        'order-requests' => 'Requests',
        'order-issues' => 'Order Issues',
        'cash-collections' => 'Cash Collection',
        'collector-manage' => 'Collector Manage',
        'resellers' => 'Resellers',
        'purchases' => 'Purchases',
        'products' => 'Products',
        'category' => 'Categories',
        'collection' => 'Collections',
        'users' => 'Users',
        'admin-users' => 'Admin Staff',
        'deliverymen' => 'Deliverymen',
        'setting' => 'Settings',
    ];
}

function ensure_admin_permission_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_custom_roles (
            role VARCHAR(40) NOT NULL,
            created_by_admin_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_role_permission_settings (
            role VARCHAR(40) NOT NULL,
            configured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_role_tab_permissions (
            role VARCHAR(40) NOT NULL,
            tab_key VARCHAR(40) NOT NULL,
            PRIMARY KEY (role, tab_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_user_permission_settings (
            admin_user_id BIGINT UNSIGNED NOT NULL,
            custom_access TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (admin_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_user_tab_permissions (
            admin_user_id BIGINT UNSIGNED NOT NULL,
            tab_key VARCHAR(40) NOT NULL,
            PRIMARY KEY (admin_user_id, tab_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $owns_transaction = !$pdo->inTransaction();
    if ($owns_transaction) {
        $pdo->beginTransaction();
    }
    try {
        $seed = $pdo->prepare('INSERT IGNORE INTO admin_role_permission_settings (role) VALUES (:role)');
        $grant = $pdo->prepare('INSERT IGNORE INTO admin_role_tab_permissions (role, tab_key) VALUES (:role, :tab_key)');
        foreach (admin_staff_roles() as $role) {
            $seed->execute([':role' => $role]);
            if ($seed->rowCount() === 1) {
                foreach (array_keys(admin_permission_tabs()) as $tab_key) {
                    $grant->execute([':role' => $role, ':tab_key' => $tab_key]);
                }
            }
        }
        if ($owns_transaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function admin_all_tab_keys(): array
{
    return array_merge(array_keys(admin_permission_tabs()), ['role-management', 'profile']);
}

function admin_assignable_roles(string $role): array
{
    $role = strtoupper(trim($role));
    $role = match ($role) {
        'HR ADMINISTRATOR' => 'HR ADMIN',
        default => $role,
    };
    $default_roles = admin_default_staff_roles();
    $role_index = array_search($role, $default_roles, true);
    if ($role_index === false && !in_array($role, admin_custom_staff_roles(), true)) {
        return [];
    }

    return match ($role) {
        'CEO' => array_merge(array_slice($default_roles, 1), admin_custom_staff_roles()),
        'MD' => array_merge(array_slice($default_roles, 2), admin_custom_staff_roles()),
        'HR ADMIN', 'MANAGER' => array_slice($default_roles, 4),
        'ASS. MANAGER' => array_slice($default_roles, 5),
        default => [],
    };
}

function admin_can_manage_staff(string $role): bool
{
    return in_array(strtoupper(trim($role)), ['CEO', 'MD', 'HR ADMIN', 'MANAGER', 'ASS. MANAGER'], true);
}

function admin_can_manage_role_permissions(string $role): bool
{
    return in_array(strtoupper(trim($role)), ['CEO', 'MD'], true);
}

function admin_request_tab_key(): ?string
{
    $script_path = str_replace('\\', '/', (string)parse_url((string)($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH));
    $script = basename($script_path);
    $parent = basename(dirname($script_path));

    $tabs_by_script = [
        'index.php' => 'dashboard',
        'process.php' => 'process',
        'sales.php' => 'orders',
        'order_details.php' => 'orders',
        'sale_update_status.php' => 'orders',
        'sale_update_status_bulk.php' => 'orders',
        'save_order_edits.php' => 'orders',
        'search_order_products.php' => 'orders',
        'create_order_issue.php' => 'orders',
        'assign.php' => 'assign',
        'bag_details.php' => 'assign',
        'save_delivery_assignment.php' => 'assign',
        'order_requests.php' => 'order-requests',
        'resolve_order_request.php' => 'order-requests',
        'add_order_request_message.php' => 'order-requests',
        'order_issues.php' => 'order-issues',
        'resolve_order_issue.php' => 'order-issues',
        'save_order_issue_assignment.php' => 'order-issues',
        'update_order_issue_shipment.php' => 'order-issues',
        'cash_collections.php' => 'cash-collections',
        'collector_manage.php' => 'collector-manage',
        'resellers.php' => 'resellers',
        'purchases.php' => 'purchases',
        'purchase_details.php' => 'purchases',
        'products.php' => 'products',
        'product_add_core.php' => 'products',
        'product_edit_core.php' => 'products',
        'product_delete_core.php' => 'products',
        'categories.php' => 'category',
        'category_add.php' => 'category',
        'category_edit.php' => 'category',
        'category_delete.php' => 'category',
        'collection.php' => 'collection',
        'collection_add.php' => 'collection',
        'collection_edit.php' => 'collection',
        'collection_delete.php' => 'collection',
        'users.php' => 'users',
        'admin_users.php' => 'admin-users',
        'signup.php' => $parent === 'auth' ? 'admin-users' : null,
        'signup_core.php' => $parent === 'auth' ? 'admin-users' : null,
        'role_management.php' => 'role-management',
        'admin_staff_permissions.php' => 'role-management',
        'deliverymen.php' => 'deliverymen',
        'deliveryman_details.php' => 'deliverymen',
        'setting.php' => 'setting',
        'setting_update_core.php' => 'setting',
        'site_settings.php' => 'setting',
        'profile.php' => 'profile',
    ];

    return $tabs_by_script[$script] ?? null;
}

function admin_can_access_tab(string $tab_key): bool
{
    $role = strtoupper(trim((string)($_SESSION['user_role'] ?? '')));
    if ($tab_key === 'role-management') {
        return admin_can_manage_role_permissions($role);
    }
    if ($tab_key === 'admin-users' && !admin_can_manage_staff($role)) {
        return false;
    }
    if (admin_can_manage_role_permissions($role)) {
        return in_array($tab_key, admin_all_tab_keys(), true);
    }
    $allowed_tabs = $_SESSION['admin_allowed_tabs'] ?? [];
    return is_array($allowed_tabs) && in_array($tab_key, $allowed_tabs, true);
}

function admin_effective_tab_keys(PDO $pdo, int $user_id, string $role): array
{
    $role = strtoupper(trim($role));
    if (admin_can_manage_role_permissions($role)) {
        return admin_all_tab_keys();
    }

    $custom_stmt = $pdo->prepare('SELECT custom_access FROM admin_user_permission_settings WHERE admin_user_id = :id LIMIT 1');
    $custom_stmt->execute([':id' => $user_id]);
    $custom_access = $custom_stmt->fetchColumn();
    if ($custom_access !== false && (int)$custom_access === 1) {
        $permission_stmt = $pdo->prepare('SELECT tab_key FROM admin_user_tab_permissions WHERE admin_user_id = :id');
        $permission_stmt->execute([':id' => $user_id]);
    } else {
        $permission_stmt = $pdo->prepare('SELECT tab_key FROM admin_role_tab_permissions WHERE role = :role');
        $permission_stmt->execute([':role' => $role]);
    }

    $allowed_tabs = array_values(array_unique(array_merge(['profile'], $permission_stmt->fetchAll(PDO::FETCH_COLUMN))));
    if (!admin_can_manage_staff($role)) {
        $allowed_tabs = array_values(array_diff($allowed_tabs, ['admin-users']));
    }
    return $allowed_tabs;
}

function admin_landing_page(array $allowed_tabs): string
{
    $pages = [
        'dashboard' => 'index.php',
        'process' => 'process.php',
        'orders' => 'sales.php',
        'assign' => 'assign.php',
        'order-requests' => 'order_requests.php',
        'order-issues' => 'order_issues.php',
        'cash-collections' => 'cash_collections.php',
        'collector-manage' => 'collector_manage.php',
        'resellers' => 'resellers.php',
        'purchases' => 'purchases.php',
        'products' => 'products.php',
        'category' => 'categories.php',
        'collection' => 'collection.php',
        'users' => 'users.php',
        'admin-users' => 'admin_users.php',
        'role-management' => 'role_management.php',
        'deliverymen' => 'deliverymen.php',
        'setting' => 'setting.php',
        'profile' => 'profile.php',
    ];
    foreach ($pages as $tab_key => $page) {
        if (in_array($tab_key, $allowed_tabs, true)) {
            return $page;
        }
    }
    return 'profile.php';
}

/* ================= AUTH GUARD ================= */
function require_admin(): void {
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url('auth/login.php'));
        exit;
    }

    ensure_admin_account_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, customar_name, email, role FROM admin_users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $role = strtoupper(trim((string)($admin['role'] ?? '')));

    if (!in_array($role, admin_staff_roles(), true)) {
        $_SESSION = [];
        session_destroy();
        http_response_code(403);
        exit('Access denied.');
    }

    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $admin['customar_name'];
    $_SESSION['email'] = $admin['email'];

    $_SESSION['admin_allowed_tabs'] = admin_effective_tab_keys($pdo, (int)$admin['id'], $role);

    $requested_tab = admin_request_tab_key();
    if ($requested_tab !== null && !admin_can_access_tab($requested_tab)) {
        if ($requested_tab === 'dashboard') {
            $landing_page = admin_landing_page($_SESSION['admin_allowed_tabs']);
            if ($landing_page !== 'index.php') {
                header('Location: ' . base_url($landing_page));
                exit;
            }
        }
        http_response_code(403);
        exit('You do not have permission to access this admin tab.');
    }
}