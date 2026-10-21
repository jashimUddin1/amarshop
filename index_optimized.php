<?php //index.php main page - main frontend
require_once('auth/config.php');

function get_products_by_collection($pdo, $collection_name, $limit = 12)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :collection ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':collection', $collection_name, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$new_arrivals  = get_products_by_collection($pdo, 'new_arrivals');
$top_deals     = get_products_by_collection($pdo, 'top_deals');
$high_discount = get_products_by_collection($pdo, 'high_discount');
$top_sales     = get_products_by_collection($pdo, 'top_sales');
$top_rated     = get_products_by_collection($pdo, 'top_rated');

$is_logged_in = isset($_SESSION['user_id']) ? true : false;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amarshop - Website</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Swiper CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        :root {
            --brand: #198754;
            --brand-dark: #157347;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            color: #1f2937;
            overflow-x: hidden; /* html বাদ দিয়ে শুধু body-তে overflow রাখা হলো sticky কাজ করার জন্য */
        }

        /* Marquee Text Animation */
        .marquee-wrap {
            background: #212529;
            color: #fff;
            overflow: hidden;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .marquee {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 18s linear infinite;
        }

        @keyframes marquee {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }

        /* Header Icon Buttons */
        .icon-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border: 1px solid #dee2e6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #333;
            background: #fff;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .icon-btn:hover {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

        /* Dynamic Product Image Area */
        .product-img-container {
            height: 200px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }

        .product-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        @media (max-width: 575.98px) {
            .product-img-container {
                height: 150px;
            }
        }
    </style>
</head>

<body>

    <!-- Sticky Header Wrapper -->
    <div class="sticky-top z-3 bg-white shadow-sm">
        <!-- Topbar -->
        <div class="bg-primary text-white py-1 fs-7" style="font-size: 0.85rem;">
            <div class="container d-flex justify-content-between align-items-center">
                <small>Welcome to Amarshop</small>
                <small>Free delivery on orders over ৳2000</small>
            </div>
        </div>

        <!-- Marquee -->
        <div class="marquee-wrap py-1">
            <div class="marquee py-1">
                <span class="pe-4">✨ New Arrivals Every Week</span>
                <span class="pe-4">🔥 Flash Sale - Up to 50% Off</span>
                <span class="pe-4">🚚 Fast Delivery Across the City</span>
                <span class="pe-4">💳 Secure Online Payment</span>
            </div>
        </div>

        <!-- Header Section -->
        <header class="py-2 border-bottom">
            <div class="container-fluid px-2 px-md-4">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    
                    <!-- Logo -->
                    <a href="index.php" class="d-flex align-items-center text-decoration-none flex-shrink-0">
                        <img src="assets/img/logo1.png" alt="Logo" class="rounded" style="height: 40px; width: auto;">
                    </a>

                    <!-- Search Bar -->
                    <div class="flex-grow-1 mx-2" style="max-width: 700px;">
                        <form action="search.php" method="GET">
                            <div class="input-group">
                                <select class="form-select bg-light text-secondary d-none d-sm-block" style="max-width: 130px; font-size: 0.85rem;">
                                    <option selected>Category</option>
                                    <option>Shirt</option>
                                    <option>Katua</option>
                                    <option>T-Shirt</option>
                                    <option>Pant</option>
                                </select>
                                <input type="text" name="q" class="form-control fs-7" placeholder="Search products..." style="font-size: 0.88rem;">
                                <button class="btn btn-success px-3" type="submit">
                                    <i class="bi bi-search d-inline d-md-none"></i>
                                    <span class="d-none d-md-inline small fw-semibold">Search</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Header Icons -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <!-- Cart Icon -->
                        <a href="#cartOffcanvas" class="icon-btn" data-bs-toggle="offcanvas" role="button" title="Cart">
                            <i class="bi bi-cart3"></i>
                            <span class="badge bg-success rounded-circle position-absolute top-0 start-100 translate-middle p-1" id="cartBadge" style="font-size: 0.65rem;">0</span>
                        </a>

                        <!-- User Profile Icon -->
                        <?php if (!$is_logged_in): ?>
                            <a href="auth/login.php" class="icon-btn" title="Login / Register">
                                <i class="bi bi-person"></i>
                                <span class="badge bg-danger rounded-circle position-absolute top-0 start-100 translate-middle p-1" style="font-size: 0.65rem;">?</span>
                            </a>
                        <?php else: ?>
                            <?php
                            $user_name = trim($_SESSION['user_name'] ?? '');
                            if (!empty($user_name)) {
                                $first_word = explode(' ', $user_name)[0];
                                $display_name = (mb_strlen($first_word) > 3) ? mb_substr($first_word, 0, 3) . '.' : $first_word;
                            } else {
                                $display_name = 'User';
                            }
                            ?>
                            <button class="icon-btn border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#userProfileDrawer" title="My Account">
                                <i class="bi bi-person"></i>
                                <span class="badge bg-success rounded-pill position-absolute top-0 start-100 translate-middle px-1" style="font-size: 0.65rem;">
                                    <?= htmlspecialchars($display_name); ?>
                                </span>
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </header>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
        <?php $alert_type = ($_SESSION['status'] === 'success') ? 'success' : (($_SESSION['status'] === 'warning') ? 'warning' : 'danger'); ?>
        <div class="container my-3">
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show py-2 px-3 shadow-sm mb-0" role="alert">
                <small><?= htmlspecialchars($_SESSION['message']); ?></small>
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['status'], $_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Hero Slider Section -->
    <div id="heroCarousel" class="carousel slide carousel-fade shadow-sm my-3" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        </div>

        <div class="carousel-inner bg-light">
            <!-- Slide 1 -->
            <div class="carousel-item active" data-bs-interval="5000">
                <div class="container py-4 py-md-5">
                    <div class="row align-items-center min-vh-50">
                        <div class="col-md-6 order-2 order-md-1 text-center text-md-start px-4">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                                <span class="badge bg-success me-2">New</span>
                                <span class="text-muted fw-bold small text-uppercase">New Collection</span>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-3">The Great Fashion Collection 2026</h1>
                            <p class="text-secondary mb-4">Up To 40% Off Final Sale Items.</p>
                            <a href="#" class="btn btn-success px-4 py-2 shadow-sm">
                                Show Collection <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="col-md-6 order-1 order-md-2 text-center mb-3 mb-md-0">
                            <img src="assets/uploads/model3rbg.png" class="img-fluid" alt="Fashion Collection" style="max-height: 380px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item" data-bs-interval="5000">
                <div class="container py-4 py-md-5">
                    <div class="row align-items-center min-vh-50">
                        <div class="col-md-6 order-1 text-center mb-3 mb-md-0">
                            <img src="assets/uploads/model1rbg.png" class="img-fluid" alt="Trending Gadgets" style="max-height: 380px; object-fit: contain;">
                        </div>
                        <div class="col-md-6 order-2 text-center text-md-start px-4">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                                <span class="badge bg-danger me-2">Hot</span>
                                <span class="text-muted fw-bold small text-uppercase">Trending Now</span>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-3">Exclusive Premium Gadgets Pack</h1>
                            <p class="text-secondary mb-4">Up To 50% Off On Modern Tech Accessories.</p>
                            <a href="#" class="btn btn-success px-4 py-2 shadow-sm">
                                Shop Collection
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="btn btn-success rounded-circle p-2 d-flex align-items-center justify-content-center shadow">
                <i class="bi bi-chevron-left"></i>
            </span>
        </button>
        <button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="btn btn-success rounded-circle p-2 d-flex align-items-center justify-content-center shadow">
                <i class="bi bi-chevron-right"></i>
            </span>
        </button>
    </div>

    <!-- Dynamic Collections Section -->
    <?php
    $all_collections = [
        ['title' => 'New Arrivals', 'slug' => 'new_arrivals', 'products' => $new_arrivals ?? [], 'badge' => 'New', 'badge_bg' => 'bg-danger', 'percentage_bg' => 'bg-dark'],
        ['title' => 'Top Deals', 'slug' => 'top_deals', 'products' => $top_deals ?? [], 'badge' => 'Hot Deal', 'badge_bg' => 'bg-warning text-dark', 'percentage_bg' => 'bg-danger'],
        ['title' => 'High Discount', 'slug' => 'high_discount', 'products' => $high_discount ?? [], 'badge' => 'Offer', 'badge_bg' => 'bg-primary', 'percentage_bg' => 'bg-danger'],
        ['title' => 'Top Sales', 'slug' => 'top_sales', 'products' => $top_sales ?? [], 'badge' => 'Best Seller', 'badge_bg' => 'bg-success', 'percentage_bg' => 'bg-danger'],
        ['title' => 'Top Rated', 'slug' => 'top_rated', 'products' => $top_rated ?? [], 'badge' => 'Top Rated', 'badge_bg' => 'bg-warning text-dark', 'percentage_bg' => 'bg-info text-dark']
    ];
    ?>

    <?php foreach ($all_collections as $collection): ?>
        <div class="container-fluid px-2 px-md-4 my-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-bold text-dark mb-0 fs-4"><?= htmlspecialchars($collection['title']); ?></h3>
                <a href="collection.php?name=<?= urlencode($collection['slug']); ?>" class="btn btn-outline-success btn-sm fw-semibold">
                    View All
                </a>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-4 g-2 g-md-3">
                <?php if (!empty($collection['products'])): ?>
                    <?php foreach ($collection['products'] as $product): ?>
                        <?php
                        $sell_price   = (float)($product['sell_price'] ?? 0);
                        $actual_price = (float)($product['actual_price'] ?? $sell_price);
                        $has_discount = ($sell_price > $actual_price && $sell_price > 0);
                        $discount_percent = $has_discount ? round((($sell_price - $actual_price) / $sell_price) * 100) : 0;
                        $rating        = (float)($product['rating'] ?? 0);
                        $total_reviews = (int)($product['total_reviews'] ?? 0);
                        ?>

                        <div class="col">
                            <div class="card product-card h-100 border rounded-3 position-relative shadow-sm">
                                <!-- Image & Badges -->
                                <div class="product-img-container position-relative overflow-hidden p-2 rounded-top">
                                    <span class="badge <?= $collection['badge_bg']; ?> position-absolute top-0 start-0 m-2">
                                        <?= htmlspecialchars($collection['badge']); ?>
                                    </span>
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="badge <?= $collection['percentage_bg'] ?? 'bg-danger'; ?> position-absolute top-0 end-0 m-2">
                                            <?= $discount_percent; ?>%
                                        </span>
                                    <?php endif; ?>
                                    <img src="<?= base_url('uploads/' . ($product['main_img'] ?: 'placeholder.jpg')); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                                </div>

                                <!-- Card Body -->
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between text-muted small mb-1">
                                            <span class="text-capitalize"><?= htmlspecialchars($product['category_name'] ?? 'General'); ?></span>
                                            <span><?= htmlspecialchars($product['product_code'] ?? ''); ?></span>
                                        </div>
                                        <a href="product-details.php?id=<?= $product['id']; ?>" class="text-decoration-none text-dark fw-bold d-block text-truncate small mb-1">
                                            <?= htmlspecialchars($product['name']); ?>
                                        </a>

                                        <?php if ($total_reviews > 0): ?>
                                            <div class="d-flex align-items-center gap-1 mb-2">
                                                <i class="bi bi-star-fill text-warning fs-7"></i>
                                                <span class="fw-bold small text-dark"><?= number_format($rating, 1); ?></span>
                                                <small class="text-muted fs-7">(<?= $total_reviews; ?>)</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="fw-bold text-success fs-6">৳<?= number_format((float)$actual_price, 0); ?></span>
                                            <?php if ($has_discount): ?>
                                                <span class="text-muted text-decoration-line-through small">৳<?= number_format((float)$sell_price, 0); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-1">
                                            <a href="product-details.php?id=<?= $product['id']; ?>" class="btn btn-outline-secondary btn-sm flex-fill">View</a>
                                            <button type="button" class="btn btn-success btn-sm flex-fill open-options-btn">Add</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overlay Cart Options -->
                                <div class="card-options-overlay position-absolute bottom-0 start-0 end-0 bg-white p-3 shadow border-top rounded-bottom d-none flex-column justify-content-between" style="z-index: 10;">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                        <span class="fw-bold text-dark small">Select Options</span>
                                        <button type="button" class="btn-close close-options-btn style="font-size: 0.65rem;"></button>
                                    </div>

                                    <?php if (!empty($product['size'])): ?>
                                        <div class="mb-2">
                                            <label class="small text-muted d-block mb-1">Size:</label>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php
                                                $sizes = array_map('trim', explode(',', $product['size']));
                                                foreach ($sizes as $index => $size):
                                                    if (!empty($size)):
                                                ?>
                                                        <button type="button" class="btn btn-outline-success btn-sm size-btn py-0 px-2 <?= $index === 0 ? 'active' : ''; ?>">
                                                            <?= htmlspecialchars($size); ?>
                                                        </button>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-2">
                                        <label class="small text-muted d-block mb-1">Quantity:</label>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <button type="button" class="btn btn-outline-secondary minus-btn">-</button>
                                            <input type="text" class="form-control text-center qty-input" value="1" readonly>
                                            <button type="button" class="btn btn-outline-secondary plus-btn">+</button>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn"
                                        data-product-id="<?= $product['id']; ?>"
                                        data-product-code="<?= htmlspecialchars($product['product_code'] ?? ''); ?>">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Footer Section -->
    <footer class="bg-dark text-white-50 pt-5 mt-5">
        <div class="container-fluid px-3 px-md-5">
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-4">
                    <img src="assets/img/logo1.png" alt="Logo" class="rounded bg-white p-1 mb-3" style="height: 40px;">
                    <p class="small mb-3">AmarShop is your one-stop online shop for the best quality products at affordable prices.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="text-white mb-3 fw-bold">Quick Links</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Shop</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">My Account</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="text-white mb-3 fw-bold">Customer Care</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Return Policy</a></li>
                    </ul>
                </div>

                <div class="col-md-6 col-lg-4">
                    <h6 class="text-white mb-3 fw-bold">Contact & Payment</h6>
                    <p class="small mb-1"><i class="bi bi-geo-alt me-2 text-success"></i>Dhaka, Bangladesh</p>
                    <p class="small mb-1"><i class="bi bi-telephone me-2 text-success"></i>+8801700-000000</p>
                    <p class="small mb-3"><i class="bi bi-envelope me-2 text-success"></i>support@amarshop.com</p>
                </div>
            </div>
        </div>

        <div class="border-top border-secondary py-3 text-center small">
            <p class="mb-0">&copy; <?= date('Y'); ?> AmarShop. All Rights Reserved. Designed by <span class="text-success fw-semibold">Developer Jasim</span></p>
        </div>
    </footer>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" style="width: 380px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold"><i class="bi bi-bag-check text-success me-2"></i>Your Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column justify-content-between">
            <div id="cartAlertMessage"></div>
            <div id="cartItemsContainer" class="flex-grow-1 overflow-auto pe-1"></div>
            <div class="border-top pt-3">
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Subtotal:</span>
                    <span class="fw-bold fs-5 text-success" id="cartSubtotal">৳0.00</span>
                </div>
                <div class="d-grid gap-2">
                    <a href="cart.php" class="btn btn-outline-success">View Cart</a>
                    <a href="checkout.php" class="btn btn-success">Checkout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const isLoggedIn = <?= $is_logged_in ? 'true' : 'false'; ?>;

        function updateCartBadge() {
            fetch('core/get_cart_count.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const badge = document.getElementById('cartBadge');
                        if (badge) badge.innerText = data.cart_count;
                    }
                })
                .catch(err => console.error('Error updating badge:', err));
        }

        function loadCartDetails() {
            fetch('core/get_cart_data.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('cartItemsContainer');
                    const subtotalEl = document.getElementById('cartSubtotal');

                    updateCartBadge();
                    if (subtotalEl) subtotalEl.innerText = '৳' + data.subtotal;

                    if (!data.items || data.items.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 opacity-50"></i>
                                <p class="mt-2 mb-0">Your cart is empty</p>
                            </div>`;
                        return;
                    }

                    let html = '';
                    data.items.forEach(item => {
                        const itemTotal = Math.round(Number(item.price)) * Number(item.quantity);
                        html += `
                            <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                                <img src="uploads/${item.main_img}" class="rounded border" style="width: 55px; height: 55px; object-fit: contain;">
                                <div class="flex-grow-1 px-3">
                                    <h6 class="mb-1 text-truncate fw-semibold small" style="max-width: 160px;">${item.name}</h6>
                                    <div class="d-flex align-items-center gap-2 my-1">
                                        <span class="badge bg-light text-dark border">Qty: ${item.quantity}</span>
                                    </div>
                                    <div class="small fw-bold text-success">৳${itemTotal}</div>
                                </div>
                                <button type="button" class="btn btn-sm text-danger" onclick="removeCartItem(${item.cart_id})">
                                    <i class="bi bi-trash fs-6"></i>
                                </button>
                            </div>`;
                    });

                    container.innerHTML = html;
                })
                .catch(err => console.error('Error loading cart details:', err));
        }

        function removeCartItem(cartId) {
            if (confirm("Are you sure you want to remove this item?")) {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                fetch('core/remove_cart_item.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            loadCartDetails();
                            updateCartBadge();
                        }
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();

            const cartOffcanvasEl = document.getElementById('cartOffcanvas');
            if (cartOffcanvasEl) {
                cartOffcanvasEl.addEventListener('show.bs.offcanvas', loadCartDetails);
            }

            document.querySelectorAll('.open-options-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (!isLoggedIn) {
                        window.location.href = 'auth/login.php?status=warning&message=' + encodeURIComponent('Login first then add to cart');
                        return;
                    }

                    const overlay = this.closest('.product-card').querySelector('.card-options-overlay');
                    document.querySelectorAll('.card-options-overlay').forEach(el => el.classList.add('d-none'));
                    overlay.classList.remove('d-none');
                    overlay.classList.add('d-flex');
                });
            });

            document.querySelectorAll('.close-options-btn').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.card-options-overlay').classList.add('d-none');
                });
            });

            document.querySelectorAll('.card-options-overlay').forEach(overlay => {
                const minusBtn = overlay.querySelector('.minus-btn');
                const plusBtn = overlay.querySelector('.plus-btn');
                const qtyInput = overlay.querySelector('.qty-input');

                if (minusBtn && plusBtn && qtyInput) {
                    minusBtn.addEventListener('click', () => {
                        let val = parseInt(qtyInput.value);
                        if (val > 1) qtyInput.value = val - 1;
                    });
                    plusBtn.addEventListener('click', () => {
                        let val = parseInt(qtyInput.value);
                        if (val < 6) qtyInput.value = val + 1;
                    });
                }
            });

            document.querySelectorAll('.final-add-cart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const overlay = this.closest('.card-options-overlay');
                    const productId = this.getAttribute('data-product-id');
                    const productCode = this.getAttribute('data-product-code') || '';
                    const selectedSizeBtn = overlay.querySelector('.size-btn.active');
                    const size = selectedSizeBtn ? selectedSizeBtn.innerText.trim() : '';
                    const quantity = overlay.querySelector('.qty-input').value;

                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('product_code', productCode);
                    formData.append('size', size);
                    formData.append('quantity', quantity);

                    fetch('core/add_to_cart.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status === 'success') {
                                updateCartBadge();
                                overlay.classList.add('d-none');
                            }
                        });
                });
            });
        });
    </script>
</body>

</html>