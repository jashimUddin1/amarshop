    <?php foreach ($all_collections as $collection): ?>
        <!-- Collection Section Start -->
        <div class="container-fluid px-2 px-md-5 my-5">

            <!-- Header: Title & View All Link -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($collection['title']); ?></h2>
                <a href="collection.php?name=<?= urlencode($collection['slug']); ?>" class="btn btn-success text-decoration-none">
                    View All
                </a>
            </div>

            <!-- Products Grid -->
            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                <?php if (!empty($collection['products'])): ?>
                    <?php foreach ($collection['products'] as $product): ?>
                        <?php
                        // ১. প্রাইস ও ডিসকাউন্ট হিসেব
                        $sell_price   = (float)($product['sell_price'] ?? 0);
                        $actual_price = (float)($product['actual_price'] ?? $sell_price);

                        $has_discount = ($sell_price > $actual_price && $sell_price > 0);
                        $discount_percent = 0;
                        if ($has_discount) {
                            $discount_percent = round((($sell_price - $actual_price) / $sell_price) * 100);
                        }

                        // ২. রিভিউ ও রেটিং তথ্য
                        $rating        = (float)($product['rating'] ?? 0);
                        $total_reviews = (int)($product['total_reviews'] ?? 0);
                        ?>

                        <div class="col">
                            <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">

                                <!-- Product Image & Dual Badges (Left & Right) -->
                                <div class="product-img-container text-center mb-2 position-relative">

                                    <!-- বাম পাশে: কালেকশন ব্যাজ (সবসময় দেখাবে) -->
                                    <span class="product-badge <?= $collection['badge_bg']; ?> text-white position-absolute top-0 start-0 px-2 py-1 rounded small fw-bold">
                                        <?= htmlspecialchars($collection['badge']); ?>
                                    </span>

                                    <!-- ডান পাশে: % ডিসকাউন্ট ব্যাজ (ডাইনামিক কালার) -->
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="product-badge <?= $collection['percentage_bg'] ?? 'bg-danger'; ?> text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">
                                            <?= $discount_percent; ?>%
                                        </span>
                                    <?php endif; ?>

                                    <img src="<?= base_url('uploads/' . ($product['main_img'] ?: 'placeholder.jpg')); ?>" class="img-fluid rounded product-image-preview" style="cursor: zoom-in;" alt="<?= htmlspecialchars($product['name']); ?>">
                                </div>

                                <!-- Product Details -->
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
                                        <a href="product-details.php?id=<?= $product['id']; ?>" class="product-title text-decoration-none text-dark fw-bold d-block text-truncate">
                                            <?= htmlspecialchars($product['name']); ?>
                                        </a>

                                        <!-- ডাইনামিক রিভিউ অ্যান্ড রেটিং -->
                                        <?php if ($total_reviews > 0): ?>
                                            <div class="product-rating my-1 d-flex align-items-center gap-1">
                                                <i class="bi bi-star-fill text-warning small"></i>
                                                <span class="fw-bold small text-dark"><?= number_format($rating, 1); ?></span>
                                                <small class="text-muted" style="font-size: 0.75rem;">(<?= $total_reviews; ?>)</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-2">
                                        <!-- ডাইনামিক প্রাইস -->
                                        <div class="price-wrapper mb-2 d-flex align-items-center gap-2">
                                            <span class="current-price fw-bold text-success fs-6">
                                                ৳<?= number_format((float)$actual_price, 0); ?>
                                            </span>
                                            <?php if ($has_discount): ?>
                                                <span class="old-price text-muted text-decoration-line-through small">
                                                    ৳<?= number_format((float)$sell_price, 0); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex justify-content-between gap-2">
                                            <a href="product-details.php?id=<?= $product['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">
                                                <span class="d-none d-md-inline"><i class="bi bi-eye me-1"></i></span> View
                                            </a>

                                            <button type="button" class="btn btn-outline-success w-50 btn-sm open-options-btn">
                                                <span class="d-none d-md-inline"><i class="bi bi-cart-plus me-1"></i></span> Add <span class="d-none d-md-inline">to Cart</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Pop-up Overlay -->
                                <div class="card-options-overlay position-absolute bottom-0 end-0 mwt bg-white rounded p-3 shadow d-none flex-column justify-content-between cart_popup" style="z-index: 10;">

                                    <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                        <span class="fw-bold text-dark small">Select Options</span>
                                        <button type="button" class="btn-close close-options-btn" style="font-size: 0.7rem;"></button>
                                    </div>

                                    <!-- Dynamic Size Selection -->
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

                                    <!-- Quantity Selection -->
                                    <div class="mb-2">
                                        <label class="d-block text-secondary fw-semibold small mb-1" style="font-size: 0.75rem;">Quantity (Max 6):</label>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                            <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px; font-size: 0.8rem;">
                                            <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                        </div>
                                    </div>

                                    <!-- Final Submit Button -->
                                    <button type="button"
                                        class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn"
                                        data-product-id="<?= $product['id']; ?>"
                                        data-product-code="<?= htmlspecialchars($product['product_code'] ?? ''); ?>">
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
        <!-- Collection Section End -->
    <?php endforeach; ?>