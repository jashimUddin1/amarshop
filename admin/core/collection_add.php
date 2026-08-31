<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth/config.php';

// নিশ্চিত করা হচ্ছে শুধু JSON রিটার্ন হবে
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collection_key = trim($_POST['collection_key'] ?? '');
    $display_name   = trim($_POST['display_name'] ?? '');

    if (empty($collection_key) || empty($display_name)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        // ১. ডুপ্লিকেট কি (Key) চেক
        $checkStmt = $pdo->prepare("SELECT id FROM collections WHERE LOWER(collection_key) = LOWER(:key)");
        $checkStmt->execute([':key' => $collection_key]);

        if ($checkStmt->rowCount() > 0) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Collection Key already exists!']);
            exit();
        }

        // ২. ডাটা ইনসার্ট করা
        $stmt = $pdo->prepare("INSERT INTO collections (collection_key, display_name) VALUES (:key, :name)");
        $stmt->execute([
            ':key'  => $collection_key,
            ':name' => $display_name
        ]);

        $_SESSION['success'] = "Collection added successfully!";
        
        ob_end_clean(); // অতিরিক্ত স্পেস বা ওয়ার্নিং মুছে ফেলা
        echo json_encode(['status' => 'success', 'message' => 'Collection added successfully!']);
        exit();
    } catch (PDOException $e) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        exit();
    }
}