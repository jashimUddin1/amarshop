<?php
require_once("auth/config.php");

// URL থেকে Category ক্যাচ করা
$category_name = isset($_GET['category']) ? trim($_GET['category']) : '';

$products = [];
$all_categories = [];

try {
    if (!empty($category_name)) {
        // নির্দিষ্ট ক্যাটাগরির প্রোডাক্ট ফেচ করা
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_name = :category ORDER BY id ASC");
        $stmt->execute(['category' => $category_name]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // ক্যাটাগরি না থাকলে সমস্ত ক্যাটাগরি ফেচ করা
        $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
        $all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= !empty($category_name) ? htmlspecialchars($category_name) . ' - Products' : 'All Categories - Amarshop'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fa; }
        .product-card, .category-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #fff;
            transition: 0.3s;
        }
        .product-card:hover, .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>

    <div class="container my-4">
        <!-- Navigation Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                        <?php if (!empty($category_name)): ?>
                            <li class="breadcrumb-item"><a href="category_products.php" class="text-decoration-none">Categories</a></li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($category_name); ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active">All Categories</li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0"><?= !empty($category_name) ? 'Category: ' . htmlspecialchars($category_name) : 'All Categories'; ?></h2>
            </div>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                Back
            </button>
        </div>

        <?php if (!empty($category_name)): ?>
            <!-- Product Grid (যখন নির্দিষ্ট ক্যাটাগরি থাকবে) -->
            <?php if (!empty($products)): ?>
                <div class="row row-cols-2 row-cols-md-4 g-4">
                    <?php foreach ($products as $prod): ?>
                        <div class="col">
                            <div class="product-card p-3 text-center h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <img src="uploads/<?= htmlspecialchars($prod['main_img']); ?>" class="img-fluid rounded mb-3" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($prod['name']); ?>">
                                    <h5 class="fs-6 text-truncate"><?= htmlspecialchars($prod['name']); ?></h5>
                                    <p class="text-success fw-bold fs-5 mb-2">৳<?= number_format($prod['sell_price'], 2); ?></p>
                                </div>
                                
                                <div class="d-flex gap-2 mt-2">
                                    <a href="product_details.php?product_id=<?= $prod['id']; ?>" class="btn btn-sm btn-outline-success flex-grow-1" title="View Details">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                    <button class="btn btn-sm btn-success flex-grow-1" <?= ((int)$prod['stock'] <= 0) ? 'disabled' : ''; ?> title="Add to Cart">
                                        <i class="bi bi-cart-plus me-1"></i> Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center my-5">
                    <h4>No products found in "<?= htmlspecialchars($category_name); ?>" category.</h4>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- All Categories Grid (যখন কোনো ক্যাটাগরি সিলেক্ট করা থাকবে না) -->
            <?php if (!empty($all_categories)): ?>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-4">
                    <?php foreach ($all_categories as $cat): ?>
                        <?php 
                            $cat_img = !empty($cat['category_photo']) ? 'uploads/categories/' . $cat['category_photo'] : 'https://via.placeholder.com/150x150/f8f9fa/374151?text=' . urlencode($cat['name']);
                        ?>
                        <div class="col">
                            <a href="category_products.php?category=<?= urlencode($cat['name']); ?>" class="text-decoration-none">
                                <div class="category-card text-center h-100 p-2">
                                    <div class="category-img-container ratio ratio-1x1 mb-2">
                                        <img src="<?= htmlspecialchars($cat_img); ?>" class="img-fluid rounded object-fit-cover" alt="<?= htmlspecialchars($cat['name']); ?>">
                                    </div>
                                    <div class="card-body p-1">
                                        <h5 class="fs-6 text-dark fw-bold text-truncate mb-0"><?= htmlspecialchars($cat['name']); ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center my-5">
                    <h4>No categories available.</h4>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</body>
</html>