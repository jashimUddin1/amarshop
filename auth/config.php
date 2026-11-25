<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    $script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
    $session_path = '/';
    foreach (['/auth/', '/core/', '/user/', '/checkout.php', '/cart.php', '/index.php'] as $marker) {
        $position = strpos($script_path, $marker);
        if ($position !== false) {
            $session_path = substr($script_path, 0, $position + 1);
            if ($session_path === '') {
                $session_path = '/';
            }
            break;
        }
    }

    session_name('AMARSHOP_CUSTOMER_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $session_path,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
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
$document_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
$project_root = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
$base_url = '/';
if ($document_root !== '' && str_starts_with($project_root, rtrim($document_root, '/') . '/')) {
    $base_url = substr($project_root, strlen(rtrim($document_root, '/')));
    $base_url = '/' . trim($base_url, '/');
    if ($base_url === '/') {
        $base_url = '';
    }
}
define('BASE_URL', $base_url);

function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid or missing CSRF token.');
    }
}

function remember_token_hash(string $token): string
{
    return hash('sha256', $token);
}

/* ================= AUTH GUARD ================= */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url('auth/login.php'));
        exit;
    }
}

// login from cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $token_hash = remember_token_hash($token);
    
    // কুকির টোকেন দিয়ে ডাটাবেজে ইউজার খোঁজা
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE remember_token = :token AND verify_status = 1 AND email_verified = 1
            AND LOWER(COALESCE(role, 'user')) NOT IN ('admin', 'manager', 'ceo', 'md', 'assistant manager', 'asst. manager', 'ass. manager', 'salesman')
        LIMIT 1
    ");
    $stmt->execute([':token' => $token_hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // অটো লগইন সেশন সেট
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['customar_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
    }
}