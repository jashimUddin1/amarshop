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
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <h3 class="fw-bold mb-4"><i class="bi bi-credit-card text-success me-2"></i>Checkout</h3>

    <form action="core/place_order.php" method="POST">
        <div class="row g-4">
            <!-- Left Side: Delivery Information Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Shipping Address</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user_info['name'] ?? ''); ?>" required>
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

                <!-- Payment Method -->
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Payment Method</h5>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                        <label class="form-check-label fw-semibold" for="cod">
                            Cash on Delivery (COD)
                        </label>
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
                        ?>
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2 me-2">
                                <div class="d-flex align-items-center">
                                    <img src="uploads/<?= $item['main_img']; ?>" class="rounded border me-2" style="width: 45px; height: 45px; object-fit: contain;">
                                    <div>
                                        <h6 class="mb-0 text-truncate" style="max-width: 140px; font-size: 0.85rem;"><?= htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Size: <?= htmlspecialchars($item['size'] ?: 'N/A'); ?> | Qty: <?= $item['quantity']; ?> * <?= $item['price'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-success" style="font-size: 0.85rem;">৳<?= number_format($item_total, 2); ?></span>
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
</script>

</body>
</html>