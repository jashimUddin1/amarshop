<?php
require_once('auth/config.php');

$cart_items = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_stmt = $pdo->prepare("SELECT product_id, size, quantity FROM cart WHERE user_id = :user_id");
    $cart_stmt->execute([':user_id' => $user_id]);

    // product_id কে Key বানিয়ে ডাটা সাজিয়ে নেওয়া
    while ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
        $cart_items[$row['product_id']] = $row;
    }
}

// Header Include
$page_title = "Home";
include('includes/header.php');
?>

<!-- লাইভ সার্চ রেজাল্ট সেকশন (ডিফল্টভাবে হাইড থাকবে) -->
<div id="liveSearchSection" class="container-fluid px-2 px-md-5 my-4" style="display: none;">
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold text-dark mb-0" id="searchResultTitle">Search Results</h3>
            <button type="button" class="btn-close" onclick="clearLiveSearch()"></button>
        </div>
        <!-- প্রোডাক্টগুলো এখানে গ্রিড আকারে দেখাবে -->
        <div id="searchResultGrid" class="row row-cols-2 row-cols-sm-2 row-cols-md-3 py-2 row-cols-lg-4 g-3 g-md-4">
            <!-- জাভাস্ক্রিপ্ট দিয়ে এখানে প্রোডাক্ট কার্ডগুলো আসবে -->
        </div>
    </div>
</div>

<!-- Hero Slider -->
<div id="heroCarousel" class="carousel slide carousel-fade shadow-sm my-3 hide-on-search" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
    </div>

    <div class="carousel-inner" style="background-color: #d1d5db;">
        <!-- Slide 1 -->
        <div class="carousel-item active" data-bs-interval="5000">
            <div class="container-fluid px-2 px-md-5">
                <div class="row align-items-center min-vh-50 py-5 py-md-0 sliderCls">
                    <div class="col-md-6 order-2 order-md-1 px-4 text-center text-md-start">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                            <span class="bg-success text-white px-2 py-1 rounded fs-7 fw-semibold me-2">New</span>
                            <span class="text-muted fw-bold tracking-wide text-uppercase" style="font-size: 0.85rem;">New Collection</span>
                        </div>
                        <h1 class="display-4 fw-bold text-dark lh-sm mb-3">The Great Fashion <br class="d-none d-lg-block">Collection 2026</h1>
                        <p class="text-secondary mb-4">Up To 40% Off Final Sale Items. <br>Caught in the Moment!</p>
                        <a href="collection.php?name=new_arrivals" class="btn btn-success btn-lg px-4 py-2.5 fs-6 shadow-sm border-0 d-inline-flex align-items-center gap-2" style="background-color: var(--brand); border-radius: 4px;">
                            Show Collection <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="col-md-6 order-1 order-md-2 text-center mb-4 mb-md-0">
                        <img src="assets/uploads/model3rbg.png" class="img-fluid" alt="Fashion Collection" style="max-height: 420px; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-item" data-bs-interval="5000">
            <div class="container-fluid px-2 px-md-5">
                <div class="row align-items-center min-vh-50 py-5 py-md-0 sliderCls">
                    <div class="col-md-6 order-1 order-md-1 text-center mb-4 mb-md-0">
                        <img src="assets/uploads/model1rbg.png" class="img-fluid" alt="Trending Gadgets" style="max-height: 420px; object-fit: contain;">
                    </div>
                    <div class="col-md-6 order-2 order-md-2 px-4 text-center text-md-start">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                            <span class="bg-success text-white px-2 py-1 rounded fs-7 fw-semibold me-2">Hot</span>
                            <span class="text-muted fw-bold tracking-wide text-uppercase" style="font-size: 0.85rem;">Trending Now</span>
                        </div>
                        <h1 class="display-4 fw-bold text-dark lh-sm mb-3">Exclusive Premium <br class="d-none d-lg-block">Gadgets Pack</h1>
                        <p class="text-secondary mb-4">Up To 50% Off On Modern Tech Accessories. <br>Grab It Before It's Gone!</p>
                        <a href="collection.php?name=top_sales" class="btn btn-success btn-lg px-4 py-2.5 fs-6 shadow-sm border-0 d-inline-flex align-items-center gap-2" style="background-color: var(--brand); border-radius: 4px;">
                            Shop Collection
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="btn btn-success rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px; background-color: var(--brand); border: none;">
            <i class="bi bi-chevron-left fs-5"></i>
        </span>
    </button>
    <button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="btn btn-success rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px; background-color: var(--brand); border: none;">
            <i class="bi bi-chevron-right fs-5"></i>
        </span>
    </button>
