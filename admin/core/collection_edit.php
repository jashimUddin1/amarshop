<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = trim($_POST['collection_id'] ?? '');
    $collection_key = trim($_POST['collection_key'] ?? '');
    $display_name   = trim($_POST['display_name'] ?? '');

    if (empty($id) || empty($collection_key) || empty($display_name)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        // ডুপ্লিকেট কি (Key) চেক (বর্তমান ID বাদে)
        $checkStmt = $pdo->prepare("SELECT id FROM collections WHERE LOWER(collection_key) = LOWER(:key) AND id != :id");
        $checkStmt->execute([
            ':key' => $collection_key,
            ':id'  => $id
        ]);

        if ($checkStmt->rowCount() > 0) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'This Collection Key is already in use!']);
            exit();
        }

        // ডাটা আপডেট করা
        $stmt = $pdo->prepare("UPDATE collections SET collection_key = :key, display_name = :name WHERE id = :id");
        $stmt->execute([
            ':key'  => $collection_key,
            ':name' => $display_name,
            ':id'   => $id
        ]);

        $_SESSION['success'] = "Collection updated successfully!";
        
        ob_end_clean(); // অতিরিক্ত কোনো স্পেস বা ফাইল ওয়ার্নিং থাকলে তা ক্লিন করবে
        echo json_encode(['status' => 'success', 'message' => 'Collection updated successfully!']);
        exit();
    } catch (PDOException $e) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        exit();
    }
}