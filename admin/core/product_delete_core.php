<?php
// admin/products/core/product_delete_core.php
header('Content-Type: application/json');

require_once '../auth/config.php';
require_admin();

$response = ['status' => 'error', 'message' => 'Something went wrong!'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing CSRF token.']);
        exit;
    }

    $role = strtoupper(trim((string)($_SESSION['user_role'] ?? '')));
    if (!in_array($role, ['CEO', 'MD', 'MANAGER'], true)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete products.']);
        exit;
    }

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