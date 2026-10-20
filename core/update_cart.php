<?php
require_once('../auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$cart_id = filter_var($_POST['cart_id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
if ($cart_id === false || $cart_id <= 0 || !is_string($action)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid cart item or action.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT c.quantity, c.product_id, p.stock, p.size AS available_sizes
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.id = :id AND c.user_id = :user_id
        FOR UPDATE
    ");
    $stmt->execute([':id' => $cart_id, ':user_id' => $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Cart item not found.']);
        exit;
    }

    if ($action === 'increase' || $action === 'decrease') {
        $quantity = (int)$item['quantity'] + ($action === 'increase' ? 1 : -1);
        if ($quantity < 1) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Quantity cannot be less than 1.']);
            exit;
        }
        if ($quantity > 6 || $quantity > (int)$item['stock']) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Requested quantity exceeds the available stock or maximum of 6.']);
            exit;
        }

        $update = $pdo->prepare('UPDATE cart SET quantity = :quantity WHERE id = :id AND user_id = :user_id');
        $update->execute([
            ':quantity' => $quantity,
            ':id' => $cart_id,
            ':user_id' => $_SESSION['user_id'],
        ]);
    } elseif ($action === 'update_size') {
        $new_size = $_POST['size'] ?? null;
        if (!is_string($new_size)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid size.']);
            exit;
        }

        $new_size = trim($new_size);
        $available_sizes = array_filter(array_map('trim', explode(',', (string)$item['available_sizes'])));
        if (!in_array($new_size, $available_sizes, true)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Selected size is not available for this product.']);
            exit;
        }

        $update = $pdo->prepare('UPDATE cart SET size = :size WHERE id = :id AND user_id = :user_id');
        $update->execute([
            ':size' => $new_size,
            ':id' => $cart_id,
            ':user_id' => $_SESSION['user_id'],
        ]);
    } else {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unsupported cart action.']);
        exit;
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Cart updated successfully.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Cart update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cart could not be updated. Please try again.']);
}