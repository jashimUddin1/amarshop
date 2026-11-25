<?php
// admin/products/core/product_edit_core.php
header('Content-Type: application/json');

require_once '../auth/config.php';
require_once __DIR__ . '/../../core/reseller.php';
require_admin();
ensure_reseller_tables($pdo);

$response = ['status' => 'error', 'message' => 'Something went wrong!'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id       = $_POST['product_id'] ?? null;
    $product_code     = trim($_POST['product_code'] ?? '');
    $name             = trim($_POST['name'] ?? '');
    $category_name    = trim($_POST['category_name'] ?? '');
    $purchase_price = filter_var($_POST['purchase_price'] ?? null, FILTER_VALIDATE_FLOAT);
    $reseller_markup_percent = filter_var($_POST['reseller_markup_percent'] ?? null, FILTER_VALIDATE_FLOAT);
    $sell_markup_percent = filter_var($_POST['sell_markup_percent'] ?? null, FILTER_VALIDATE_FLOAT);
    $discount_percent = filter_var($_POST['discount_percent'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($purchase_price === false || $reseller_markup_percent === false ||
        $sell_markup_percent === false || $discount_percent === false) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Enter valid purchase price, markup and discount percentages.']);
        exit;
    }
    try {
        $prices = reseller_calculate_prices((float)$purchase_price, (float)$reseller_markup_percent, (float)$sell_markup_percent, (float)$discount_percent);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    $purchase_price = round((float)$purchase_price, 2);
    $description      = trim($_POST['description'] ?? '');
    $size             = trim($_POST['size'] ?? '');
    $color             = trim($_POST['product_color'] ?? '');
    $stock            = (int)($_POST['stock'] ?? 0);
    $collection_name  = trim($_POST['collection_name'] ?? '');

    if (!$product_id) {
        $response = ['status' => 'error', 'message' => 'Invalid Product ID.'];
        echo json_encode($response);
        exit;
    }

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

    $main_img  = uploadImg('main_img')  ?? $_POST['old_main_img'];
    $side_img1 = uploadImg('side_img1') ?? $_POST['old_side_img1'];
    $side_img2 = uploadImg('side_img2') ?? $_POST['old_side_img2'];
    $side_img3 = uploadImg('side_img3') ?? $_POST['old_side_img3'];
    $side_img4 = uploadImg('side_img4') ?? $_POST['old_side_img4'];
    $side_img5 = uploadImg('side_img5') ?? $_POST['old_side_img5'];

    try {
        $sql = "UPDATE products SET 
            product_code = ?, name = ?, category_name = ?, purchase_price = ?,
            reseller_markup_percent = ?, sell_markup_percent = ?, reseller_price = ?, sell_price = ?,
            discount_percent = ?, description = ?, size = ?, color = ?, stock = ?, collection_name = ?, 
            main_img = ?, side_img1 = ?, side_img2 = ?, side_img3 = ?, side_img4 = ?, side_img5 = ?
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $product_code, $name, $category_name, $purchase_price, $reseller_markup_percent,
            $sell_markup_percent, $prices['reseller_price'], $prices['sell_price'],
            $discount_percent, $description, $size, $color, $stock, $collection_name, 
            $main_img, $side_img1, $side_img2, $side_img3, $side_img4, $side_img5, $product_id
        ]);

        $_SESSION['status'] = "Product Updated Successfully!";
        $_SESSION['status_type'] = "success";

        $response = ['status' => 'success', 'message' => 'Product Updated Successfully!'];
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
exit;