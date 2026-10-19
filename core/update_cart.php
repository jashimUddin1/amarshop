<?php
require_once('../auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id  = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $action   = $_POST['action'] ?? ''; // 'increase', 'decrease', or 'update_size'
    $new_size = isset($_POST['size']) ? trim($_POST['size']) : null;
    $user_id  = $_SESSION['user_id'];

    if ($cart_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Cart Item']);
        exit;
    }

    try {
        // কার্ট আইটেম চেক করা
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $cart_id, ':user_id' => $user_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
            exit;
        }

        if ($action === 'increase') {
            if ((int)$item['quantity'] >= 6) {
                echo json_encode(['status' => 'error', 'message' => 'Maximum quantity is 6.']);
                exit;
            }

            $update = $pdo->prepare("
                UPDATE cart
                SET quantity = quantity + 1
                WHERE id = :id AND user_id = :user_id AND quantity < 6
            ");
            $update->execute([':id' => $cart_id, ':user_id' => $user_id]);
            if ($update->rowCount() === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Maximum quantity is 6.']);
                exit;
            }
        } 
        elseif ($action === 'decrease') {
            if ($item['quantity'] > 1) {
                $new_qty = $item['quantity'] - 1;
                $update = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE id = :id");
                $update->execute([':qty' => $new_qty, ':id' => $cart_id]);
            }
        } 
        elseif ($action === 'update_size' && $new_size !== null) {
            $update = $pdo->prepare("UPDATE cart SET size = :size WHERE id = :id");
            $update->execute([':size' => $new_size, ':id' => $cart_id]);
        }

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}