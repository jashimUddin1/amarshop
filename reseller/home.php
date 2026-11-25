<?php
require_once __DIR__ . '/includes/bootstrap.php';

$search_term = trim(is_string($_GET['search'] ?? null) ? $_GET['search'] : '');
$selected_category = trim(is_string($_GET['category'] ?? null) ? $_GET['category'] : '');
$selected_collection = trim(is_string($_GET['collection'] ?? null) ? $_GET['collection'] : '');
$categories = $pdo->query('SELECT name, category_photo FROM categories ORDER BY id ASC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$collections = $pdo->query('SELECT title, slug, badge, badge_bg FROM collections ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$all_collections = [];

$product_sql = "
    SELECT id, product_code, name, category_name, size, main_img, reseller_price, stock, rating, total_reviews
    FROM products
    WHERE stock > 0 AND reseller_price IS NOT NULL AND deleted_at IS NULL
";
if ($selected_category !== '') {
    $product_sql .= ' AND category_name = :category';
}
if ($search_term !== '') {
    $product_sql .= ' AND (name LIKE :search_name OR product_code LIKE :search_code OR category_name LIKE :search_category)';
}

$product_stmt = $pdo->prepare($product_sql . ' AND collection_name = :collection ORDER BY id DESC LIMIT 12');
foreach ($collections as $collection) {
    if ($selected_collection !== '' && $selected_collection !== (string)$collection['slug']) {
        continue;
    }
    $params = [':collection' => $collection['slug']];
    if ($selected_category !== '') {
        $params[':category'] = $selected_category;
    }
    if ($search_term !== '') {
        $params[':search_name'] = '%' . $search_term . '%';
        $params[':search_code'] = '%' . $search_term . '%';
        $params[':search_category'] = '%' . $search_term . '%';
    }
    $product_stmt->execute($params);
    $collection['products'] = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($collection['products']) {
        $all_collections[] = $collection;
    }
}

if ($selected_collection === '') {
    $other_stmt = $pdo->prepare($product_sql . " AND (collection_name IS NULL OR collection_name = '') ORDER BY id DESC LIMIT 12");
    $other_params = [];
    if ($selected_category !== '') {
        $other_params[':category'] = $selected_category;
    }
    if ($search_term !== '') {
        $other_params[':search_name'] = '%' . $search_term . '%';
        $other_params[':search_code'] = '%' . $search_term . '%';
        $other_params[':search_category'] = '%' . $search_term . '%';
    }
    $other_stmt->execute($other_params);
    $other_products = $other_stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($other_products) {
        $all_collections[] = [
            'title' => 'More products',
            'slug' => 'other',
            'badge' => 'Shop',
            'badge_bg' => 'bg-success',
            'products' => $other_products,
        ];
    }
}

$page_title = 'Home';
$current_page = 'home';
require __DIR__ . '/includes/header.php';
?>
<div class="d-md-none mb-3">
    <form action="home.php" method="get" role="search">
        <?php if ($selected_category !== ''): ?><input type="hidden" name="category" value="<?= reseller_h($selected_category); ?>"><?php endif; ?>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
            <input class="form-control" type="search" name="search" value="<?= reseller_h($search_term); ?>" placeholder="Search products" aria-label="Search products">
            <button class="btn btn-success" type="submit">Search</button>
        </div>
    </form>
</div>

<section class="rounded-3 bg-white border shadow-sm overflow-hidden mb-4">
    <div class="row align-items-center g-0">
        <div class="col-md-7 p-4 p-lg-5">
            <span class="badge text-bg-success mb-2">Reseller storefront</span>
            <h1 class="display-6 fw-bold text-dark mb-2">Find your next best seller</h1>
            <p class="text-secondary mb-0">Browse products at your reseller price, choose your customer selling price, and manage orders from one place.</p>
        </div>
        <div class="col-md-5 d-none d-md-flex justify-content-center align-items-center bg-success-subtle p-4" style="min-height:180px">
            <i class="bi bi-bag-heart text-success" style="font-size:7rem" aria-hidden="true"></i>
        </div>
    </div>
</section>

<?php if ($categories): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h2 class="h4 fw-bold mb-1">Top Categories</h2><div class="bg-success" style="width:60px;height:3px"></div></div>
            <?php if ($selected_category !== '' || $search_term !== ''): ?><a class="btn btn-sm btn-outline-secondary" href="home.php">Clear filters</a><?php endif; ?>
        </div>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
            <?php foreach ($categories as $category): ?>
                <?php
                $category_name = (string)$category['name'];
                $category_image = trim((string)($category['category_photo'] ?? ''));
                ?>
                <div class="col">
                    <a href="home.php?category=<?= rawurlencode($category_name); ?>" class="text-decoration-none">
                        <div class="card h-100 text-center border-0 shadow-sm">
                            <?php if ($category_image !== ''): ?>
                                <img src="../uploads/categories/<?= reseller_h($category_image); ?>" class="card-img-top object-fit-cover" style="aspect-ratio:1/1" alt="<?= reseller_h($category_name); ?>">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light text-success" style="aspect-ratio:1/1"><i class="bi bi-grid fs-1"></i></div>
                            <?php endif; ?>
                            <div class="card-body py-2"><span class="fw-semibold text-success text-truncate d-block"><?= reseller_h($category_name); ?></span></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($selected_category !== '' || $selected_collection !== '' || $search_term !== ''): ?>
    <div class="alert alert-light border d-flex align-items-center justify-content-between">
        <span>
            Showing products<?= $selected_category !== '' ? ' in ' . reseller_h($selected_category) : ''; ?><?= $search_term !== '' ? ' matching “' . reseller_h($search_term) . '”' : ''; ?>
        </span>
        <a href="home.php" class="small text-success">Show all</a>
    </div>
<?php endif; ?>

<?php foreach ($all_collections as $collection): ?>
    <section class="mb-5" id="collection-<?= reseller_h($collection['slug']); ?>">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <h2 class="h4 fw-bold mb-0"><?= reseller_h($collection['title']); ?></h2>
                <?php if (!empty($collection['badge'])): ?>
                    <span class="badge <?= reseller_h($collection['badge_bg'] ?: 'bg-success'); ?>"><?= reseller_h($collection['badge']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($collection['products']): ?>
            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
                <?php foreach ($collection['products'] as $product): ?>
                    <?php
                    $product_id = (int)$product['id'];
                    $image = trim((string)($product['main_img'] ?? ''));
                    $sizes = array_values(array_filter(array_map('trim', explode(',', (string)($product['size'] ?? ''))), static function ($size) {
                        return $size !== '';
                    }));
                    $stock = max(0, (int)$product['stock']);
                    $search_index = strtolower(implode(' ', [
                        (string)$product['name'],
                        (string)$product['product_code'],
                        (string)($product['category_name'] ?? ''),
                    ]));
                    ?>
                    <div class="col product-card" data-search="<?= reseller_h($search_index); ?>">
                        <article class="card reseller-product-card shadow-sm h-100">
                            <div class="position-relative">
                                <?php if ($image !== ''): ?>
                                    <img src="../uploads/<?= reseller_h($image); ?>" class="card-img-top reseller-product-image product-image-preview" alt="<?= reseller_h($product['name']); ?>">
                                <?php else: ?>
                                    <div class="reseller-product-image-placeholder d-flex align-items-center justify-content-center text-secondary"><i class="bi bi-image fs-1"></i></div>
                                <?php endif; ?>
                                <?php if (!empty($collection['badge'])): ?>
                                    <span class="badge <?= reseller_h($collection['badge_bg'] ?: 'bg-success'); ?> position-absolute top-0 start-0 m-2"><?= reseller_h($collection['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column p-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <small class="text-muted text-truncate"><?= reseller_h($product['category_name'] ?: 'General'); ?></small>
                                    <small class="text-muted text-nowrap"><?= reseller_h($product['product_code']); ?></small>
                                </div>
                                <h3 class="h6 fw-bold mt-1 mb-2 text-truncate" title="<?= reseller_h($product['name']); ?>"><?= reseller_h($product['name']); ?></h3>
                                <?php if ((int)($product['total_reviews'] ?? 0) > 0): ?>
                                    <div class="small mb-2"><i class="bi bi-star-fill text-warning"></i> <?= number_format((float)$product['rating'], 1); ?> <span class="text-muted">(<?= (int)$product['total_reviews']; ?>)</span></div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mt-auto mb-3">
                                    <span class="fw-bold fs-5 text-success"><?= reseller_money($product['reseller_price']); ?></span>
                                    <span class="badge text-bg-success">In Stock</span>
                                </div>
                                <button type="button" class="btn btn-outline-success w-100 open-reseller-options">
                                    <i class="bi bi-bag-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                            <div class="reseller-product-options position-absolute bottom-0 end-0 m-2 p-3 rounded-3 shadow d-none flex-column" style="z-index:5;left:.5rem">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <span class="fw-semibold small">Add product</span>
                                    <button type="button" class="btn-close close-reseller-options" aria-label="Close"></button>
                                </div>
                                <?php if ($sizes): ?>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Size</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($sizes as $size_index => $size): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success reseller-size-button <?= $size_index === 0 ? 'active' : ''; ?>" data-size="<?= reseller_h($size); ?>"><?= reseller_h($size); ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <div class="col-5">
                                        <label class="form-label small mb-1">Qty</label>
                                        <input class="form-control form-control-sm reseller-quantity" type="number" min="1" max="<?= min(1000, $stock); ?>" value="1">
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label small mb-1">Your selling price</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">৳</span>
                                            <input class="form-control reseller-selling-price" type="number" min="<?= (int)ceil((float)$product['reseller_price']); ?>" max="99999999" step="1" placeholder="Enter price" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm w-100 mt-3 add-reseller-cart" data-product-id="<?= $product_id; ?>">
                                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No matching products found in this collection.</p>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if (!$all_collections): ?>
    <div class="alert alert-info">No products match your search right now.</div>
<?php endif; ?>

<script>
(() => {
    const csrfToken = <?= json_encode($csrf_token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.querySelectorAll('.open-reseller-options').forEach(button => {
        button.addEventListener('click', () => {
            const options = button.closest('.card').querySelector('.reseller-product-options');
            options.classList.remove('d-none');
            options.classList.add('d-flex');
        });
    });
    document.querySelectorAll('.close-reseller-options').forEach(button => {
        button.addEventListener('click', () => {
            const options = button.closest('.reseller-product-options');
            options.classList.add('d-none');
            options.classList.remove('d-flex');
        });
    });
    document.querySelectorAll('.reseller-size-button').forEach(button => {
        button.addEventListener('click', () => {
            button.parentElement.querySelectorAll('.reseller-size-button').forEach(option => option.classList.remove('active'));
            button.classList.add('active');
        });
    });
    document.querySelectorAll('.add-reseller-cart').forEach(button => {
        button.addEventListener('click', async () => {
            const card = button.closest('.card');
            const priceInput = card.querySelector('.reseller-selling-price');
            const quantityInput = card.querySelector('.reseller-quantity');
            const price = Number(priceInput.value);
            if (!priceInput.value || !priceInput.checkValidity()) {
                priceInput.reportValidity();
                return;
            }
            if (!quantityInput.checkValidity()) {
                quantityInput.reportValidity();
                return;
            }
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('product_id', button.dataset.productId);
            formData.append('size', card.querySelector('.reseller-size-button.active')?.dataset.size || '');
            formData.append('quantity', quantityInput.value);
            formData.append('selling_price', String(price));
            button.disabled = true;
            try {
                const response = await fetch('cart_action.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Could not add this product.');
                }
                const count = document.getElementById('resellerCartCount');
                count.textContent = result.cart_count;
                count.classList.toggle('d-none', result.cart_count < 1);
                const countText = result.cart_count ? `(${result.cart_count})` : '';
                document.getElementById('resellerSidebarCartCount').textContent = countText;
                document.getElementById('resellerMobileCartCount').textContent = countText;
                button.innerHTML = '<i class="bi bi-check2 me-1"></i>Added';
                setTimeout(() => {
                    button.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Add to Cart';
                    const options = card.querySelector('.reseller-product-options');
                    options.classList.add('d-none');
                    options.classList.remove('d-flex');
                }, 900);
            } catch (error) {
                window.alert(error.message);
            } finally {
                button.disabled = false;
            }
        });
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
