<?php
require_once __DIR__ . '/../config.php';

$reseller = require_reseller($pdo);
$reseller_id = (int)$reseller['id'];
$csrf_token = $_SESSION['reseller_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['reseller_csrf'] = $csrf_token;
$notice = is_array($_SESSION['reseller_flash'] ?? null) ? $_SESSION['reseller_flash'] : null;
unset($_SESSION['reseller_flash']);

function reseller_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reseller_money($value): string
{
    return '৳' . number_format((float)$value, 2);
}

function reseller_cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['reseller_cart'] ?? [] as $variants) {
        if (!is_array($variants)) {
            continue;
        }
        foreach ($variants as $item) {
            if (is_array($item)) {
                $count += max(0, (int)($item['quantity'] ?? 0));
            }
        }
    }
    return $count;
}
