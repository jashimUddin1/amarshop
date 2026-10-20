<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth/config.php';
require_admin();
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM collections WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'Collection deleted successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete collection.']);
    }
}