<?php
/**
 * get_history.php — Admin History API
 * Returns JSON history for a staff user or product
 * GET params: type=staff|product, id=<int>
 */
require_once __DIR__ . '/../auth/config.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$type = is_string($_GET['type'] ?? null) ? trim($_GET['type']) : '';
$id   = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id <= 0 || !in_array($type, ['staff', 'product'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

// Helper: resolve admin user name by id
function resolve_admin_name(PDO $pdo, ?int $uid): string {
    if (!$uid) return '—';
    static $cache = [];
    if (!isset($cache[$uid])) {
        $s = $pdo->prepare('SELECT customar_name FROM admin_users WHERE id = :id LIMIT 1');
        $s->execute([':id' => $uid]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        $cache[$uid] = $row ? $row['customar_name'] : "User #$uid";
    }
    return $cache[$uid];
}

$rows = [];

if ($type === 'staff') {
    // Get the target staff member's info
    $stmt = $pdo->prepare('SELECT id, customar_name, email, phone, role, verify_status, created_at FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        http_response_code(404);
        echo json_encode(['error' => 'Staff not found.']);
        exit;
    }

    // Build history from what we know:
    // 1. Created account
    $rows[] = [
        'time'   => $staff['created_at'],
        'action' => 'Account Created',
        'detail' => 'Role: ' . $staff['role'],
        'by'     => '—',
        'icon'   => 'person-plus',
        'color'  => 'success',
    ];

    // Check orders done by this staff (if orders table has updated_by / created_by linking to admin)
    // We'll look at products uploaded by this user
    try {
        $prod_stmt = $pdo->prepare('SELECT id, name, product_code, created_at, updated_at FROM products WHERE created_by = :id ORDER BY created_at DESC LIMIT 20');
        $prod_stmt->execute([':id' => $id]);
        $prods = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($prods as $p) {
            $rows[] = [
                'time'   => $p['created_at'],
                'action' => 'Product Added',
                'detail' => htmlspecialchars($p['name'] . ' [' . $p['product_code'] . ']', ENT_QUOTES, 'UTF-8'),
                'by'     => resolve_admin_name($pdo, $id),
                'icon'   => 'box-seam',
                'color'  => 'primary',
            ];
        }

        $upd_stmt = $pdo->prepare('SELECT id, name, product_code, updated_at FROM products WHERE updated_by = :id AND updated_at IS NOT NULL ORDER BY updated_at DESC LIMIT 20');
        $upd_stmt->execute([':id' => $id]);
        $upds = $upd_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($upds as $p) {
            $rows[] = [
                'time'   => $p['updated_at'],
                'action' => 'Product Updated',
                'detail' => htmlspecialchars($p['name'] . ' [' . $p['product_code'] . ']', ENT_QUOTES, 'UTF-8'),
                'by'     => resolve_admin_name($pdo, $id),
                'icon'   => 'pencil-square',
                'color'  => 'warning',
            ];
        }

        $del_stmt = $pdo->prepare('SELECT id, name, product_code, deleted_at, delete_reason FROM products WHERE deleted_by = :id AND deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 20');
        $del_stmt->execute([':id' => $id]);
        $dels = $del_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dels as $p) {
            $rows[] = [
                'time'   => $p['deleted_at'],
                'action' => 'Product Deleted',
                'detail' => htmlspecialchars($p['name'] . ' [' . $p['product_code'] . ']' . (!empty($p['delete_reason']) ? ' — ' . $p['delete_reason'] : ''), ENT_QUOTES, 'UTF-8'),
                'by'     => resolve_admin_name($pdo, $id),
                'icon'   => 'trash',
                'color'  => 'danger',
            ];
        }
    } catch (PDOException $e) {
        // ignore if columns don't exist yet
    }

    // Check purchases uploaded by this admin
    try {
        $pur_stmt = $pdo->prepare('SELECT id, created_at FROM purchases WHERE created_by = :id ORDER BY created_at DESC LIMIT 10');
        $pur_stmt->execute([':id' => $id]);
        $purs = $pur_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($purs as $p) {
            $rows[] = [
                'time'   => $p['created_at'],
                'action' => 'Purchase Created',
                'detail' => 'Purchase #' . $p['id'],
                'by'     => resolve_admin_name($pdo, $id),
                'icon'   => 'bag-check',
                'color'  => 'info',
            ];
        }
    } catch (PDOException $e) {}

    // Sort by time desc
    usort($rows, fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));

    echo json_encode([
        'name'  => htmlspecialchars($staff['customar_name'], ENT_QUOTES, 'UTF-8'),
        'role'  => htmlspecialchars($staff['role'], ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($staff['email'], ENT_QUOTES, 'UTF-8'),
        'rows'  => $rows,
    ]);
    exit;
}

if ($type === 'product') {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found.']);
        exit;
    }

    // Created
    if (!empty($product['created_at'])) {
        $rows[] = [
            'time'   => $product['created_at'],
            'action' => 'Product Created',
            'detail' => 'Added to system',
            'by'     => resolve_admin_name($pdo, $product['created_by'] ? (int)$product['created_by'] : null),
            'icon'   => 'plus-circle',
            'color'  => 'success',
        ];
    }

    // Updated
    if (!empty($product['updated_at']) && $product['updated_at'] !== $product['created_at']) {
        $rows[] = [
            'time'   => $product['updated_at'],
            'action' => 'Product Updated',
            'detail' => 'Product details were edited',
            'by'     => resolve_admin_name($pdo, $product['updated_by'] ? (int)$product['updated_by'] : null),
            'icon'   => 'pencil-square',
            'color'  => 'warning',
        ];
    }

    // Deleted (soft)
    if (!empty($product['deleted_at'])) {
        $reason = !empty($product['delete_reason']) ? $product['delete_reason'] : 'No reason provided';
        $rows[] = [
            'time'   => $product['deleted_at'],
            'action' => 'Product Deleted',
            'detail' => 'Reason: ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
            'by'     => resolve_admin_name($pdo, $product['deleted_by'] ? (int)$product['deleted_by'] : null),
            'icon'   => 'trash',
            'color'  => 'danger',
        ];
    }

    // Check purchases related to this product
    try {
        $pur_stmt = $pdo->prepare('
            SELECT pi.quantity, pi.purchase_price, p.created_at, p.created_by
            FROM purchase_items pi
            JOIN purchases p ON p.id = pi.purchase_id
            WHERE pi.product_id = :id
            ORDER BY p.created_at DESC
            LIMIT 20
        ');
        $pur_stmt->execute([':id' => $id]);
        $purs = $pur_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($purs as $pur) {
            $rows[] = [
                'time'   => $pur['created_at'],
                'action' => 'Stock Purchased',
                'detail' => 'Qty: ' . $pur['quantity'] . ' @ ৳' . number_format((float)$pur['purchase_price'], 0),
                'by'     => resolve_admin_name($pdo, $pur['created_by'] ? (int)$pur['created_by'] : null),
                'icon'   => 'bag-check',
                'color'  => 'info',
            ];
        }
    } catch (PDOException $e) {}

    // Sort by time desc
    usort($rows, fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));

    echo json_encode([
        'name'  => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
        'code'  => htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8'),
        'rows'  => $rows,
    ]);
    exit;
}
