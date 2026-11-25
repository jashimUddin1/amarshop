<?php

function reseller_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $pdo->quote($column));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' .
            str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function ensure_reseller_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reseller_accounts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(180) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            email VARCHAR(191) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            referral_code VARCHAR(24) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            rejection_reason TEXT NULL,
            reviewed_by_admin_id BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reseller_email (email),
            UNIQUE KEY uq_reseller_phone (phone),
            UNIQUE KEY uq_reseller_referral (referral_code),
            KEY idx_reseller_status (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reseller_commissions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reseller_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            order_amount DECIMAL(12,2) NOT NULL,
            commission_amount DECIMAL(12,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reseller_commission_order (order_id),
            KEY idx_reseller_commissions (reseller_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    foreach ([
        'reseller_markup_percent' => 'DECIMAL(5,2) NULL DEFAULT NULL AFTER purchase_price',
        'sell_markup_percent' => 'DECIMAL(5,2) NULL DEFAULT NULL AFTER reseller_markup_percent',
        'reseller_price' => 'DECIMAL(10,2) NULL DEFAULT NULL AFTER sell_price',
    ] as $column => $definition) {
        reseller_ensure_column($pdo, 'products', $column, $definition);
    }
    $pdo->exec("
        UPDATE products
        SET reseller_markup_percent = COALESCE(reseller_markup_percent, 0),
            sell_markup_percent = COALESCE(sell_markup_percent,
                CASE WHEN purchase_price > 0
                    THEN GREATEST(0, ((sell_price - purchase_price) / purchase_price) * 100)
                    ELSE 0
                END),
            reseller_price = COALESCE(reseller_price, purchase_price)
        WHERE reseller_markup_percent IS NULL OR sell_markup_percent IS NULL OR reseller_price IS NULL
    ");
    reseller_ensure_column($pdo, 'orders', 'reseller_id', 'BIGINT UNSIGNED NULL AFTER user_id');
    reseller_ensure_column($pdo, 'order_items', 'reseller_price', 'DECIMAL(10,2) NULL AFTER price');
    $index = $pdo->query("SHOW INDEX FROM orders WHERE Key_name = 'idx_orders_reseller'");
    if (!$index->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec('CREATE INDEX idx_orders_reseller ON orders (reseller_id, created_at)');
    }
}

function reseller_calculate_prices(
    float $purchase_price,
    float $reseller_markup_percent,
    float $sell_markup_percent,
    float $discount_percent
): array {
    if ($purchase_price < 0 || $purchase_price > 99999999.99 ||
        $reseller_markup_percent < 0 || $reseller_markup_percent > 999.99 ||
        $sell_markup_percent < 0 || $sell_markup_percent > 999.99 ||
        $discount_percent < 0 || $discount_percent > 99.99) {
        throw new InvalidArgumentException('Enter valid non-negative prices and percentages (discount must be below 100%).');
    }

    $reseller_price = round($purchase_price * (1 + $reseller_markup_percent / 100), 2);
    $sell_price = round($purchase_price * (1 + $sell_markup_percent / 100), 2);
    $actual_price = round($sell_price * (1 - $discount_percent / 100), 2);
    $maximum_reseller_price = floor(($sell_price * 0.99 + 0.00000001) * 100) / 100;
    if ($sell_price <= 0 || $reseller_price > $maximum_reseller_price) {
        throw new InvalidArgumentException('Reseller price must be at least 1% lower than sell price.');
    }
    if ($actual_price < $reseller_price) {
        throw new InvalidArgumentException('Discount makes the customer price lower than the reseller price.');
    }

    return [
        'reseller_price' => $reseller_price,
        'sell_price' => $sell_price,
        'actual_price' => $actual_price,
    ];
}

function capture_reseller_referral(PDO $pdo): void
{
    $code = is_string($_GET['ref'] ?? null) ? trim($_GET['ref']) : '';
    if ($code === '' && is_string($_COOKIE['amarshop_reseller_ref'] ?? null)) {
        $code = trim($_COOKIE['amarshop_reseller_ref']);
    }
    if ($code === '' || strlen($code) > 24) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id, referral_code FROM reseller_accounts WHERE referral_code = :code AND status = 'approved' LIMIT 1");
    $stmt->execute([':code' => $code]);
    $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reseller) {
        return;
    }

    $_SESSION['reseller_referral_id'] = (int)$reseller['id'];
    setcookie('amarshop_reseller_ref', (string)$reseller['referral_code'], [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function current_reseller_referral_id(PDO $pdo): ?int
{
    $reseller_id = filter_var($_SESSION['reseller_referral_id'] ?? null, FILTER_VALIDATE_INT);
    if ($reseller_id === false || $reseller_id === null || $reseller_id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id FROM reseller_accounts WHERE id = :id AND status = 'approved' LIMIT 1");
    $stmt->execute([':id' => $reseller_id]);
    $approved_id = $stmt->fetchColumn();
    return $approved_id ? (int)$approved_id : null;
}

function record_reseller_order_commission(PDO $pdo, int $order_id): void
{
    $order_stmt = $pdo->prepare("
        SELECT reseller_id, order_status
        FROM orders
        WHERE id = :id
        FOR UPDATE
    ");
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || empty($order['reseller_id']) || strtolower((string)$order['order_status']) !== 'delivered') {
        return;
    }

    $exists = $pdo->prepare('SELECT 1 FROM reseller_commissions WHERE order_id = :order_id');
    $exists->execute([':order_id' => $order_id]);
    if ($exists->fetchColumn()) {
        return;
    }

    $totals = $pdo->prepare("
        SELECT COALESCE(SUM(oi.total_price), 0) AS order_amount,
            COALESCE(SUM(GREATEST(0, oi.price - COALESCE(oi.reseller_price, p.reseller_price)) * oi.quantity), 0) AS commission_amount
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = :order_id
    ");
    $totals->execute([':order_id' => $order_id]);
    $amounts = $totals->fetch(PDO::FETCH_ASSOC);
    $insert = $pdo->prepare("
        INSERT INTO reseller_commissions (reseller_id, order_id, order_amount, commission_amount)
        VALUES (:reseller_id, :order_id, :order_amount, :commission_amount)
    ");
    $insert->execute([
        ':reseller_id' => (int)$order['reseller_id'],
        ':order_id' => $order_id,
        ':order_amount' => (float)$amounts['order_amount'],
        ':commission_amount' => (float)$amounts['commission_amount'],
    ]);
}

function require_reseller(PDO $pdo): array
{
    $reseller_id = filter_var($_SESSION['reseller_id'] ?? null, FILTER_VALIDATE_INT);
    if ($reseller_id === false || $reseller_id === null || $reseller_id <= 0) {
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, name, phone, email, referral_code, status FROM reseller_accounts WHERE id = :id');
    $stmt->execute([':id' => $reseller_id]);
    $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reseller || $reseller['status'] !== 'approved') {
        unset($_SESSION['reseller_id'], $_SESSION['reseller_name']);
        $_SESSION['reseller_flash'] = [
            'type' => 'warning',
            'message' => $reseller && $reseller['status'] === 'rejected'
                ? 'Your reseller account was rejected. ' . (string)($_SESSION['reseller_rejection_reason'] ?? 'Please contact admin.')
                : 'Your reseller account is not approved. Please contact admin.',
        ];
        header('Location: login.php');
        exit;
    }

    return $reseller;
}
