<?php
require_once '../auth/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');

    if (empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
        exit();
    }

    try {
        // ১. ক্যাটাগরি অলরেডি আছে কিনা চেক করা
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(:name)");
        $checkStmt->execute([':name' => $category_name]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category name already exists!']);
            exit();
        }

        // ২. নতুন ক্যাটাগরি যুক্ত করা
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute([':name' => $category_name]);

        $_SESSION['success'] = "Category added successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Category added successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}