</div>

<?php
// ডাটাবেজ থেকে ক্যাটাগরিগুলো ফেচ করা
try {
    $cat_grid_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $all_db_categories = $cat_grid_stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_categories = count($all_db_categories);

    // হোমপেজে দেখানোর জন্য প্রথম ৬টি ক্যাটাগরি নেওয়া
    $db_top_categories = array_slice($all_db_categories, 0, 6);
} catch (PDOException $e) {
    $db_top_categories = [];
    $total_categories = 0;
}
?>

<!-- Product Category Grid -->
<div class="container-fluid px-2 px-md-5 my-5 hide-on-search">
    <div class="container-fluid px-2 px-md-5 my-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold text-dark mb-1">Top Categories</h2>
                <div class="bg-success mt-2" style="width: 70px; height: 3px;"></div>
            </div>
            <?php if ($total_categories > 6): ?>
                <a href="category_products.php" class="btn btn-outline-success fw-bold px-4 py-2">
                    View All
                </a>
            <?php endif; ?>
        </div>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">
            <?php if (!empty($db_top_categories)): ?>
                <?php foreach ($db_top_categories as $cat_item): ?>
                    <?php
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_name = :cat_name");
                    $count_stmt->execute([':cat_name' => $cat_item['name']]);
                    $product_count = $count_stmt->fetchColumn();

                    $cat_img = 'uploads/categories/' . ($cat_item['category_photo'] ?: 'default-avatar.svg');
                    ?>
                    <div class="col">
                        <a href="category_products.php?category=<?= urlencode($cat_item['name']); ?>" class="text-decoration-none">
                            <div class="category-card text-center h-100 p-2 border rounded bg-white shadow-sm">
                                <div class="category-img-container ratio ratio-1x1 mb-2">
                                    <img src="<?= htmlspecialchars($cat_img); ?>" class="img-fluid rounded object-fit-cover" alt="<?= htmlspecialchars($cat_item['name']); ?>">
                                </div>
                                <div class="card-body p-1">
                                    <span class="category-text mb-0 text-success fw-bold text-truncate d-block"><?= htmlspecialchars($cat_item['name']); ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No categories found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// ১. ডাটাবেজ থেকে সব Collections ফেচ করা
$stmt = $pdo->query("SELECT * FROM collections ORDER BY id ASC");
$db_collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_collections = [];

// ইউআরএল থেকে ক্যাটাগরি ধরা
$selected_category = $_GET['category'] ?? '';

foreach ($db_collections as $col) {
    $slug = $col['slug'];

    // ক্যাটাগরি সিলেক্ট করা থাকলে এবং স্টক ০-এর বেশি হওয়ার শর্তসহ কুয়েরি
    if (!empty($selected_category)) {
        $p_stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :slug AND category_name = :category AND stock > 0 ORDER BY id DESC LIMIT 12");
        $p_stmt->execute([':slug' => $slug, ':category' => $selected_category]);
    } else {
        // শুধু কালেকশন এবং স্টক ০-এর বেশি হওয়ার শর্তসহ কুয়েরি
        $p_stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :slug AND stock > 0 ORDER BY id DESC LIMIT 12");
        $p_stmt->execute([':slug' => $slug]);
    }

    $products = $p_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ২. মূল অ্যারে বিল্ড করা
    $all_collections[] = [
        'title'         => $col['title'],
        'slug'          => $slug,
        'products'      => $products, // ১০০% ডায়নামিক প্রোডাক্ট লিস্ট
        'badge'         => $col['badge'] ?? '',
        'badge_bg'      => $col['badge_bg'] ?? 'bg-danger',
        'percentage_bg' => $col['percentage_bg'] ?? 'bg-dark'
    ];
}
?>

