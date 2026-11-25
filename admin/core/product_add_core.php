<?php
// admin/products/core/product_add_core.php
header('Content-Type: application/json');

require_once '../auth/config.php';
require_once __DIR__ . '/../../core/reseller.php';
require_admin();
ensure_reseller_tables($pdo);

$response = ['status' => 'error', 'message' => 'Something went wrong!'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = $_SESSION['user_id'] ?? 1;

    $product_code     = trim($_POST['product_code'] ?? '');
    $name             = trim($_POST['name'] ?? '');
    $category_name    = trim($_POST['category_name'] ?? '');
    $purchase_price = filter_var(trim((string)($_POST['purchase_price'] ?? '')), FILTER_VALIDATE_FLOAT);
    $reseller_percent = filter_var(trim((string)($_POST['reseller_price'] ?? '')), FILTER_VALIDATE_FLOAT);
    $sell_percent = filter_var(trim((string)($_POST['sell_price'] ?? '')), FILTER_VALIDATE_FLOAT);
    $discount_percent = filter_var(trim((string)($_POST['discount_percent'] ?? '')), FILTER_VALIDATE_FLOAT);
    if ($purchase_price === false || $reseller_percent === false ||
        $sell_percent === false || $discount_percent === false) {
        echo json_encode(['status' => 'error', 'message' => 'Enter a valid purchase price, reseller percentage, sell percentage and discount.']);
        exit;
    }
    try {
        $prices = reseller_calculate_prices((float)$purchase_price, (float)$reseller_percent, (float)$sell_percent, (float)$discount_percent);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    $reseller_markup_percent = (float)$reseller_percent;
    $sell_markup_percent = (float)$sell_percent;
    $purchase_price = round((float)$purchase_price, 2);
    $description      = trim($_POST['description'] ?? '');
    $size             = trim($_POST['size'] ?? '');
    $color            = trim($_POST['product_color'] ?? '');
    $stock            = (int)($_POST['stock'] ?? 0);
    $collection_name  = trim($_POST['collection_name'] ?? '');

    // Image Upload Helper Function
    function uploadImg($file_key) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed_ext)) {
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $upload_dir = '../../uploads/';
                if (!is_dir($upload_dir)) { 
                    mkdir($upload_dir, 0777, true); 
                }
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_dir . $filename)) {
                    return $filename;
                }
            }
        }
        return NULL;
    }

    $main_img  = uploadImg('main_img');
    $side_img1 = uploadImg('side_img1');
    $side_img2 = uploadImg('side_img2');
    $side_img3 = uploadImg('side_img3');
    $side_img4 = uploadImg('side_img4');
    $side_img5 = uploadImg('side_img5');

    if (empty($product_code) || empty($name) || empty($category_name) || !$main_img) {
        $response = ['status' => 'error', 'message' => 'Please fill all required fields and upload main image.'];
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "INSERT INTO products (
            product_code, name, category_name, purchase_price, reseller_markup_percent, sell_markup_percent,
            reseller_price, sell_price, discount_percent,
            description, size, color, stock, collection_name, main_img, side_img1, side_img2, side_img3, 
            side_img4, side_img5, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $product_code, $name, $category_name, $purchase_price, $reseller_markup_percent,
            $sell_markup_percent, $prices['reseller_price'], $prices['sell_price'], $discount_percent,
            $description, $size, $color, $stock, $collection_name, $main_img, $side_img1, $side_img2, $side_img3, 
            $side_img4, $side_img5, $current_user_id
        ]);

        $_SESSION['status'] = "Product Added Successfully!";
        $_SESSION['status_type'] = "success";

        $response = ['status' => 'success', 'message' => 'Product Added Successfully!'];
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
exit;