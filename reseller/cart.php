<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../core/order_status.php';
require_once __DIR__ . '/../core/order_admin.php';

$shipping_rates = ['inside_dhaka' => 60.00, 'outside_dhaka' => 120.00];
$notice = $notice ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload and try again.');
        }

        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'remove') {
            $remove_id = filter_var($_POST['remove_product_id'] ?? null, FILTER_VALIDATE_INT);
            $remove_size = is_string($_POST['remove_size'] ?? null) ? trim($_POST['remove_size']) : '';
            if ($remove_id === false || $remove_id === null || $remove_id <= 0 ||
                !isset($_SESSION['reseller_cart'][$remove_id][$remove_size])) {
                throw new InvalidArgumentException('The selected cart item could not be found.');
            }
            unset($_SESSION['reseller_cart'][$remove_id][$remove_size]);
            if (empty($_SESSION['reseller_cart'][$remove_id])) {
                unset($_SESSION['reseller_cart'][$remove_id]);
            }
            $_SESSION['reseller_flash'] = ['type' => 'success', 'message' => 'Product removed from cart.'];
            header('Location: cart.php');
            exit;
        }

        if (!in_array($action, ['update_cart', 'checkout'], true)) {
            throw new InvalidArgumentException('Choose an available cart action.');
        }

        $raw_items = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
        if (!$raw_items || count($raw_items) > 100) {
            throw new InvalidArgumentException('Your cart must contain between 1 and 100 products.');
        }
        $selected = [];
        $unique_ids = [];
        foreach ($raw_items as $raw_item) {
            if (!is_array($raw_item)) {
                throw new InvalidArgumentException('Cart item data is invalid.');
            }
            $product_id = filter_var($raw_item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($raw_item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $raw_price = filter_var($raw_item['selling_price'] ?? null, FILTER_VALIDATE_FLOAT);
            $size = is_string($raw_item['size'] ?? null) ? trim($raw_item['size']) : '';
            if ($product_id === false || $product_id === null || $product_id <= 0 ||
                $quantity === false || $quantity === null || $quantity < 1 || $quantity > 1000 ||
                $raw_price === false || $raw_price < 0 || $raw_price > 99999999) {
                throw new InvalidArgumentException('Enter a valid quantity and selling price for every cart item.');
            }
            $key = $product_id . "\0" . $size;
            if (isset($selected[$key])) {
                throw new InvalidArgumentException('A product size appears more than once in your cart.');
            }
            $selected[$key] = [
                'product_id' => (int)$product_id,
                'size' => $size,
                'quantity' => (int)$quantity,
                'selling_price' => (int)round((float)$raw_price),
            ];
            $unique_ids[(int)$product_id] = (int)$product_id;
        }

        if ($action === 'checkout') {
            $customer_name = trim((string)($_POST['customer_name'] ?? ''));
            $customer_phone = trim((string)($_POST['customer_phone'] ?? ''));
            $customer_email = trim((string)($_POST['customer_email'] ?? ''));
            $customer_address = trim((string)($_POST['customer_address'] ?? ''));
            $shipping_area = is_string($_POST['shipping_area'] ?? null) ? $_POST['shipping_area'] : '';
            if ($customer_name === '' || strlen($customer_name) > 180 ||
                $customer_phone === '' || strlen($customer_phone) > 40 ||
                $customer_address === '' || strlen($customer_address) > 5000 ||
                ($customer_email !== '' && (!filter_var($customer_email, FILTER_VALIDATE_EMAIL) || strlen($customer_email) > 191)) ||
                !array_key_exists($shipping_area, $shipping_rates)) {
                throw new InvalidArgumentException('Enter customer details and choose a delivery area before submitting the order.');
            }
            ensure_order_status_history_table($pdo);
            ensure_order_number_sequence_table($pdo);
            $pdo->beginTransaction();
            $sequence_stmt = $pdo->query('SELECT next_number FROM order_number_sequences WHERE id = 1 FOR UPDATE');
            $sequence = (int)$sequence_stmt->fetchColumn();
            if ($sequence < 1) {
                throw new RuntimeException('Order number sequence is unavailable.');
            }
        }

        $placeholders = implode(',', array_fill(0, count($unique_ids), '?'));
        $lock_clause = $action === 'checkout' ? ' FOR UPDATE' : '';
        $product_stmt = $pdo->prepare("
            SELECT id, product_code, name, size, stock, reseller_price
            FROM products
            WHERE id IN ($placeholders) AND deleted_at IS NULL
            $lock_clause
        ");
        $product_stmt->execute(array_values($unique_ids));
        $products = [];
        foreach ($product_stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
            $products[(int)$product['id']] = $product;
        }
        if (count($products) !== count($unique_ids)) {
            throw new InvalidArgumentException('One or more products in your cart are no longer available.');
        }

        $stock_by_product = [];
        foreach ($selected as $key => $line) {
            $product = $products[$line['product_id']];
            $available_sizes = array_values(array_filter(array_map('trim', explode(',', (string)($product['size'] ?? ''))), static function ($value) {
                return $value !== '';
            }));
            if (($available_sizes && !in_array($line['size'], $available_sizes, true)) ||
                (!$available_sizes && $line['size'] !== '')) {
                throw new InvalidArgumentException($product['name'] . ' has an invalid size selection. Remove it and add the product again.');
            }
            $minimum_price = (int)ceil((float)$product['reseller_price']);
            if ((int)$product['stock'] < 1 || $line['selling_price'] < $minimum_price) {
                throw new InvalidArgumentException($product['name'] . ' is unavailable or its selling price is below the current reseller price.');
            }
            $stock_by_product[$line['product_id']] = ($stock_by_product[$line['product_id']] ?? 0) + $line['quantity'];
        }
        foreach ($stock_by_product as $product_id => $quantity) {
            if ($quantity > (int)$products[$product_id]['stock']) {
                throw new InvalidArgumentException($products[$product_id]['name'] . ' does not have enough stock for the requested quantity.');
            }
        }

        if ($action === 'update_cart') {
            $_SESSION['reseller_cart'] = [];
            foreach ($selected as $line) {
                $_SESSION['reseller_cart'][$line['product_id']][$line['size']] = [
                    'quantity' => $line['quantity'],
                    'selling_price' => $line['selling_price'],
                ];
            }
            $_SESSION['reseller_flash'] = ['type' => 'success', 'message' => 'Cart updated.'];
            header('Location: cart.php');
            exit;
        }

        $subtotal = 0.0;
        foreach ($selected as $key => $line) {
            $selected[$key]['product'] = $products[$line['product_id']];
            $selected[$key]['reseller_price'] = (float)$products[$line['product_id']]['reseller_price'];
            $selected[$key]['total'] = $line['selling_price'] * $line['quantity'];
            $subtotal += $selected[$key]['total'];
        }
        $shipping_charge = $shipping_rates[$shipping_area];
        $order_number = str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
        $order_stmt = $pdo->prepare("
            INSERT INTO orders
                (order_number, user_id, reseller_id, fullname, phone, email, address, shipping_area,
                 subtotal, shipping_charge, grand_total, payment_method, order_status, created_at)
            VALUES
                (:order_number, 0, :reseller_id, :fullname, :phone, :email, :address, :shipping_area,
                 :subtotal, :shipping_charge, :grand_total, 'COD', 'processing', NOW())
        ");
        $order_stmt->execute([
            ':order_number' => $order_number,
            ':reseller_id' => $reseller_id,
            ':fullname' => $customer_name,
            ':phone' => $customer_phone,
            ':email' => $customer_email,
            ':address' => $customer_address,
            ':shipping_area' => $shipping_area,
            ':subtotal' => $subtotal,
            ':shipping_charge' => $shipping_charge,
            ':grand_total' => $subtotal + $shipping_charge,
        ]);
        $order_id = (int)$pdo->lastInsertId();
        $item_stmt = $pdo->prepare("
            INSERT INTO order_items
                (order_id, product_id, product_code, size, quantity, price, reseller_price, total_price)
            VALUES (:order_id, :product_id, :product_code, :size, :quantity, :price, :reseller_price, :total_price)
        ");
        $stock_stmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity');
        foreach ($selected as $line) {
            $product = $line['product'];
            $item_stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => (int)$product['id'],
                ':product_code' => $product['product_code'],
                ':size' => $line['size'],
                ':quantity' => $line['quantity'],
                ':price' => $line['selling_price'],
                ':reseller_price' => $line['reseller_price'],
                ':total_price' => $line['total'],
            ]);
        }
        foreach ($stock_by_product as $product_id => $quantity) {
            $stock_stmt->execute([':quantity' => $quantity, ':id' => $product_id]);
            if ($stock_stmt->rowCount() !== 1) {
                throw new RuntimeException('Stock changed while creating the order.');
            }
        }
        $pdo->prepare('UPDATE order_number_sequences SET next_number = :next WHERE id = 1')
            ->execute([':next' => $sequence + 1]);
        $pdo->prepare('INSERT INTO order_status_history (order_id, status) VALUES (:id, :status)')
            ->execute([':id' => $order_id, ':status' => 'processing']);
        $pdo->commit();
        unset($_SESSION['reseller_cart']);
        $_SESSION['reseller_flash'] = ['type' => 'success', 'message' => 'Order #' . $order_number . ' submitted. Commission will be calculated after delivery.'];
        header('Location: sells.php');
        exit;
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $notice = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Reseller cart checkout failed: ' . $e->getMessage());
        $notice = ['type' => 'danger', 'message' => 'The cart action could not be completed. Please try again.'];
    }
}

$session_cart = $_SESSION['reseller_cart'] ?? [];
$cart_product_ids = array_values(array_filter(array_map('intval', array_keys($session_cart)), static function ($id) {
    return $id > 0;
}));
$cart_lines = [];
$removed_unavailable = false;
if ($cart_product_ids) {
    $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT id, product_code, name, size, main_img, stock, reseller_price
        FROM products
        WHERE id IN ($placeholders) AND deleted_at IS NULL
    ");
    $stmt->execute($cart_product_ids);
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $products[(int)$product['id']] = $product;
    }
    foreach ($session_cart as $raw_id => $variants) {
        $product_id = (int)$raw_id;
        if (!isset($products[$product_id])) {
            unset($_SESSION['reseller_cart'][$raw_id]);
            $removed_unavailable = true;
            continue;
        }
        if (!is_array($variants)) {
            continue;
        }
        foreach ($variants as $size => $item) {
            if (!is_array($item) || (int)($item['quantity'] ?? 0) < 1) {
                continue;
            }
            $cart_lines[] = [
                'product' => $products[$product_id],
                'product_id' => $product_id,
                'size' => (string)$size,
                'quantity' => (int)$item['quantity'],
                'selling_price' => (int)($item['selling_price'] ?? 0),
            ];
        }
    }
    $cart_subtotal = 0;
    $estimated_commission = 0;
    foreach ($cart_lines as $line) {
        $cart_subtotal += $line['selling_price'] * $line['quantity'];
        $estimated_commission += max(0, $line['selling_price'] - (float)$line['product']['reseller_price']) * $line['quantity'];
    }
    if ($removed_unavailable && !$notice) {
        $notice = ['type' => 'warning', 'message' => 'Products that are no longer available have been removed from your cart.'];
    }
}

