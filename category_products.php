<?php
require_once("auth/config.php");

// URL থেকে Category ক্যাচ করা
$category_name = isset($_GET['category']) ? trim($_GET['category']) : '';

$products = [];
$all_categories = [];
$cart_items = [];

try {
    if (!empty($category_name)) {
        // নির্দিষ্ট ক্যাটাগরির প্রোডাক্ট ফেচ করা
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_name = :category ORDER BY id ASC");
        $stmt->execute(['category' => $category_name]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_SESSION['user_id']) && !empty($products)) {
            $cart_stmt = $pdo->prepare("SELECT product_id, size, quantity FROM cart WHERE user_id = :user_id");
            $cart_stmt->execute([':user_id' => $_SESSION['user_id']]);
            while ($cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
                $cart_items[$cart_item['product_id']] = $cart_item;
            }
        }
    } else {
        // ক্যাটাগরি না থাকলে সমস্ত ক্যাটাগরি ফেচ করা
        $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
        $all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

$page_title = !empty($category_name) ? $category_name . ' - Products' : 'All Categories';
include 'includes/header.php';
?>

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
                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                    <?php foreach ($products as $prod): ?>
                        <?php
                        $sell_price = (float)($prod['sell_price'] ?? 0);
                        $actual_price = (float)($prod['actual_price'] ?? $sell_price);
                        $has_discount = $sell_price > $actual_price && $sell_price > 0;
                        $discount_percent = $has_discount ? round((($sell_price - $actual_price) / $sell_price) * 100) : 0;
                        $stock = (int)($prod['stock'] ?? 0);
                        ?>
                        <div class="col">
                            <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">
                                <div class="product-img-container text-center mb-2 position-relative">
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="product-badge bg-danger text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">
                                            <?= $discount_percent; ?>%
                                        </span>
                                    <?php endif; ?>
                                    <img src="<?= base_url('uploads/' . ($prod['main_img'] ?: 'placeholder.jpg')); ?>" class="img-fluid rounded product-image-preview" style="cursor: zoom-in;" alt="<?= htmlspecialchars($prod['name']); ?>">
                                </div>

                                <div class="product-card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between">
                                            <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                                <?= htmlspecialchars($prod['category_name'] ?? 'General'); ?>
                                            </p>
                                            <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                                <?= htmlspecialchars($prod['product_code'] ?? ''); ?>
                                            </p>
                                        </div>
                                        <a href="product_details.php?product_id=<?= (int)$prod['id']; ?>" class="product-title text-decoration-none text-dark fw-bold d-block text-truncate">
                                            <?= htmlspecialchars($prod['name']); ?>
                                        </a>
                                    </div>

                                    <div class="mt-2">
                                        <div class="row justify-content-between">
                                            <div class="price-wrapper col-md-6 mb-2 d-flex align-items-center gap-2">
                                                <span class="current-price fw-bold text-success fs-6">৳<?= number_format((float)$actual_price, 0); ?></span>
                                                <?php if ($has_discount): ?>
                                                    <span class="old-price text-muted text-decoration-line-through small">৳<?= number_format((float)$sell_price, 0); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <?php if ($stock > 0): ?>
                                                    <span class="badge bg-success mb-2">In Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger mb-2">Out of Stock</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between gap-2 mb-2 mx-2">
                                            <a href="product_details.php?product_id=<?= (int)$prod['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">
                                                <span class="d-none d-md-inline"><i class="bi bi-eye me-1"></i></span> View
                                            </a>
                                            <?php if (isset($cart_items[$prod['id']])): ?>
                                                <?php
                                                $in_cart = $cart_items[$prod['id']];
                                                $cart_text = !empty($in_cart['size'])
                                                    ? htmlspecialchars($in_cart['size']) . ' - ' . (int)$in_cart['quantity'] . ' ps'
                                                    : (int)$in_cart['quantity'] . ' ps';
                                                ?>
                                                <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn fw-bold" <?= $stock <= 0 ? 'disabled' : ''; ?>>
                                                    <?= $cart_text; ?>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn" <?= $stock <= 0 ? 'disabled' : ''; ?>>
                                                    <span class="d-none d-md-inline"><i class="bi bi-cart-plus me-1"></i></span> Add <span class="d-none d-md-inline">to Cart</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-options-overlay position-absolute bottom-0 end-0 mwt bg-white rounded p-3 shadow d-none flex-column justify-content-between cart_popup" style="z-index: 10;">
                                    <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                        <span class="fw-bold text-dark small">Select Options</span>
                                        <button type="button" class="btn-close close-options-btn" style="font-size: 0.7rem;"></button>
                                    </div>

                                    <?php if (!empty($prod['size'])): ?>
                                        <div class="my-2">
                                            <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Size:</label>
                                            <div class="d-flex gap-1 justify-content-start flex-wrap">
                                                <?php foreach (array_values(array_filter(array_map('trim', explode(',', $prod['size'])))) as $index => $size): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success size-btn px-2 py-0 fw-bold <?= $index === 0 ? 'active' : ''; ?>">
                                                        <?= htmlspecialchars($size); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-2">
                                        <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Quantity (Max 6):</label>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                            <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px; font-size: 0.8rem;">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn" data-product-id="<?= (int)$prod['id']; ?>" data-product-code="<?= htmlspecialchars($prod['product_code'] ?? ''); ?>">
                                        Add to Cart
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
                            $cat_img = 'uploads/categories/' . ($cat['category_photo'] ?: 'default-avatar.svg');
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

<?php include 'includes/footer.php'; ?>