<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    $script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/admin'));
    $admin_marker = '/admin';
    $admin_position = strpos($script_path, $admin_marker);
    $session_path = $admin_position === false ? '/admin' : substr($script_path, 0, $admin_position + strlen($admin_marker));
    session_name('AMARSHOP_ADMIN_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => rtrim($session_path, '/') . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
}

function admin_staff_roles(): array
{
    return ['CEO', 'MD', 'HR ADMIN', 'MANAGER', 'ASS. MANAGER', 'DATA ENTRY OPERATOR', 'SALESMAN'];
}

function admin_assignable_roles(string $role): array
{
    $role = strtoupper(trim($role));
    $role = match ($role) {
        'HR ADMINISTRATOR' => 'HR ADMIN',
        default => $role,
    };
    $roles = admin_staff_roles();
    $role_index = array_search($role, $roles, true);
    if ($role_index === false || $role === 'SALESMAN' || $role === 'DATA ENTRY OPERATOR') {
        return [];
    }

    return match ($role) {
        'CEO' => array_slice($roles, 1),
        'MD' => array_slice($roles, 2),
        'HR ADMIN', 'MANAGER' => array_slice($roles, 4),
        'ASS. MANAGER' => array_slice($roles, 5),
        default => [],
    };
}

function admin_can_manage_staff(string $role): bool
{
    return in_array(strtoupper(trim($role)), ['CEO', 'MD', 'HR ADMIN', 'MANAGER', 'ASS. MANAGER'], true);
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
}