<?php
require_once '../auth/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = $_POST['category_id'] ?? '';
    $category_name = trim($_POST['category_name'] ?? '');
    $sub_category  = trim($_POST['sub_category'] ?? '');

    if (empty($id) || empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name and ID are required.']);
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

        // বর্তমান ক্যাটাগরির ডাটা তুলে আনা (পুরনো ছবি ডিলিট/রাখার জন্য)
        $currentStmt = $pdo->prepare("SELECT category_photo FROM categories WHERE id = :id");
        $currentStmt->execute([':id' => $id]);
        $current_cat = $currentStmt->fetch(PDO::FETCH_ASSOC);

        $photo_name = $current_cat['category_photo'] ?? null;

        // ২. নতুন ছবি সিলেক্ট করা হলে তা আপলোড করা
        if (isset($_FILES['category_photo']) && $_FILES['category_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/categories/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['category_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image format! Only JPG, JPEG, PNG, WEBP & GIF are allowed.']);
                exit();
            }

            $new_photo_name = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_path = $upload_dir . $new_photo_name;

            if (move_uploaded_file($_FILES['category_photo']['tmp_name'], $target_path)) {
                // পুরনো ছবি সার্ভার ফোল্ডার থেকে ডিলিট করে দেওয়া
                if (!empty($photo_name) && file_exists($upload_dir . $photo_name)) {
                    unlink($upload_dir . $photo_name);
                }
                $photo_name = $new_photo_name;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload new category photo.']);
                exit();
            }
        }

        // ৩. ডাটাবেজ আপডেট করা
        $stmt = $pdo->prepare("UPDATE categories SET name = :name, sub_category = :sub_category, category_photo = :photo WHERE id = :id");
        $stmt->execute([
            ':name'         => $category_name,
            ':sub_category'  => $sub_category,
            ':photo'        => $photo_name,
            ':id'           => $id
        ]);

        $_SESSION['success'] = "Category updated successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Category updated successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}