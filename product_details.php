<?php
require_once("auth/config.php");

// 1. URL থেকে product_id ফেচ করা
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        die("Error fetching product: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}

// 2. Related Products ফেচ করা
$related_products = [];
if (!empty($product['category_name'])) {
    $stmt_rel = $pdo->prepare("SELECT * FROM products WHERE category_name = :category AND id != :id LIMIT 4");
    $stmt_rel->execute(['category' => $product['category_name'], 'id' => $product_id]);
    $related_products = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Size String থেকে Array তে রূপান্তর করা (যেমন: "M, L, XL" -> ['M', 'L', 'XL'])
$sizes = [];
if (!empty($product['size'])) {
    $sizes = array_filter(array_map('trim', explode(',', $product['size'])));
}
$page_title = $product['name'];
include 'includes/header.php';
?>
<style>
    :root {
        --brand: #198754;
        --brand-dark: #157347;
        --text: #1f2937;
        --muted: #6c757d;
        --border: #dee2e6;
        --bg: #f8f9fa;
    }

    body {
        font-family: Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    .breadcrumb-item a {
        color: var(--muted);
        text-decoration: none;
    }

    .main-img-box {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }

    .main-img-box img {
        max-height: 400px;
        object-fit: contain;
        width: 100%;
    }

    .thumb-img {
        width: 65px;
        height: 65px;
        object-fit: cover;
        border: 1px solid var(--border);
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s;
        background: #fff;
    }

    .thumb-img:hover, .thumb-img.active {
        border-color: var(--brand);
        transform: scale(1.05);
    }

    .product-details {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        border: 1px solid var(--border);
    }

    .product-title {
        font-size: 1.6rem;
        font-weight: 700;
    }

    .price-area .current-price {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--brand);
    }

    .price-area .old-price {
        font-size: 1.2rem;
        color: var(--muted);
        text-decoration: line-through;
        margin-left: 10px;
    }

    .btn-check:checked + .btn-outline-success {
        background-color: var(--brand);
        color: white;
    }

    .qty-input {
        width: 50px;
        text-align: center;
        border: 1px solid var(--border);
    }

    .product-card {
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        overflow: hidden;
        transition: 0.3s;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }
</style>

    <!-- 1. Breadcrumb + Back Button + Product Code -->
    <div class="container my-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item">
                        <a href="category_products.php?category=<?= urlencode($product['category_name']); ?>">
                            <?= htmlspecialchars($product['category_name']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($product['product_code']); ?></li>
                </ol>
            </nav>

            <!-- Back Button -->
            <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                Back
            </button>
        </div>
    </div>

    <!-- Product Single View Section -->
    <section class="container my-4">
        <div class="row g-4">
            <!-- Left Side: Dynamic Gallery -->
            <div class="col-lg-6">
                <div class="main-img-box mb-3">
                    <img id="featuredImg" src="uploads/<?= htmlspecialchars($product['main_img']); ?>" class="img-fluid product-image-preview" style="cursor: zoom-in;" alt="<?= htmlspecialchars($product['name']); ?>">
                </div>

                <!-- 2. Dynamic Thumbnails (Main + Side Images) -->
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <!-- Main Image Thumbnail -->
                    <?php if (!empty($product['main_img'])): ?>
                        <img src="uploads/<?= htmlspecialchars($product['main_img']); ?>" class="thumb-img active" onclick="changeImage(this)">
                    <?php endif; ?>

                    <!-- Side Images Check & Display -->
                    <?php 
                    for ($i = 1; $i <= 5; $i++) {
                        $side_key = 'side_img' . $i;
                        if (!empty($product[$side_key])): ?>
                            <img src="uploads/<?= htmlspecialchars($product[$side_key]); ?>" class="thumb-img" onclick="changeImage(this)">
                        <?php endif;
                    } 
                    ?>
                </div>
            </div>

            <!-- Right Side: Details -->
            <div class="col-lg-6">
                <div class="product-details shadow-sm">
                    
                    <!-- 3. Dynamic Stock Status -->
                    <?php if ((int)$product['stock'] > 0): ?>
                        <span class="badge bg-success mb-2">In Stock </span>
                    <?php else: ?>
                        <span class="badge bg-danger mb-2">Out of Stock</span>
                    <?php endif; ?>

                    <h1 class="product-title mb-2"><?= htmlspecialchars($product['name']); ?></h1>

                    <!-- Price -->
                    <div class="price-area mb-3">
                        <span class="current-price">৳<?= number_format($product['sell_price'], 2); ?></span>
                        <?php if (!empty($product['purchase_price']) && $product['purchase_price'] > $product['sell_price']): ?>
                            <span class="old-price">৳<?= number_format($product['purchase_price'], 2); ?></span>
                        <?php endif; ?>
                    </div>

                    <p class="text-secondary mb-4">
                        <?= htmlspecialchars($product['description'] ?? ''); ?>
                    </p>

                    <hr>

                    <!-- 4. Dynamic Sizes -->
                    <?php if (!empty($sizes)): ?>
                    <div class="mb-3">
                        <label class="fw-bold mb-2 d-block">Select Size:</label>
                        <div class="btn-group flex-wrap" role="group">
                            <?php foreach ($sizes as $index => $sz): ?>
                                <input type="radio" class="btn-check" name="size" id="size_<?= $index; ?>" value="<?= htmlspecialchars($sz); ?>" <?= $index === 0 ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-success px-3 me-1 mb-1 rounded" for="size_<?= $index; ?>"><?= htmlspecialchars($sz); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 5. Quantity Control (Max Limit: 6) -->
                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block">Quantity (Max 6):</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(-1)">-</button>
                            <input type="text" id="qtyInput" value="1" class="form-control qty-input" readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(1)">+</button>
                        </div>
                        <small id="qtyMsg" class="text-danger d-none mt-1">সর্বোচ্চ ৬ টি অর্ডার করতে পারবেন।</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button type="button" id="addToCartBtn" class="btn btn-success btn-lg flex-grow-1" <?= ((int)$product['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus me-2"></i>Add to Cart
                        </button>
                        <button type="button" id="buyNowBtn" class="btn btn-dark btn-lg flex-grow-1" <?= ((int)$product['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="bi bi-lightning-fill me-1"></i>Buy Now
                        </button>
                    </div>
                    <div id="cartActionMessage" class="d-none" role="status"></div>

                    <!-- Meta Information -->
                    <div class="border-top pt-3 fs-7 text-muted">
                        <p class="mb-1"><strong>Product Code:</strong> <?= htmlspecialchars($product['product_code']); ?></p>
                        <p class="mb-0"><strong>Category:</strong> <?= htmlspecialchars($product['category_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Related Products Section + View All Button -->
    <?php if (!empty($related_products)): ?>
    <section class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Related Products</h3>
            <!-- View All Button -->
            <a href="category_products.php?category=<?= urlencode($product['category_name']); ?>" class="btn btn-outline-success">
                View All
            </a>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($related_products as $rel_prod): ?>
            <div class="col">
                <div class="product-card p-2 text-center h-100 d-flex flex-column justify-content-between">
                    <div>
                        <img src="uploads/<?= htmlspecialchars($rel_prod['main_img']); ?>" class="img-fluid rounded mb-2 product-image-preview" style="height: 180px; object-fit: cover; cursor: zoom-in;" alt="<?= htmlspecialchars($rel_prod['name']); ?>">
                        <h6 class="text-truncate"><?= htmlspecialchars($rel_prod['name']); ?></h6>
                        <p class="text-success fw-bold mb-1">৳<?= number_format($rel_prod['sell_price'], 2); ?></p>
                    </div>
                    <a href="product_details.php?product_id=<?= $rel_prod['id']; ?>" class="btn btn-sm btn-outline-success w-100 mt-2">View Product</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <script>
        // Image Switcher
        function changeImage(element) {
            document.getElementById('featuredImg').src = element.src;
            document.querySelectorAll('.thumb-img').forEach(img => img.classList.remove('active'));
            element.classList.add('active');
        }

        // 5. Quantity Switcher (Max Limit 6 Logic)
        function updateQty(change) {
            let qtyInput = document.getElementById('qtyInput');
            let qtyMsg = document.getElementById('qtyMsg');
            let currentQty = parseInt(qtyInput.value);
            let maxQty = 6;
            
            let newQty = currentQty + change;

            if (newQty >= 1 && newQty <= maxQty) {
                qtyInput.value = newQty;
                qtyMsg.classList.add('d-none');
            } else if (newQty > maxQty) {
                qtyMsg.classList.remove('d-none');
            }
        }

        const productPageIsLoggedIn = <?= json_encode($is_logged_in); ?>;
        const addToCartButton = document.getElementById('addToCartBtn');
        const buyNowButton = document.getElementById('buyNowBtn');
        const cartActionMessage = document.getElementById('cartActionMessage');

        async function addProductToCart(buyNow) {
            if (!productPageIsLoggedIn) {
                window.location.href = 'auth/login.php?status=warning&message=' + encodeURIComponent('Login first then add to cart');
                return;
            }

            const formData = new FormData();
            formData.append('product_id', <?= (int)$product['id']; ?>);
            formData.append('product_code', <?= json_encode($product['product_code'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
            formData.append('size', document.querySelector('input[name="size"]:checked')?.value || '');
            formData.append('quantity', document.getElementById('qtyInput').value);

            addToCartButton.disabled = true;
            buyNowButton.disabled = true;
            cartActionMessage.className = 'd-none';

            try {
                const response = await fetch('core/add_to_cart.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    throw new Error(`Add-to-cart request failed with status ${response.status}`);
                }

                const data = await response.json();
                if (data.status === 'success') {
                    if (typeof updateCartBadge === 'function') {
                        updateCartBadge();
                    }
                    if (buyNow) {
                        window.location.href = 'checkout.php';
                        return;
                    }
                    cartActionMessage.className = 'alert alert-success py-2';
                    cartActionMessage.textContent = data.message;
                } else if (data.status === 'info' && buyNow) {
                    window.location.href = 'checkout.php';
                    return;
                } else {
                    cartActionMessage.className = 'alert alert-warning py-2';
                    cartActionMessage.textContent = data.message || 'Could not add the product to your cart.';
                }
            } catch (error) {
                console.error('Error adding product to cart:', error);
                cartActionMessage.className = 'alert alert-danger py-2';
                cartActionMessage.textContent = 'Could not add the product to your cart. Please try again.';
            } finally {
                addToCartButton.disabled = false;
                buyNowButton.disabled = false;
            }
        }

        addToCartButton.addEventListener('click', () => addProductToCart(false));
        buyNowButton.addEventListener('click', () => addProductToCart(true));
    </script>
<?php include 'includes/footer.php'; ?>