<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_name('AMARSHOP_ADMIN_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/amarshop/admin',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ================= TIMEZONE ================= */
date_default_timezone_set('Asia/Dhaka');

$host = "localhost";
$db   = "amarshop";
$user = "root";
$pass = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


/* ================= BASE URL ================= */
define('BASE_URL', '/amarshop/admin');

function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/* ================= AUTH GUARD ================= */
function require_admin(): void {
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url('auth/login.php'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT customar_name, email, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $role = strtolower((string)($admin['role'] ?? ''));

    if (!in_array($role, ['admin', 'manager'], true)) {
        $_SESSION = [];
        session_destroy();
        http_response_code(403);
        exit('Access denied.');
    }

    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $admin['customar_name'];
    $_SESSION['email'] = $admin['email'];
}