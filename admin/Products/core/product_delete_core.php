<?php
// admin/products/core/product_delete_core.php
header('Content-Type: application/json');

require_once '../../auth/config.php'; 

$response = ['status' => 'error', 'message' => 'Something went wrong!'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = $_POST['product_id'] ?? null;

    if ($p_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$p_id]);

            $_SESSION['status'] = "Product Permanently Deleted!";
            $_SESSION['status_type'] = "danger";

            $response = ['status' => 'success', 'message' => 'Product Deleted Permanently!'];
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid Product ID.'];
    }
}

echo json_encode($response);
exit;