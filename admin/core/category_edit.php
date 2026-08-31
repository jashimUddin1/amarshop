<?php
require_once '../auth/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['category_id'] ?? '';
    $category_name = trim($_POST['category_name'] ?? '');

    if (empty($id) || empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        // ১. নিজের আইডি ছাড়া অন্য কোথাও একই নাম আছে কিনা চেক করা
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) AND id != :id");
        $checkStmt->execute([
            ':name' => $category_name,
            ':id'   => $id
        ]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'This category name already exists!']);
            exit();
        }

        // ২. আপডেট করা
        $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
        $stmt->execute([
            ':name' => $category_name,
            ':id'   => $id
        ]);

        $_SESSION['success'] = "Category updated successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Category updated successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}