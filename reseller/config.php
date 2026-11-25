<?php
if (session_status() === PHP_SESSION_NONE) {
    $script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/reseller'));
    $reseller_marker = '/reseller';
    $reseller_position = strpos($script_path, $reseller_marker);
    $session_path = $reseller_position === false ? '/reseller' : substr($script_path, 0, $reseller_position + strlen($reseller_marker));
    session_name('AMARSHOP_RESELLER_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => rtrim($session_path, '/') . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/reseller.php';
ensure_reseller_tables($pdo);
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
