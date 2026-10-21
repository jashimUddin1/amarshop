<?php
require_once('auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?status=warning&message=' . urlencode('Please login to view your cart.'));
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items from DB
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.size as selected_size, c.quantity, c.product_code,
           p.name, p.main_img, p.size as available_sizes, p.stock,
           ROUND(COALESCE(p.actual_price, p.sell_price)) as price
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id 
    ORDER BY c.id DESC
");
$stmt->execute([':user_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>.product-image-preview { cursor: zoom-in; }</style>
</head>
<body class="bg-light py-5">

<div class="container">
    <h3 class="fw-bold mb-4"><i class="bi bi-cart3 text-success me-2"></i>Shopping Cart</h3>

    <?php if (empty($cart_items)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <h4 class="mt-3 text-secondary">Your cart is empty!</h4>
            <a href="index.php" class="btn btn-success mt-3 align-self-center px-4">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left Side: Cart Items List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-3">
                    <div class="divide-y">
                        <?php foreach ($cart_items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                                <!-- Product Image -->
                                <img src="uploads/<?= htmlspecialchars($item['main_img']); ?>" class="rounded border product-image-preview" style="width: 75px; height: 75px; object-fit: contain;" alt="<?= htmlspecialchars($item['name']); ?>">
                                
                                <!-- Details (3 Rows Layout) -->
                                <div class="flex-grow-1 px-3">
                                    <!-- Row 1: Name & Code -->
                                    <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted d-block mb-1">Code: <?= htmlspecialchars($item['product_code']); ?></small>
                                    
                                    <!-- Row 2: Size & Quantity Selection -->
                                    <div class="d-flex align-items-center gap-3 my-2">
                                        <?php if (!empty($item['available_sizes'])): 
                                            $sizes = array_map('trim', explode(',', $item['available_sizes']));
                                        ?>
                                            <select class="form-select form-select-sm pe-4" style="width: auto; font-size: 0.8rem;" onchange="updateCartSize(<?= (int)$item['cart_id']; ?>, this.value)">
                                                <?php foreach ($sizes as $sz): ?>
                                                    <option value="<?= htmlspecialchars($sz, ENT_QUOTES, 'UTF-8'); ?>" <?= $sz === $item['selected_size'] ? 'selected' : ''; ?>>Size: <?= htmlspecialchars($sz, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>

                                        <div class="input-group input-group-sm" style="width: 85px;">
                                            <button class="btn btn-outline-secondary px-2" type="button" onclick="updateCartQuantity(<?= (int)$item['cart_id']; ?>, 'decrease')" <?= (int)$item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                            <input type="text" class="form-control text-center p-0 fw-bold" value="<?= $item['quantity']; ?>" readonly style="font-size: 0.8rem;">
                                            <button class="btn btn-outline-secondary px-2" type="button" onclick="updateCartQuantity(<?= (int)$item['cart_id']; ?>, 'increase')" <?= (int)$item['quantity'] >= min(6, (int)$item['stock']) ? 'disabled' : ''; ?>>+</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Row 3: Total (Price * Quantity) -->
                                    <div class="mt-1" style="font-size: 0.85rem;">
                                        <span class="fw-bold text-success">Total: ৳<?= number_format((float)$item_total, 0); ?></span>
                                        <small class="text-muted ms-1">(৳<?= number_format((float)$item['price'], 0); ?> × <?= $item['quantity']; ?>)</small>
                                    </div>
                                </div>

                                <!-- Delete Action -->
                                <div>
                                    <button class="btn btn-sm text-danger p-2" onclick="removeCartItem(<?= (int)$item['cart_id']; ?>)" title="Remove Item">
                                        <i class="bi bi-trash fs-4"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold border-bottom pb-2">Order Summary</h5>
                    <div class="d-flex justify-content-between my-2">
                        <span>Subtotal</span>
                        <span class="fw-bold">৳<?= number_format((float)$subtotal, 0); ?></span>
                    </div>
                    <div class="d-flex justify-content-between my-2">
                        <span>Shipping</span>
                        <span class="text-muted" style="font-size: 0.85rem;">Calculated at checkout</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
                        <span>Estimated Total</span>
                        <span class="text-success">৳<?= number_format((float)$subtotal, 0); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-success w-100 fw-bold py-2">Proceed to Checkout</a>
                    <a href="index.php" class="btn btn-link text-secondary text-decoration-none text-center w-100 mt-2">← Continue Shopping</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="productImagePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 py-2">
                <h5 class="modal-title text-white" id="productImagePreviewTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2 p-md-3">
                <img id="productImagePreviewContent" class="img-fluid" alt="" style="max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (event) {
    const target = event.target instanceof Element ? event.target.closest('.product-image-preview') : null;
    if (!target) return;

    const preview = document.getElementById('productImagePreview');
    const previewImage = document.getElementById('productImagePreviewContent');
    const previewTitle = document.getElementById('productImagePreviewTitle');
    if (!preview || !previewImage || !previewTitle) return;

    previewImage.src = target.currentSrc || target.src;
    previewImage.alt = target.alt || 'Product image';
    previewTitle.textContent = target.alt || 'Product image';
    bootstrap.Modal.getOrCreateInstance(preview).show();
});
function showCartUpdateError(message) {
    let alert = document.getElementById('cartUpdateAlert');
    if (!alert) {
        alert = document.createElement('div');
        alert.id = 'cartUpdateAlert';
        alert.className = 'alert alert-warning';
        document.querySelector('.container').prepend(alert);
    }
    alert.textContent = message;
}

function sendCartUpdate(formData) {
    return fetch('core/update_cart.php', { method: 'POST', body: formData })
        .then(async response => {
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Cart update failed. Please try again.');
            }
            location.reload();
        })
        .catch(error => {
            showCartUpdateError(error.message || 'Cart update failed. Please try again.');
        });
}

function updateCartQuantity(cartId, action) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('action', action);
    sendCartUpdate(formData);
}

function updateCartSize(cartId, newSize) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('action', 'update_size');
    formData.append('size', newSize);
    sendCartUpdate(formData);
}

function removeCartItem(cartId) {
    if (confirm("Are you sure you want to remove this item?")) {
        const formData = new FormData();
        formData.append('cart_id', cartId);
        fetch('core/remove_cart_item.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => { if (res.status === 'success') location.reload(); });
    }
}
</script>
</body>
</html>