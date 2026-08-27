<?php
// config.php
session_start();

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
define('BASE_URL', '/amarshop');

function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/* ================= AUTH GUARD ================= */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url('auth/login.php'));
        exit;
    }
}

// config.php - এর নিচে যোগ করুন
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'developermdjasim@gmail.com');       // আপনার Gmail ইমেইল
define('SMTP_PASS', 'zdtq wxte duca xabi');          // Google App Password (১৬ ডিজিটের)
define('SMTP_FROM', 'amarshop@gmail.com');
define('SMTP_NAME', 'AmarShop');


// login from cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    
    // কুকির টোকেন দিয়ে ডাটাবেজে ইউজার খোঁজা
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = :token AND verify_status = 1 LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // অটো লগইন সেশন সেট
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['customar_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
    }
}