<?php foreach ($all_collections as $collection): ?>
    <!-- এই ডিভটিতে id="col-slug" যোগ করা হয়েছে -->
    <div class="container-fluid hide-on-search px-2 px-md-5 my-5" id="col-<?= htmlspecialchars($collection['slug']); ?>">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($collection['title']); ?></h2>
            <a href="collection.php?name=<?= urlencode($collection['slug']); ?>" class="btn btn-success text-decoration-none">
                View All
            </a>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 py-2 row-cols-lg-4 g-3 g-md-4">
            <?php if (!empty($collection['products'])): ?>
                <?php foreach ($collection['products'] as $product): ?>
                    <?php
                    $sell_price   = (float)($product['sell_price'] ?? 0);
                    $actual_price = (float)($product['actual_price'] ?? $sell_price);
                    $has_discount = ($sell_price > $actual_price && $sell_price > 0);
                    $discount_percent = $has_discount ? round((($sell_price - $actual_price) / $sell_price) * 100) : 0;
                    $rating        = (float)($product['rating'] ?? 0);
                    $total_reviews = (int)($product['total_reviews'] ?? 0);
                    $stock         = (int)($product['stock'] ?? 0);
                    ?>
                    <div class="col">
                        <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">
                            <div class="product-img-container text-center mb-2 position-relative">
                                <span class="product-badge <?= $collection['badge_bg']; ?> text-white position-absolute top-0 start-0 px-2 py-1 rounded small fw-bold">
                                    <?= htmlspecialchars($collection['badge']); ?>
                                </span>
                                <?php if ($discount_percent > 0): ?>
                                    <span class="product-badge <?= $collection['percentage_bg'] ?? 'bg-danger'; ?> text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">
                                        <?= $discount_percent; ?>%
                                    </span>
                                <?php endif; ?>
                                <img src="<?= base_url('uploads/' . ($product['main_img'] ?: 'placeholder.jpg')); ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']); ?>">
                            </div>

                            <div class="product-card-body d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                            <?= htmlspecialchars($product['category_name'] ?? 'General'); ?>
                                        </p>
                                        <p class="product-category text-muted small mb-1 d-none d-md-block text-capitalize">
                                            <?= htmlspecialchars($product['product_code'] ?? ''); ?>
                                        </p>
                                    </div>
                                    <a href="product_details.php?product_id=<?= $product['id']; ?>" class="product-title text-decoration-none text-dark fw-bold d-block text-truncate">
                                        <?= htmlspecialchars($product['name']); ?>
                                    </a>

                                    <?php if ($total_reviews > 0): ?>
                                        <div class="product-rating my-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-star-fill text-warning small"></i>
                                            <span class="fw-bold small text-dark"><?= number_format($rating, 1); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;">(<?= $total_reviews; ?>)</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-2">
                                    <div class="row justify-content-between">
                                        <div class="price-wrapper col-md-6 mb-2 d-flex align-items-center gap-2">
                                            <span class="current-price fw-bold text-success fs-6">৳<?= number_format($actual_price, 2); ?></span>
                                            <?php if ($has_discount): ?>
                                                <span class="old-price text-muted text-decoration-line-through small">৳<?= number_format($sell_price, 2); ?></span>
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
                                        <a href="product_details.php?product_id=<?= $product['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">
                                            <span class="d-none d-md-inline"><i class="bi bi-eye me-1"></i></span> View
                                        </a>

                                        <?php if (isset($cart_items[$product['id']])): ?>
                                            <?php
                                            $in_cart = $cart_items[$product['id']];
                                            $cart_text = '';

                                            // সাইজ থাকলে সাইজ + পরিমাণ দেখাবে, না থাকলে শুধু পরিমাণ
                                            if (!empty($in_cart['size'])) {
                                                $cart_text = htmlspecialchars($in_cart['size']) . ' - ' . $in_cart['quantity'] . ' ps';
                                            } else {
                                                $cart_text = $in_cart['quantity'] . ' ps';
                                            }
                                            ?>
                                            <!-- কার্টে যুক্ত থাকলে এই বাটন দেখাবে (ছবি অনুযায়ী) -->
                                            <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn fw-bold">
                                                <?= $cart_text; ?>
                                            </button>
                                        <?php else: ?>
                                            <!-- কার্টে না থাকলে ডিফল্ট বাটন দেখাবে -->
                                            <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn">
                                                <span class="d-none d-md-inline"><i class="bi bi-cart-plus me-1"></i></span> Add <span class="d-none d-md-inline">to Cart</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Options Overlay -->
                            <div class="card-options-overlay position-absolute bottom-0 end-0 mwt bg-white rounded p-3 shadow d-none flex-column justify-content-between cart_popup" style="z-index: 10;">
                                <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                    <span class="fw-bold text-dark small">Select Options</span>
                                    <button type="button" class="btn-close close-options-btn" style="font-size: 0.7rem;"></button>
                                </div>

                                <?php if (!empty($product['size'])): ?>
                                    <div class="my-2">
                                        <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Size:</label>
                                        <div class="d-flex gap-1 justify-content-start flex-wrap">
                                            <?php
                                            $sizes = array_map('trim', explode(',', $product['size']));
                                            foreach ($sizes as $index => $size):
                                                if (!empty($size)):
                                            ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success size-btn px-2 py-0 fw-bold <?= $index === 0 ? 'active' : ''; ?>">
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
                                    <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Quantity (Max 6):</label>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                        <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px; font-size: 0.8rem;">
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn" data-product-id="<?= $product['id']; ?>" data-product-code="<?= htmlspecialchars($product['product_code'] ?? ''); ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted">No products found in <?= htmlspecialchars($collection['title']); ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('liveSearchInput');
        const searchSection = document.getElementById('liveSearchSection');
        const resultGrid = document.getElementById('searchResultGrid');
        const resultTitle = document.getElementById('searchResultTitle');

        if (!searchInput) return;

        let timeout = null;
        let searchRequestId = 0;
        document.getElementById('searchForm').addEventListener('submit', function(event) {
            event.preventDefault();
            clearTimeout(timeout);
            const query = searchInput.value.trim();
            if (query) {
                fetchProducts(query, '', '');
            } else {
                clearLiveSearch();
            }
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            searchRequestId++;
            let query = this.value.trim();

            if (query.length === 0) {
                clearLiveSearch();
                return;
            }

            timeout = setTimeout(() => {
                fetchProducts(query, '', '');
            }, 300);
        });

        window.fetchProducts = function(search = '', category = '', collection = '') {
            const requestId = ++searchRequestId;
            let url = `search_api.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&collection=${encodeURIComponent(collection)}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Search request failed with status ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (requestId !== searchRequestId) return;

                    if (data.status === 'success' && Array.isArray(data.products)) {
                        displaySearchResults(data.products, search);
                    } else {
                        throw new Error('Search response did not contain a valid product list.');
                    }
                })
                .catch(error => {
                    if (requestId !== searchRequestId) return;
                    console.error('Error fetching search results:', error);
                    document.querySelectorAll('.hide-on-search').forEach(el => el.style.display = 'none');
                    searchSection.style.display = 'block';
                    resultTitle.textContent = 'Search Results';
                    resultGrid.innerHTML = `<div class="col-12 text-center py-4"><p class="text-danger fs-5 mb-0">Search failed. Please try again.</p></div>`;
                });
        }

        function displaySearchResults(products, searchQuery) {
            // স্লাইডার, ক্যাটাগরি গ্রিড এবং অন্যান্য কালেকশন সেকশনগুলো লুকিয়ে ফেলা
            document.querySelectorAll('.hide-on-search').forEach(el => el.style.display = 'none');

            searchSection.style.display = 'block';
            resultTitle.textContent = searchQuery ? `Search Results for "${searchQuery}"` : 'Search Results';

            if (products.length === 0) {
                resultGrid.innerHTML = `<div class="col-12 text-center py-4"><p class="text-muted fs-5 mb-0">কোনো প্রোডাক্ট পাওয়া যায়নি (Nothing found).</p></div>`;
                return;
            }

            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]);

            let html = '';
            products.forEach(product => {
                let actualPrice = parseFloat(product.actual_price || product.sell_price || 0);
                let sellPrice = parseFloat(product.sell_price || 0);
                let hasDiscount = (sellPrice > actualPrice && sellPrice > 0);
                let discountPercent = hasDiscount ? Math.round(((sellPrice - actualPrice) / sellPrice) * 100) : 0;
                let imgSrc = product.main_img ? `uploads/${escapeHtml(product.main_img)}` : 'assets/uploads/placeholder.jpg';
                let stock = parseInt(product.stock || 0);
                let cartQuantity = parseInt(product.cart_quantity || 0);
                let isInCart = cartQuantity > 0;
                let cartText = product.cart_size ?
                    `${escapeHtml(product.cart_size)} - ${cartQuantity} ps` :
                    `${cartQuantity} ps`;

                let sizeHtml = '';
                if (product.size) {
                    let sizes = product.size.split(',').map(s => s.trim()).filter(s => s !== '');
                    if (sizes.length > 0) {
                        sizeHtml = `
                            <div class="my-2">
                                <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Size:</label>
                                <div class="d-flex gap-1 justify-content-start flex-wrap">
                                    ${sizes.map((sz, idx) => `
                                        <button type="button" class="btn btn-sm btn-outline-success size-btn px-2 py-0 fw-bold ${idx === 0 ? 'active' : ''}">
                                            ${escapeHtml(sz)}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }
                }

                html += `
                    <div class="col">
                        <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">
                            <div class="product-img-container text-center mb-2 position-relative">
                                ${discountPercent > 0 ? `<span class="product-badge bg-danger text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">${discountPercent}%</span>` : ''}
                                <img src="${imgSrc}" class="img-fluid rounded" alt="${escapeHtml(product.name)}">
                            </div>
                            <div class="product-card-body d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <p class="product-category text-muted small mb-1 text-capitalize">${escapeHtml(product.category_name || 'General')}</p>
                                        <p class="product-category text-muted small mb-1">${escapeHtml(product.product_code || '')}</p>
                                    </div>
                                    <a href="product_details.php?product_id=${encodeURIComponent(product.id)}" class="product-title text-decoration-none text-dark fw-bold d-block text-truncate">
                                        ${escapeHtml(product.name)}
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="price-wrapper col-md-6 mb-2 d-flex align-items-center gap-2">
                                            <span class="current-price fw-bold text-success fs-6">৳${actualPrice.toFixed(2)}</span>
                                            ${hasDiscount ? `<span class="old-price text-muted text-decoration-line-through small">৳${sellPrice.toFixed(2)}</span>` : ''}
                                        </div>
                                        <div class="col-md-6 text-end">
                                            ${stock > 0 ? `<span class="badge bg-success mb-2">In Stock</span>` : `<span class="badge bg-danger mb-2">Out of Stock</span>`}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between gap-2 mb-2 mx-2">
                                        <a href="product_details.php?product_id=${encodeURIComponent(product.id)}" class="btn btn-outline-secondary w-50 btn-sm">
                                            <i class="bi bi-eye me-1"></i> View
                                        </a>
                                        <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn ${isInCart ? 'fw-bold' : ''}" ${stock <= 0 ? 'disabled' : ''}>
                                            ${isInCart ? cartText : '<span class="d-none d-md-inline"><i class="bi bi-cart-plus me-1"></i></span> Add <span class="d-none d-md-inline">to Cart</span>'}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Options Overlay Popup -->
                            <div class="card-options-overlay position-absolute bottom-0 end-0 mwt bg-white rounded p-3 shadow d-none flex-column justify-content-between cart_popup" style="z-index: 10;">
                                <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                    <span class="fw-bold text-dark small">Select Options</span>
                                    <button type="button" class="btn-close close-options-btn" style="font-size: 0.7rem;"></button>
                                </div>
                                ${sizeHtml}
                                <div class="mb-2">
                                    <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Quantity (Max 6):</label>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                        <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px; font-size: 0.8rem;">
                                        <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn" data-product-id="${escapeHtml(product.id)}" data-product-code="${escapeHtml(product.product_code || '')}">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            resultGrid.innerHTML = html;
        }

        window.clearLiveSearch = function() {
            clearTimeout(timeout);
            searchRequestId++;
            searchSection.style.display = 'none';
            resultGrid.innerHTML = '';
            if (searchInput) searchInput.value = '';
            // আগের মতো সব সেকশনগুলো আবার ফিরিয়ে আনা
            document.querySelectorAll('.hide-on-search').forEach(el => el.style.display = '');
        }

        const initialSearch = new window.URLSearchParams(window.location.search).get('search');
        if (initialSearch && initialSearch.trim()) {
            searchInput.value = initialSearch;
            fetchProducts(initialSearch.trim(), '', '');
        }
    });
</script>

<?php
// Footer Include
include('includes/footer.php');
?>