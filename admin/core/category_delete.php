<?php
require_once '../auth/config.php';
require_admin();
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['success'] = "Category deleted successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete category.']);
    }
}