$page_title = 'Cart';
$current_page = 'cart';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 fw-bold mb-1">Reseller Cart</h1><p class="text-muted mb-0">Review quantities and customer selling prices before checkout.</p></div>
    <a class="btn btn-outline-success" href="home.php"><i class="bi bi-arrow-left me-1"></i>Continue shopping</a>
</div>

<?php if (!$cart_lines): ?>
    <div class="card border-0 shadow-sm text-center p-5">
        <i class="bi bi-bag text-success display-4"></i>
        <h2 class="h5 fw-bold mt-3">Your cart is empty</h2>
        <p class="text-muted">Browse products and add them to your reseller cart.</p>
        <a class="btn btn-success align-self-center" href="home.php">Browse products</a>
    </div>
<?php else: ?>
    <form method="post" id="resellerCartForm">
        <input type="hidden" name="csrf_token" value="<?= reseller_h($csrf_token); ?>">
        <div class="card border-0 shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Reseller price</th><th style="min-width:120px">Quantity</th><th style="min-width:190px">Customer selling price</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cart_lines as $index => $line): ?>
                        <?php
                        $product = $line['product'];
                        $image = trim((string)($product['main_img'] ?? ''));
                        $minimum_price = (int)ceil((float)$product['reseller_price']);
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[<?= $index; ?>][product_id]" value="<?= $line['product_id']; ?>">
                                <input type="hidden" name="items[<?= $index; ?>][size]" value="<?= reseller_h($line['size']); ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($image !== ''): ?><img src="../uploads/<?= reseller_h($image); ?>" alt="" class="rounded object-fit-cover" style="width:64px;height:64px"><?php endif; ?>
                                    <div><div class="fw-semibold"><?= reseller_h($product['name']); ?></div><small class="text-muted"><?= reseller_h($product['product_code']); ?><?= $line['size'] !== '' ? ' · Size ' . reseller_h($line['size']) : ''; ?></small></div>
                                </div>
                            </td>
                            <td class="text-success fw-semibold"><?= reseller_money($product['reseller_price']); ?></td>
                            <td><input class="form-control" type="number" name="items[<?= $index; ?>][quantity]" min="1" max="<?= max(1, (int)$product['stock']); ?>" value="<?= $line['quantity']; ?>" required></td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input class="form-control cart-selling-price" type="number" name="items[<?= $index; ?>][selling_price]" min="<?= $minimum_price; ?>" max="99999999" step="1" value="<?= $line['selling_price']; ?>" required>
                                </div>
                                <small class="text-muted">At least <?= reseller_money($product['reseller_price']); ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger remove-cart-item" type="submit" name="action" value="remove" formnovalidate data-product-id="<?= $line['product_id']; ?>" data-size="<?= reseller_h($line['size']); ?>" aria-label="Remove <?= reseller_h($product['name']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr><th colspan="3" class="text-end">Estimated subtotal (delivery excluded)</th><th colspan="2"><?= reseller_money($cart_subtotal); ?></th></tr>
                        <tr><td colspan="3" class="text-end text-muted small">Potential commission if delivered</td><td colspan="2" class="text-success fw-semibold"><?= reseller_money($estimated_commission); ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <input type="hidden" name="remove_product_id" id="removeProductId" value="">
        <input type="hidden" name="remove_size" id="removeSize" value="">
        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-outline-success" name="action" value="update_cart" formnovalidate><i class="bi bi-arrow-repeat me-1"></i>Update cart</button>
        </div>
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Customer delivery information</div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="customerName">Customer name</label><input class="form-control" id="customerName" name="customer_name" maxlength="180" required></div>
                <div class="col-md-6"><label class="form-label" for="customerPhone">Customer phone</label><input class="form-control" id="customerPhone" name="customer_phone" type="tel" maxlength="40" required></div>
                <div class="col-md-6"><label class="form-label" for="customerEmail">Customer email (optional)</label><input class="form-control" id="customerEmail" name="customer_email" type="email" maxlength="191"></div>
                <div class="col-md-6"><label class="form-label" for="shippingArea">Delivery area</label><select class="form-select" id="shippingArea" name="shipping_area" required><option value="">Choose area</option><option value="inside_dhaka">Inside Dhaka (৳60)</option><option value="outside_dhaka">Outside Dhaka (৳120)</option></select></div>
                <div class="col-12"><label class="form-label" for="customerAddress">Delivery address</label><textarea class="form-control" id="customerAddress" name="customer_address" rows="2" maxlength="5000" required></textarea></div>
            </div></div>
        </section>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-muted">Commission = customer selling price minus reseller price, recorded after delivery.</span>
            <button class="btn btn-success btn-lg" type="submit" name="action" value="checkout"><i class="bi bi-send me-1"></i>Submit customer order</button>
        </div>
    </form>
<?php endif; ?>
<script>
document.querySelectorAll('.remove-cart-item').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('removeProductId').value = button.dataset.productId;
        document.getElementById('removeSize').value = button.dataset.size;
        document.querySelectorAll('#resellerCartForm [name^="items["]').forEach(input => input.disabled = true);
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
