<?php
require_once '../auth/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $sub_category  = trim($_POST['sub_category'] ?? '');

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

        // ২. ছবি আপলোড প্রসেস করা; ছবি না দিলে default avatar ব্যবহার করা
        $upload_dir = '../../uploads/categories/';
        $default_photo = 'default-avatar.svg';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
            echo json_encode(['status' => 'error', 'message' => 'Could not create the category image folder.']);
            exit();
        }
        if (!is_file($upload_dir . $default_photo)) {
            echo json_encode(['status' => 'error', 'message' => 'The default category avatar is missing.']);
            exit();
        }

        $photo_name = $default_photo;
        if (isset($_FILES['category_photo']) && $_FILES['category_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['category_photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'Category photo upload failed.']);
                exit();
            }
            $file_ext = strtolower(pathinfo($_FILES['category_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image format! Only JPG, JPEG, PNG, WEBP & GIF are allowed.']);
                exit();
            }

            $photo_name = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_path = $upload_dir . $photo_name;

            if (!move_uploaded_file($_FILES['category_photo']['tmp_name'], $target_path)) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload category photo.']);
                exit();
            }
        }

        // ৩. নতুন ক্যাটাগরি ডাটাবেজে যুক্ত করা
        $stmt = $pdo->prepare("INSERT INTO categories (name, sub_category, category_photo) VALUES (:name, :sub_category, :photo)");
        $stmt->execute([
            ':name'         => $category_name,
            ':sub_category'  => $sub_category,
            ':photo'        => $photo_name
        ]);

        $_SESSION['success'] = "Category added successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Category added successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}