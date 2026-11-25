<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('AMARSHOP_RESELLER_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/amarshop/reseller',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/reseller.php';
ensure_reseller_tables($pdo);
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
