<?php

if (session_status() === PHP_SESSION_NONE) {
    $script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/offline/index.php'));
    $session_path = rtrim(str_replace('\\', '/', dirname($script_path)), '/') . '/';
    $session_secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $legacy_pos_session = [];

    if (isset($_COOKIE['AMARSHOP_OFFLINE_POS'])) {
        session_name('AMARSHOP_OFFLINE_POS');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $session_path,
            'secure' => $session_secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        foreach ($_SESSION as $key => $value) {
            if (is_string($key) && strpos($key, 'offline_pos_') === 0) {
                $legacy_pos_session[$key] = $value;
            }
        }
        session_write_close();
    }

    session_name('AMARSHOP_ADMIN_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $session_secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    foreach ($legacy_pos_session as $key => $value) {
        if (!array_key_exists($key, $_SESSION)) {
            $_SESSION[$key] = $value;
        }
    }
    if (ini_get('session.use_cookies') && !headers_sent()) {
        setcookie(session_name(), session_id(), [
            'expires' => 0,
            'path' => '/',
            'secure' => $session_secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (isset($_COOKIE['AMARSHOP_OFFLINE_POS'])) {
            setcookie('AMARSHOP_OFFLINE_POS', '', [
                'expires' => time() - 42000,
                'path' => $session_path,
                'secure' => $session_secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}

date_default_timezone_set('Asia/Dhaka');

define('POS_DB_HOST', getenv('AMARSHOP_POS_DB_HOST') ?: 'localhost');
define('POS_DB_NAME', getenv('AMARSHOP_POS_DB_NAME') ?: 'amarshop');
define('POS_DB_USER', getenv('AMARSHOP_POS_DB_USER') ?: 'root');
define('POS_DB_PASS', getenv('AMARSHOP_POS_DB_PASS') ?: '');
define('POS_STORE_NAME', getenv('AMARSHOP_POS_STORE_NAME') ?: 'AmarShop');
define('POS_SETUP_KEY', getenv('AMARSHOP_POS_SETUP_KEY') ?: '');
define('POS_LOW_STOCK_LEVEL', 5);

try {
    $pdo = new PDO(
        'mysql:host=' . POS_DB_HOST . ';dbname=' . POS_DB_NAME . ';charset=utf8mb4',
        POS_DB_USER,
        POS_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+06:00'");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS offline_pos_users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name VARCHAR(180) NOT NULL,
            username VARCHAR(80) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'cashier',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            admin_user_id BIGINT UNSIGNED NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_offline_pos_username (username),
            UNIQUE KEY uq_offline_pos_admin_user (admin_user_id),
            KEY idx_offline_pos_users_active (is_active, role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $admin_user_column = $pdo->query("SHOW COLUMNS FROM offline_pos_users LIKE 'admin_user_id'")->fetch();
    if (!$admin_user_column) {
        $pdo->exec('ALTER TABLE offline_pos_users ADD COLUMN admin_user_id BIGINT UNSIGNED NULL DEFAULT NULL');
    }
    $admin_user_index = $pdo->query("SHOW INDEX FROM offline_pos_users WHERE Key_name = 'uq_offline_pos_admin_user'")->fetch();
    if (!$admin_user_index) {
        $pdo->exec('ALTER TABLE offline_pos_users ADD UNIQUE KEY uq_offline_pos_admin_user (admin_user_id)');
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS offline_pos_installation (
            id TINYINT UNSIGNED NOT NULL,
            is_complete TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec('INSERT IGNORE INTO offline_pos_installation (id, is_complete) VALUES (1, 0)');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS offline_pos_sales (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            receipt_no VARCHAR(40) NOT NULL,
            cashier_id BIGINT UNSIGNED NOT NULL,
            customer_name VARCHAR(180) NOT NULL DEFAULT '',
            customer_phone VARCHAR(40) NOT NULL DEFAULT '',
            subtotal DECIMAL(12,2) NOT NULL,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(12,2) NOT NULL,
            cash_received DECIMAL(12,2) NOT NULL,
            change_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'cash',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_offline_pos_receipt (receipt_no),
            KEY idx_offline_pos_sales_date (created_at),
            KEY idx_offline_pos_sales_cashier (cashier_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS offline_pos_sale_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sale_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_code VARCHAR(50) NOT NULL DEFAULT '',
            product_name VARCHAR(255) NOT NULL,
            size VARCHAR(100) NOT NULL DEFAULT '',
            quantity INT UNSIGNED NOT NULL,
            unit_price DECIMAL(12,2) NOT NULL,
            line_total DECIMAL(12,2) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_offline_pos_sale_items_sale (sale_id, id),
            KEY idx_offline_pos_sale_items_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $exception) {
    error_log('Offline POS database initialization failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Offline POS could not connect to its database. Check offline/config.php and make sure the products table is available.');
}
