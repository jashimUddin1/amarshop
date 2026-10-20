<?php
require_once('auth/config.php');

$collection_key = isset($_GET['name']) ? trim($_GET['name']) : '';

// টাইটেল রূপান্তর
$titles = [
    'new_arrivals'  => 'New Arrivals',
    'top_deals'     => 'Top Deals',
    'high_discount' => 'High Discount Products',
    'top_sales'     => 'Top Sales',
    'top_rated'     => 'Top Rated Products'
];

$page_title = $titles[$collection_key] ?? 'All Products';

// উক্ত কালেকশনের সকল প্রোডাক্ট ফেস করা
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :collection ORDER BY id DESC");
    $stmt->execute([':collection' => $collection_key]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title); ?> - Amarshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .product-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            background: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .product-img-container {
            height: 200px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .product-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .product-card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #198754;
        }

        .btn-add-cart {
            background: transparent;
            color: #198754;
            border: 1px solid #198754;
            border-radius: 5px;
            padding: 6px 12px;
            font-weight: 600;
        }

        .btn-add-cart:hover {
            background: #198754;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><?= htmlspecialchars($page_title); ?></h2>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Home</a>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="product-card">
                            <div class="product-img-container">
                                <img src="<?= base_url('uploads/' . ($product['main_img'] ?: 'placeholder.jpg')); ?>" class="img-fluid product-image-preview" style="cursor: zoom-in;" alt="<?= htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-card-body">
                                <div>
                                    <a href="product-details.php?id=<?= $product['id']; ?>" class="text-decoration-none text-dark fw-semibold mb-2 d-block">
                                        <?= htmlspecialchars($product['name']); ?>
                                    </a>
                                </div>
                                <div>
                                    <div class="mb-2">
                                        <span class="current-price">৳<?= number_format($product['actual_price'] ?? $product['sell_price'], 2); ?></span>
                                    </div>
                                    <a href="product-details.php?id=<?= $product['id']; ?>" class="btn btn-add-cart w-100 text-center">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted">No products found in this collection.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>