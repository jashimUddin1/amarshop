<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('AMARSHOP_DELIVERY_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/amarshop/delivery',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../admin/auth/config.php';
require_once __DIR__ . '/../core/delivery_logistics.php';
ensure_admin_account_tables($pdo);
ensure_delivery_logistics_tables($pdo);

function require_deliveryman(): array
{
    global $pdo;
    $deliveryman_id = filter_var($_SESSION['deliveryman_id'] ?? null, FILTER_VALIDATE_INT);
    if ($deliveryman_id === false || $deliveryman_id === null || $deliveryman_id <= 0) {
        header('Location: login.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, customar_name, phone, email FROM deliveryman WHERE id = :id AND status = 'active'");
    $stmt->execute([':id' => $deliveryman_id]);
    $deliveryman = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$deliveryman) {
        unset($_SESSION['deliveryman_id']);
        header('Location: login.php');
        exit;
    }
    return $deliveryman;
}
