<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = trim($_POST['sale_id'] ?? '');
    $status  = trim($_POST['status'] ?? '');

    if (empty($sale_id) || empty($status)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = :order_status WHERE id = :id");
        $stmt->execute([
            ':order_status' => $status,
            ':id'     => $sale_id
        ]);

        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'Order status updated successfully!']);
        exit();
    } catch (PDOException $e) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}