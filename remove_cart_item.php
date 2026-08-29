<?php
require_once('auth/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $user_id = $_SESSION['user_id'];

    if ($cart_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Cart ID']);
        exit;
    }

    try {
        // নিশ্চিত হওয়া যে আইটেমটি এই ইউজারেরই
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id'      => $cart_id,
            ':user_id' => $user_id
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Item removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found or already deleted']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}