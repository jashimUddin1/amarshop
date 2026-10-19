<?php
require_once('auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?status=warning&message=' . urlencode('Please login to checkout.'));
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info if available
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$user_stmt->execute([':id' => $user_id]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.size, c.quantity, c.product_code,
           p.name, p.main_img,
           COALESCE(p.actual_price, p.sell_price) as price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id
");
$stmt->execute([':user_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}
$page_title = "Checkout";
include 'includes/header.php'; 
?>


<section id="checkoutSearchSection" class="container-fluid px-2 px-md-5 my-4" style="display: none;">
    <div class="bg-white rounded shadow-sm border p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold text-dark mb-0" id="checkoutSearchTitle">Search Results</h3>
            <button type="button" class="btn-close" aria-label="Close search results" onclick="clearCheckoutSearch()"></button>
        </div>
        <div id="checkoutSearchGrid" class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4"></div>
    </div>
</section>

<div id="checkoutContent">
<div class="container">
    <div class="d-flex justify-content-between">
        <h3 class="fw-bold mb-2 mt-4"><i class="bi bi-credit-card text-success me-2"></i>Checkout</h3>

        <!-- Payment Method -->
                <div class="card border-0 shadow-sm p-2 m-2">
                    <div class="form-check mb-2 mt-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                        <label class="form-check-label fw-semibold" for="cod">
                            Cash on Delivery (COD)
                        </label>
                    </div>
                </div>
    </div>

    <form action="core/place_order.php" method="POST">
        <div class="row g-4">
            <!-- Left Side: Delivery Information Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Shipping Address</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user_info['customar_name'] ?? ''); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user_info['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user_info['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery Area *</label>
                        <select name="shipping_area" id="shippingArea" class="form-select" required onchange="calculateTotal()">
                            <option value="60">Inside Dhaka (৳60)</option>
                            <option value="120">Outside Dhaka (৳120)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Address *</label>
                        <textarea name="address" class="form-control" rows="1" placeholder="House no, Road no, Area..." required><?= htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                    </div>
                </div>

            </div>

            <!-- Right Side: Order Summary -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Your Items</h5>

                    <!-- Items -->
                    <div class="mb-3 overflow-auto" style="max-height: 250px;">
                        <?php foreach ($cart_items as $item):
                            $item_total = $item['price'] * $item['quantity'];
                            $product_label = !empty($item['product_code'])
                                ? $item['product_code'] . ' - ' . $item['name']
                                : $item['name'];
                        ?>
                            <div class="d-flex align-items-start justify-content-between border-bottom py-2 me-2">
                                <div class="d-flex align-items-start flex-grow-1 me-2" style="min-width: 0;">
                                    <img src="uploads/<?= $item['main_img']; ?>" class="rounded border me-2 flex-shrink-0" style="width: 45px; height: 45px; object-fit: contain;">
                                    <div style="min-width: 0; flex: 1;">
                                        <h6 class="mb-0" style="font-size: 0.85rem; white-space: normal; overflow-wrap: anywhere;"><?= htmlspecialchars($product_label); ?></h6>
                                        <small class="text-muted">Size: <?= htmlspecialchars($item['size'] ?: 'N/A'); ?> | Qty: <?= $item['quantity']; ?> * <?= $item['price'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-success text-nowrap flex-shrink-0" style="font-size: 0.85rem;">৳<?= number_format($item_total, 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Financial Summary -->
                    <div class="d-flex justify-content-between my-2">
                        <span>Subtotal</span>
                        <span class="fw-bold">৳<span id="subtotalVal"><?= number_format($subtotal, 2, '.', ''); ?></span></span>
                    </div>
                    <div class="d-flex justify-content-between my-2">
                        <span>Shipping Charge</span>
                        <span class="fw-bold">৳<span id="shippingCharge">60.00</span></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4">
                        <span>Grand Total</span>
                        <span class="text-success">৳<span id="grandTotal">0.00</span></span>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 fs-5">Place Order</button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>

<script>
    function calculateTotal() {
        const subtotal = parseFloat(document.getElementById('subtotalVal').innerText);
        const shippingArea = parseFloat(document.getElementById('shippingArea').value);

        document.getElementById('shippingCharge').innerText = shippingArea.toFixed(2);

        const grandTotal = subtotal + shippingArea;
        document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
    }

    // Initial calculation on page load
    calculateTotal();

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('liveSearchInput');
        const searchForm = document.getElementById('searchForm');
        const checkoutContent = document.getElementById('checkoutContent');
        const searchSection = document.getElementById('checkoutSearchSection');
        const resultGrid = document.getElementById('checkoutSearchGrid');
        const resultTitle = document.getElementById('checkoutSearchTitle');

        if (!searchInput || !searchForm) return;

        let timeout = null;
        let requestId = 0;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]);
        }

        function showSearchResults(products, query) {
            checkoutContent.style.display = 'none';
            searchSection.style.display = 'block';
            resultTitle.textContent = query ? `Search Results for "${query}"` : 'Search Results';

            if (products.length === 0) {
                resultGrid.innerHTML = '<div class="col-12 text-center py-4"><p class="text-muted fs-5 mb-0">কোনো প্রোডাক্ট পাওয়া যায়নি (Nothing found).</p></div>';
                return;
            }

            resultGrid.innerHTML = products.map(product => {
                const id = escapeHtml(product.id);
                const name = escapeHtml(product.name);
                const code = escapeHtml(product.product_code || '');
                const category = escapeHtml(product.category_name || 'General');
                const image = product.main_img
                    ? `uploads/${escapeHtml(product.main_img)}`
                    : 'assets/uploads/placeholder.jpg';
                const price = Number.parseFloat(product.actual_price || product.sell_price || 0);
                const sellPrice = Number.parseFloat(product.sell_price || 0);
                const stock = Number.parseInt(product.stock || 0, 10);
                const quantityInCart = Number.parseInt(product.cart_quantity || 0, 10);
                const discounted = sellPrice > price && sellPrice > 0;
                const discount = discounted ? Math.round(((sellPrice - price) / sellPrice) * 100) : 0;
                const sizes = String(product.size || '').split(',').map(size => size.trim()).filter(Boolean);
                const sizeOptions = sizes.length
                    ? `<div class="my-2">
                            <label class="d-block text-secondary fw-semibold small mb-1">Size:</label>
                            <div class="d-flex gap-1 flex-wrap">
                                ${sizes.map((size, index) => `<button type="button" class="btn btn-sm btn-outline-success size-btn px-2 py-0 fw-bold ${index === 0 ? 'active' : ''}">${escapeHtml(size)}</button>`).join('')}
                            </div>
                       </div>`
                    : '';
                const cartLabel = product.cart_size
                    ? `${escapeHtml(product.cart_size)} - ${quantityInCart} ps`
                    : `${quantityInCart} ps`;

                return `<div class="col">
                    <div class="product-card position-relative border rounded p-2 bg-white shadow-sm h-100">
                        <div class="product-img-container text-center mb-2 position-relative">
                            ${discount > 0 ? `<span class="product-badge bg-danger text-white position-absolute top-0 end-0 px-2 py-1 rounded small fw-bold">${discount}%</span>` : ''}
                            <img src="${image}" class="img-fluid rounded" alt="${name}">
                        </div>
                        <div class="d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-muted small mb-1">${category}</p>
                                    <p class="text-muted small mb-1">${code}</p>
                                </div>
                                <a href="product_details.php?product_id=${encodeURIComponent(product.id)}" class="text-decoration-none text-dark fw-bold d-block text-truncate">${name}</a>
                            </div>
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold text-success">৳${price.toFixed(2)}</span>
                                        ${discounted ? `<small class="text-muted text-decoration-line-through ms-1">৳${sellPrice.toFixed(2)}</small>` : ''}
                                    </div>
                                    <span class="badge ${stock > 0 ? 'bg-success' : 'bg-danger'}">${stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <a href="product_details.php?product_id=${encodeURIComponent(product.id)}" class="btn btn-outline-secondary btn-sm w-50">View</a>
                                    <button type="button" class="btn btn-outline-success btn-sm w-50 open-options-btn ${quantityInCart > 0 ? 'fw-bold' : ''}" ${stock <= 0 ? 'disabled' : ''}>
                                        ${quantityInCart > 0 ? cartLabel : 'Add to Cart'}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-options-overlay position-absolute bottom-0 end-0 bg-white rounded p-3 shadow d-none flex-column justify-content-between" style="z-index: 10; width: 100%;">
                            <div class="d-flex justify-content-between align-items-center pb-1 border-bottom">
                                <span class="fw-bold text-dark small">Select Options</span>
                                <button type="button" class="btn-close close-options-btn" aria-label="Close" style="font-size: 0.7rem;"></button>
                            </div>
                            ${sizeOptions}
                            <div class="mb-2">
                                <label class="d-block text-secondary fw-semibold small mb-1">Quantity (Max 6):</label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border px-2 py-0 minus-btn fw-bold">-</button>
                                    <input type="text" class="form-control form-control-sm text-center p-0 qty-input fw-bold" value="1" readonly style="width: 35px; height: 28px;">
                                    <button type="button" class="btn btn-sm btn-light border px-2 py-0 plus-btn fw-bold">+</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success btn-sm w-100 fw-semibold final-add-cart-btn" data-product-id="${id}" data-product-code="${code}">Add to Cart</button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        function fetchCheckoutProducts(query) {
            const currentRequestId = ++requestId;
            checkoutContent.style.display = 'none';
            searchSection.style.display = 'block';
            resultTitle.textContent = 'Searching...';
            resultGrid.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Searching...</span></div></div>';

            fetch(`search_api.php?search=${encodeURIComponent(query)}&category=&collection=`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Search request failed with status ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (currentRequestId !== requestId) return;
                    if (data.status !== 'success' || !Array.isArray(data.products)) {
                        throw new Error('Search response did not contain a valid product list.');
                    }
                    showSearchResults(data.products, query);
                })
                .catch(error => {
                    if (currentRequestId !== requestId) return;
                    console.error('Error fetching checkout search results:', error);
                    resultTitle.textContent = 'Search Results';
                    resultGrid.innerHTML = '<div class="col-12 text-center py-4"><p class="text-danger fs-5 mb-0">Search failed. Please try again.</p></div>';
                });
        }

        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            clearTimeout(timeout);
            const query = searchInput.value.trim();
            if (query) {
                fetchCheckoutProducts(query);
            } else {
                window.clearCheckoutSearch();
            }
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            requestId++;
            const query = this.value.trim();
            if (!query) {
                window.clearCheckoutSearch();
                return;
            }
            timeout = setTimeout(() => fetchCheckoutProducts(query), 300);
        });

        window.clearCheckoutSearch = function() {
            clearTimeout(timeout);
            requestId++;
            searchSection.style.display = 'none';
            resultGrid.innerHTML = '';
            checkoutContent.style.display = '';
            searchInput.value = '';
        };

        const initialSearch = new URLSearchParams(window.location.search).get('search');
        if (initialSearch && initialSearch.trim()) {
            searchInput.value = initialSearch;
            fetchCheckoutProducts(initialSearch.trim());
        }
    });
</script>

<?php include 'includes/footer.php'; ?>