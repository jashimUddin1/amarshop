<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/purchase_admin.php';
require_once __DIR__ . '/../core/reseller.php';
require_admin();
ensure_purchase_tables($pdo);
ensure_reseller_tables($pdo);

$csrf_token = $_SESSION['purchases_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['purchases_csrf'] = $csrf_token;
$notice = is_array($_SESSION['purchases_notice'] ?? null) ? $_SESSION['purchases_notice'] : null;
unset($_SESSION['purchases_notice']);
$active_tab = is_string($_GET['tab'] ?? null) && in_array($_GET['tab'], ['purchases', 'new', 'suppliers'], true)
    ? $_GET['tab']
    : 'purchases';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $csrf)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'add_supplier') {
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            if ($name === '' || strlen($name) > 180 || strlen($phone) > 40 ||
                strlen($email) > 191 || strlen($address) > 5000 ||
                ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                throw new InvalidArgumentException('Enter a supplier name and valid contact details.');
            }
            $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (:name, :phone, :email, :address)');
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':address' => $address !== '' ? $address : null,
            ]);
            $_SESSION['purchases_notice'] = ['type' => 'success', 'message' => 'Supplier added.'];
            header('Location: purchases.php?tab=suppliers');
            exit;
        }

        if ($action === 'toggle_supplier') {
            $supplier_id = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);
            if ($supplier_id === false || $supplier_id === null || $supplier_id <= 0) {
                throw new InvalidArgumentException('Choose a valid supplier.');
            }
            $stmt = $pdo->prepare("
                UPDATE suppliers SET status = IF(status = 'active', 'inactive', 'active')
                WHERE id = :id
            ");
            $stmt->execute([':id' => $supplier_id]);
            if ($stmt->rowCount() !== 1) {
                throw new InvalidArgumentException('Supplier not found.');
            }
            $_SESSION['purchases_notice'] = ['type' => 'success', 'message' => 'Supplier status updated.'];
            header('Location: purchases.php?tab=suppliers');
            exit;
        }

        if ($action !== 'create_purchase') {
            throw new InvalidArgumentException('Invalid purchase action.');
        }
        $supplier_id = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);
        $purchase_date = is_string($_POST['purchase_date'] ?? null) ? $_POST['purchase_date'] : '';
        $supplier_invoice = trim((string)($_POST['supplier_invoice'] ?? ''));
        $discount = filter_var($_POST['discount_amount'] ?? '0', FILTER_VALIDATE_FLOAT);
        $extra_cost = filter_var($_POST['extra_cost'] ?? '0', FILTER_VALIDATE_FLOAT);
        $paid_amount = filter_var($_POST['paid_amount'] ?? '0', FILTER_VALIDATE_FLOAT);
        $payment_method = is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : 'cash';
        $reference_no = trim((string)($_POST['reference_no'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $product_modes = $_POST['product_mode'] ?? [];
        $product_ids = $_POST['product_id'] ?? [];
        $new_product_names = $_POST['new_product_name'] ?? [];
        $new_product_codes = $_POST['new_product_code'] ?? [];
        $reseller_markup_percentages = $_POST['reseller_markup_percent'] ?? [];
        $sell_markup_percentages = $_POST['sell_markup_percent'] ?? [];
        $discount_percentages = $_POST['discount_percent'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_costs = $_POST['unit_cost'] ?? [];
        $valid_date = DateTime::createFromFormat('Y-m-d', $purchase_date);
        $today = new DateTime('today');
        if ($supplier_id === false || $supplier_id === null || $supplier_id <= 0 ||
            !$valid_date || $valid_date->format('Y-m-d') !== $purchase_date || $valid_date > $today ||
            $supplier_invoice === '' || strlen($supplier_invoice) > 100 ||
            $discount === false || $extra_cost === false || $paid_amount === false ||
            $discount < 0 || $extra_cost < 0 || $paid_amount < 0 ||
            !in_array($payment_method, ['cash', 'bank', 'mobile_banking', 'cheque', 'other'], true) ||
            strlen($reference_no) > 100 || strlen($notes) > 5000 ||
            !is_array($product_modes) || !is_array($product_ids) ||
            !is_array($new_product_names) || !is_array($new_product_codes) ||
            !is_array($reseller_markup_percentages) || !is_array($sell_markup_percentages) ||
            !is_array($discount_percentages) ||
            !is_array($quantities) || !is_array($unit_costs) ||
            count($product_modes) === 0 || count($product_modes) > 100 ||
            count($product_modes) !== count($product_ids) ||
            count($product_modes) !== count($new_product_names) ||
            count($product_modes) !== count($new_product_codes) ||
            count($product_modes) !== count($reseller_markup_percentages) ||
            count($product_modes) !== count($sell_markup_percentages) ||
            count($product_modes) !== count($discount_percentages) ||
            count($product_modes) !== count($quantities) ||
            count($product_modes) !== count($unit_costs)) {
            throw new InvalidArgumentException('Review supplier, date, invoice, payment and line item details.');
        }

        $items = [];
        $seen_products = [];
        $seen_new_codes = [];
        foreach ($product_modes as $index => $product_mode) {
            $product_mode = is_string($product_mode) ? $product_mode : '';
            $product_id_input = $product_ids[$index] ?? '';
            $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT);
            $unit_cost = filter_var($unit_costs[$index] ?? null, FILTER_VALIDATE_FLOAT);
            $reseller_markup = filter_var($reseller_markup_percentages[$index] ?? null, FILTER_VALIDATE_FLOAT);
            $sell_markup = filter_var($sell_markup_percentages[$index] ?? null, FILTER_VALIDATE_FLOAT);
            $discount_percent = filter_var($discount_percentages[$index] ?? null, FILTER_VALIDATE_FLOAT);
            if (!in_array($product_mode, ['restock', 'new'], true) ||
                $quantity === false || $quantity === null || $quantity <= 0 || $quantity > 100000 ||
                $unit_cost === false || $unit_cost < 0 || $unit_cost > 99999999.99 ||
                $reseller_markup === false || $sell_markup === false || $discount_percent === false) {
                throw new InvalidArgumentException('Each purchase line needs a product, quantity, unit cost and valid price percentages.');
            }
            $unit_cost = round((float)$unit_cost, 2);
            $prices = reseller_calculate_prices($unit_cost, (float)$reseller_markup, (float)$sell_markup, (float)$discount_percent);
            $item = [
                'mode' => $product_mode,
                'quantity' => $quantity,
                'unit_cost' => $unit_cost,
                'reseller_markup_percent' => (float)$reseller_markup,
                'sell_markup_percent' => (float)$sell_markup,
                'discount_percent' => (float)$discount_percent,
                'reseller_price' => $prices['reseller_price'],
                'sell_price' => $prices['sell_price'],
            ];
            if ($product_mode === 'restock') {
                $product_id = filter_var($product_id_input, FILTER_VALIDATE_INT);
                if ($product_id === false || $product_id === null || $product_id <= 0) {
                    throw new InvalidArgumentException('Enter a valid existing product code for restock.');
                }
                if (isset($seen_products[$product_id])) {
                    throw new InvalidArgumentException('Do not add the same product twice; combine its quantity on one line.');
                }
                $seen_products[$product_id] = true;
                $item['product_id'] = $product_id;
            } else {
                $product_name = trim((string)($new_product_names[$index] ?? ''));
                $product_code = trim((string)($new_product_codes[$index] ?? ''));
                $code_key = strtolower($product_code);
                if ($product_name === '' || strlen($product_name) > 255 ||
                    $product_code === '' || strlen($product_code) > 50) {
                    throw new InvalidArgumentException('Enter a product name and product code for each new product.');
                }
                if (isset($seen_new_codes[$code_key])) {
                    throw new InvalidArgumentException('Each new product must have a unique product code.');
                }
                $seen_new_codes[$code_key] = true;
                $item['product_name'] = $product_name;
                $item['product_code'] = $product_code;
            }
            $items[] = $item;
        }

        $pdo->beginTransaction();
        $supplier_stmt = $pdo->prepare("SELECT id FROM suppliers WHERE id = :id AND status = 'active' FOR UPDATE");
        $supplier_stmt->execute([':id' => $supplier_id]);
        if (!$supplier_stmt->fetchColumn()) {
            throw new InvalidArgumentException('Select an active supplier.');
        }
        $product_stmt = $pdo->prepare("
            SELECT id, product_code, name, size, purchase_price, stock
            FROM products WHERE id = :id AND deleted_at IS NULL FOR UPDATE
        ");
        $existing_code_stmt = $pdo->prepare("
            SELECT id FROM products
            WHERE product_code = :code
            LIMIT 1 FOR UPDATE
        ");
        $insert_product = $pdo->prepare("
            INSERT INTO products
                (product_code, name, category_name, purchase_price, reseller_markup_percent,
                 sell_markup_percent, reseller_price, sell_price, discount_percent,
                 description, size, color, stock, collection_name, created_by)
            VALUES
                (:code, :name, 'General', :purchase_cost, :reseller_markup, :sell_markup,
                 :reseller_price, :sell_price, :discount, '', '', '', 0, '', :created_by)
        ");
        $subtotal = 0.0;
        foreach ($items as &$item) {
            if ($item['mode'] === 'new') {
                $existing_code_stmt->execute([':code' => $item['product_code']]);
                if ($existing_code_stmt->fetchColumn()) {
                    throw new InvalidArgumentException('That product code already exists. Choose Re-stock or enter another code.');
                }
                $insert_product->execute([
                    ':code' => $item['product_code'],
                    ':name' => $item['product_name'],
                    ':purchase_cost' => $item['unit_cost'],
                    ':reseller_markup' => $item['reseller_markup_percent'],
                    ':sell_markup' => $item['sell_markup_percent'],
                    ':reseller_price' => $item['reseller_price'],
                    ':sell_price' => $item['sell_price'],
                    ':discount' => $item['discount_percent'],
                    ':created_by' => (int)($_SESSION['user_id'] ?? 1),
                ]);
                $item['product_id'] = (int)$pdo->lastInsertId();
                $item['size'] = '';
                $item['stock_before'] = 0;
            } else {
                $product_stmt->execute([':id' => $item['product_id']]);
                $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    throw new InvalidArgumentException('A selected product is no longer available.');
                }
                $item['product_code'] = (string)$product['product_code'];
                $item['product_name'] = (string)$product['name'];
                $item['size'] = (string)($product['size'] ?? '');
                $item['stock_before'] = (int)$product['stock'];
            }
            $item['line_total'] = round($item['unit_cost'] * $item['quantity'], 2);
            $subtotal += $item['line_total'];
        }
        unset($item);
        $total_amount = round($subtotal - (float)$discount + (float)$extra_cost, 2);
        if ($discount > $subtotal || $total_amount <= 0 || $paid_amount > $total_amount) {
            throw new InvalidArgumentException('Discount cannot exceed subtotal, total must be positive, and paid amount cannot exceed purchase total.');
        }
        $purchase_code = 'PUR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $insert_purchase = $pdo->prepare("
            INSERT INTO purchases
                (purchase_code, supplier_id, supplier_invoice, purchase_date, subtotal, discount_amount,
                 extra_cost, total_amount, notes, created_by_admin_id)
            VALUES
                (:code, :supplier, :invoice, :date, :subtotal, :discount, :extra, :total, :notes, :admin_id)
        ");
        $insert_purchase->execute([
            ':code' => $purchase_code,
            ':supplier' => $supplier_id,
            ':invoice' => $supplier_invoice,
            ':date' => $purchase_date,
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':extra' => $extra_cost,
            ':total' => $total_amount,
            ':notes' => $notes !== '' ? $notes : null,
            ':admin_id' => (int)$_SESSION['admin_user_id'],
        ]);
        $purchase_id = (int)$pdo->lastInsertId();
        $insert_item = $pdo->prepare("
            INSERT INTO purchase_items
                (purchase_id, product_id, product_code, product_name, size, quantity, unit_cost, line_total)
            VALUES (:purchase, :product, :code, :name, :size, :quantity, :unit_cost, :line_total)
        ");
        $update_stock = $pdo->prepare("
            UPDATE products
            SET stock = stock + :quantity, purchase_price = :cost,
                reseller_markup_percent = :reseller_markup, sell_markup_percent = :sell_markup,
                reseller_price = :reseller_price, sell_price = :sell_price, discount_percent = :discount
            WHERE id = :id
        ");
        $insert_movement = $pdo->prepare("
            INSERT INTO inventory_movements
                (product_id, movement_type, quantity, stock_before, stock_after, reference_type,
                 reference_id, note, admin_user_id)
            VALUES (:product, 'purchase', :quantity, :before, :after, 'purchase', :reference, :note, :admin_id)
        ");
        foreach ($items as $item) {
            $insert_item->execute([
                ':purchase' => $purchase_id,
                ':product' => $item['product_id'],
                ':code' => $item['product_code'],
                ':name' => $item['product_name'],
                ':size' => $item['size'],
                ':quantity' => $item['quantity'],
                ':unit_cost' => $item['unit_cost'],
                ':line_total' => $item['line_total'],
            ]);
            $stock_after = $item['stock_before'] + $item['quantity'];
            $update_stock->execute([
                ':quantity' => $item['quantity'],
                ':cost' => $item['unit_cost'],
                ':reseller_markup' => $item['reseller_markup_percent'],
                ':sell_markup' => $item['sell_markup_percent'],
                ':reseller_price' => $item['reseller_price'],
                ':sell_price' => $item['sell_price'],
                ':discount' => $item['discount_percent'],
                ':id' => $item['product_id'],
            ]);
            $insert_movement->execute([
                ':product' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':before' => $item['stock_before'],
                ':after' => $stock_after,
                ':reference' => $purchase_id,
                ':note' => 'Purchase ' . $purchase_code,
                ':admin_id' => (int)$_SESSION['admin_user_id'],
            ]);
        }
        if ($paid_amount > 0) {
            $payment_stmt = $pdo->prepare("
                INSERT INTO purchase_payments
                    (purchase_id, amount, payment_method, reference_no, received_by_admin_id)
                VALUES (:purchase, :amount, :method, :reference, :admin_id)
            ");
            $payment_stmt->execute([
                ':purchase' => $purchase_id,
                ':amount' => $paid_amount,
                ':method' => $payment_method,
                ':reference' => $reference_no !== '' ? $reference_no : null,
                ':admin_id' => (int)$_SESSION['admin_user_id'],
            ]);
        }
        $pdo->commit();
        header('Location: purchase_details.php?id=' . $purchase_id . '&created=1');
        exit;
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $notice = ['type' => 'warning', 'message' => $e->getMessage()];
        $active_tab = $action === 'add_supplier' || $action === 'toggle_supplier' ? 'suppliers' : 'new';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Purchase entry failed: ' . $e->getMessage());
        $notice = ['type' => 'danger', 'message' => 'Purchase could not be saved. Please try again.'];
        $active_tab = 'new';
    }
}

$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE p.purchase_code LIKE :search OR p.supplier_invoice LIKE :search OR s.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
$purchase_stmt = $pdo->prepare("
    SELECT p.*, s.name AS supplier_name,
        COALESCE(pay.paid_total, 0) AS paid_total,
        COALESCE(ret.return_total, 0) AS return_total
    FROM purchases p
    JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN (
        SELECT purchase_id, SUM(amount) AS paid_total FROM purchase_payments GROUP BY purchase_id
    ) pay ON pay.purchase_id = p.id
    LEFT JOIN (
        SELECT purchase_id, SUM(total_amount) AS return_total FROM purchase_returns GROUP BY purchase_id
    ) ret ON ret.purchase_id = p.id
    $where
    ORDER BY p.purchase_date DESC, p.id DESC
    LIMIT 200
");
$purchase_stmt->execute($params);
$purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $pdo->query("
    SELECT s.*,
        COALESCE(p.total_purchase, 0) AS total_purchase,
        COALESCE(r.total_return, 0) AS total_return,
        COALESCE(pay.total_paid, 0) AS total_paid
    FROM suppliers s
    LEFT JOIN (SELECT supplier_id, SUM(total_amount) AS total_purchase FROM purchases GROUP BY supplier_id) p ON p.supplier_id = s.id
    LEFT JOIN (SELECT p.supplier_id, SUM(r.total_amount) AS total_return FROM purchase_returns r JOIN purchases p ON p.id = r.purchase_id GROUP BY p.supplier_id) r ON r.supplier_id = s.id
    LEFT JOIN (SELECT p.supplier_id, SUM(pay.amount) AS total_paid FROM purchase_payments pay JOIN purchases p ON p.id = pay.purchase_id GROUP BY p.supplier_id) pay ON pay.supplier_id = s.id
    ORDER BY s.name
")->fetchAll(PDO::FETCH_ASSOC);
$active_suppliers = array_values(array_filter($suppliers, static fn(array $supplier): bool => $supplier['status'] === 'active'));
$products = $pdo->query("
    SELECT id, product_code, name, size, purchase_price, stock, reseller_markup_percent,
        sell_markup_percent, discount_percent
    FROM products WHERE deleted_at IS NULL ORDER BY name, size
")->fetchAll(PDO::FETCH_ASSOC);
$product_lookup = array_map(static function (array $product): array {
    return [
        'id' => (int)$product['id'],
        'product_code' => (string)$product['product_code'],
        'name' => (string)$product['name'],
        'size' => (string)($product['size'] ?? ''),
        'purchase_price' => (float)$product['purchase_price'],
        'stock' => (int)$product['stock'],
        'reseller_markup_percent' => (float)$product['reseller_markup_percent'],
        'sell_markup_percent' => (float)$product['sell_markup_percent'],
        'discount_percent' => (float)$product['discount_percent'],
    ];
}, $products);
$page_title = 'Product Purchases';
$current_page = 'purchases';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div><h1 class="h4 fw-bold mb-1">Product Purchases</h1><p class="small text-muted mb-0">Purchase stock, record supplier payments and track balances.</p></div>
    <a class="btn btn-success" href="purchases.php?tab=new"><i class="bi bi-bag-plus me-1"></i>New purchase</a>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'purchases' ? 'active' : ''; ?>" href="purchases.php?tab=purchases">Purchase history</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'new' ? 'active' : ''; ?>" href="purchases.php?tab=new">New purchase</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'suppliers' ? 'active' : ''; ?>" href="purchases.php?tab=suppliers">Suppliers</a></li>
    </ul>

    <?php if ($active_tab === 'new'): ?>
        <?php if (!$active_suppliers): ?><div class="alert alert-warning">Add an active supplier before recording a purchase. <a href="purchases.php?tab=suppliers">Manage suppliers</a></div><?php endif; ?>
        <form method="post" class="card shadow-sm">
            <div class="card-header fw-semibold">Record received stock</div>
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="create_purchase">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label" for="supplier">Supplier</label><select class="form-select" id="supplier" name="supplier_id" required><option value="">Select supplier</option><?php foreach ($active_suppliers as $supplier): ?><option value="<?= (int)$supplier['id']; ?>"><?= htmlspecialchars($supplier['name'] . ($supplier['phone'] ? ' · ' . $supplier['phone'] : ''), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label" for="purchaseDate">Purchase date</label><input class="form-control" id="purchaseDate" type="date" name="purchase_date" value="<?= date('Y-m-d'); ?>" max="<?= date('Y-m-d'); ?>" required></div>
                    <div class="col-md-5"><label class="form-label" for="supplierInvoice">Supplier invoice / challan no.</label><input class="form-control" id="supplierInvoice" name="supplier_invoice" maxlength="100" required></div>
                </div>
                <div class="table-responsive"><table class="table align-middle" id="purchaseLines">
                    <thead class="table-light"><tr><th style="min-width:300px">Product</th><th>Current stock</th><th style="min-width:110px">Quantity</th><th style="min-width:140px">Unit cost (৳)</th><th>Line total</th><th></th></tr></thead>
                    <tbody id="purchaseLineRows">
                        <tr class="purchase-line">
                            <td>
                                <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Product type">
                                    <button class="btn btn-outline-primary product-mode-btn active" type="button" data-mode="restock" aria-pressed="true">Re-stock</button>
                                    <button class="btn btn-outline-primary product-mode-btn" type="button" data-mode="new" aria-pressed="false">New</button>
                                </div>
                                <input class="product-mode" type="hidden" name="product_mode[]" value="restock">
                                <input class="selected-product-id" type="hidden" name="product_id[]">
                                <div class="restock-fields">
                                    <input class="form-control product-code-input" type="text" list="purchaseProductCodes" maxlength="50" placeholder="Enter product code" autocomplete="off" required aria-label="Existing product code">
                                    <div class="product-match small text-muted mt-1" aria-live="polite"></div>
                                </div>
                                <div class="new-fields d-none">
                                    <input class="form-control new-product-name mb-2" type="text" name="new_product_name[]" maxlength="255" placeholder="Product name" aria-label="New product name">
                                    <input class="form-control new-product-code" type="text" name="new_product_code[]" maxlength="50" placeholder="New product code" aria-label="New product code">
                                </div>
                                <div class="row g-1 mt-2">
                                    <div class="col-4"><label class="form-label small mb-1">Reseller markup %</label><input class="form-control form-control-sm reseller-markup" name="reseller_markup_percent[]" type="number" min="0" max="999.99" step="0.01" placeholder="0" required></div>
                                    <div class="col-4"><label class="form-label small mb-1">Retail markup %</label><input class="form-control form-control-sm sell-markup" name="sell_markup_percent[]" type="number" min="0" max="999.99" step="0.01" placeholder="20" required></div>
                                    <div class="col-4"><label class="form-label small mb-1">Site discount %</label><input class="form-control form-control-sm discount-percent" name="discount_percent[]" type="number" min="0" max="99.99" step="0.01" placeholder="0" required></div>
                                </div>
                            </td>
                            <td class="current-stock text-muted">—</td><td><input class="form-control line-quantity" type="number" name="quantity[]" min="1" step="1" value="1" required></td>
                            <td><input class="form-control line-cost" type="number" name="unit_cost[]" min="0" step="0.01" value="0.00" required></td><td class="line-total">৳0.00</td>
                            <td><button class="btn btn-sm btn-outline-danger remove-line" type="button" aria-label="Remove item" disabled><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table></div>
                <button class="btn btn-sm btn-outline-primary mb-3" id="addPurchaseLine" type="button"><i class="bi bi-plus-lg me-1"></i>Add product</button>
                <div class="row g-3 justify-content-end">
                    <div class="col-md-3"><label class="form-label" for="discountAmount">Discount (৳)</label><input class="form-control purchase-adjustment" id="discountAmount" name="discount_amount" type="number" min="0" step="0.01" value="0"></div>
                    <div class="col-md-3"><label class="form-label" for="extraCost">Other costs (৳)</label><input class="form-control purchase-adjustment" id="extraCost" name="extra_cost" type="number" min="0" step="0.01" value="0"></div>
                    <div class="col-md-3"><label class="form-label" for="paidAmount">Paid now (৳)</label><input class="form-control" id="paidAmount" name="paid_amount" type="number" min="0" step="0.01" value="0" required></div>
                    <div class="col-md-3"><label class="form-label" for="paymentMethod">Payment method</label><select class="form-select" id="paymentMethod" name="payment_method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile_banking">Mobile banking</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    <div class="col-md-4"><label class="form-label" for="referenceNo">Payment reference</label><input class="form-control" id="referenceNo" name="reference_no" maxlength="100"></div>
                    <div class="col-md-8"><label class="form-label" for="purchaseNotes">Notes</label><input class="form-control" id="purchaseNotes" name="notes" maxlength="5000"></div>
                    <div class="col-md-6 text-md-end"><div>Subtotal: <strong id="purchaseSubtotal">৳0.00</strong></div><div>Purchase total: <strong id="purchaseTotal">৳0.00</strong></div></div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-success" type="submit" <?= !$active_suppliers ? 'disabled' : ''; ?>>Save purchase &amp; add stock</button></div>
        </form>
        <template id="purchaseLineTemplate"><tr class="purchase-line">
            <td>
                <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Product type">
                    <button class="btn btn-outline-primary product-mode-btn active" type="button" data-mode="restock" aria-pressed="true">Re-stock</button>
                    <button class="btn btn-outline-primary product-mode-btn" type="button" data-mode="new" aria-pressed="false">New</button>
                </div>
                <input class="product-mode" type="hidden" name="product_mode[]" value="restock">
                <input class="selected-product-id" type="hidden" name="product_id[]">
                <div class="restock-fields">
                    <input class="form-control product-code-input" type="text" list="purchaseProductCodes" maxlength="50" placeholder="Enter product code" autocomplete="off" required aria-label="Existing product code">
                    <div class="product-match small text-muted mt-1" aria-live="polite"></div>
                </div>
                <div class="new-fields d-none">
                    <input class="form-control new-product-name mb-2" type="text" name="new_product_name[]" maxlength="255" placeholder="Product name" aria-label="New product name">
                    <input class="form-control new-product-code" type="text" name="new_product_code[]" maxlength="50" placeholder="New product code" aria-label="New product code">
                </div>
                <div class="row g-1 mt-2">
                    <div class="col-4"><label class="form-label small mb-1">Reseller markup %</label><input class="form-control form-control-sm reseller-markup" name="reseller_markup_percent[]" type="number" min="0" max="999.99" step="0.01" placeholder="0" required></div>
                    <div class="col-4"><label class="form-label small mb-1">Retail markup %</label><input class="form-control form-control-sm sell-markup" name="sell_markup_percent[]" type="number" min="0" max="999.99" step="0.01" placeholder="20" required></div>
                    <div class="col-4"><label class="form-label small mb-1">Site discount %</label><input class="form-control form-control-sm discount-percent" name="discount_percent[]" type="number" min="0" max="99.99" step="0.01" placeholder="0" required></div>
                </div>
            </td>
            <td class="current-stock text-muted">—</td><td><input class="form-control line-quantity" type="number" name="quantity[]" min="1" step="1" value="1" required></td>
            <td><input class="form-control line-cost" type="number" name="unit_cost[]" min="0" step="0.01" value="0.00" required></td><td class="line-total">৳0.00</td>
            <td><button class="btn btn-sm btn-outline-danger remove-line" type="button" aria-label="Remove item"><i class="bi bi-trash"></i></button></td>
        </tr></template>
        <datalist id="purchaseProductCodes">
            <?php foreach ($products as $product): ?>
                <option value="<?= htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </datalist>
    <?php elseif ($active_tab === 'suppliers'): ?>
        <div class="row g-3">
            <div class="col-lg-4"><form method="post" class="card shadow-sm"><div class="card-header fw-semibold">Add supplier</div><div class="card-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="add_supplier">
                <label class="form-label" for="supplierName">Supplier name</label><input class="form-control mb-3" id="supplierName" name="name" maxlength="180" required>
                <label class="form-label" for="supplierPhone">Phone</label><input class="form-control mb-3" id="supplierPhone" name="phone" maxlength="40">
                <label class="form-label" for="supplierEmail">Email</label><input class="form-control mb-3" id="supplierEmail" name="email" type="email" maxlength="191">
                <label class="form-label" for="supplierAddress">Address</label><textarea class="form-control" id="supplierAddress" name="address" rows="3" maxlength="5000"></textarea>
            </div><div class="card-footer text-end"><button class="btn btn-success" type="submit">Save supplier</button></div></form></div>
            <div class="col-lg-8"><div class="card shadow-sm"><div class="card-header fw-semibold">Supplier accounts</div><div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Supplier</th><th>Contact</th><th>Purchases</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead><tbody>
                <?php foreach ($suppliers as $supplier): $balance = (float)$supplier['total_purchase'] - (float)$supplier['total_return'] - (float)$supplier['total_paid']; ?>
                    <tr><td class="fw-semibold"><?= htmlspecialchars($supplier['name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars(trim(($supplier['phone'] ?? '') . (!empty($supplier['email']) ? ' · ' . $supplier['email'] : '')) ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>৳<?= number_format((float)$supplier['total_purchase'], 2); ?></td><td>৳<?= number_format((float)$supplier['total_paid'], 2); ?></td>
                    <td class="<?= $balance > 0 ? 'text-danger' : 'text-success'; ?>">৳<?= number_format(abs($balance), 2); ?> <?= $balance > 0 ? 'due' : ($balance < 0 ? 'credit' : 'settled'); ?></td>
                    <td><span class="badge text-bg-<?= $supplier['status'] === 'active' ? 'success' : 'secondary'; ?>"><?= htmlspecialchars(ucfirst($supplier['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="toggle_supplier"><input type="hidden" name="supplier_id" value="<?= (int)$supplier['id']; ?>"><button class="btn btn-sm btn-outline-secondary"><?= $supplier['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button></form></td></tr>
                <?php endforeach; ?>
                <?php if (!$suppliers): ?><tr><td colspan="7" class="text-center text-muted py-4">No suppliers saved.</td></tr><?php endif; ?>
                </tbody></table></div></div></div>
        </div>
    <?php else: ?>
        <form method="get" class="row g-2 mb-3"><input type="hidden" name="tab" value="purchases"><div class="col"><label class="visually-hidden" for="purchaseSearch">Search purchases</label><input class="form-control" id="purchaseSearch" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search purchase code, supplier invoice or supplier"></div><div class="col-auto"><button class="btn btn-outline-success">Search</button></div></form>
        <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Purchase</th><th>Supplier</th><th>Date</th><th>Total</th><th>Paid</th><th>Returns</th><th>Balance</th><th></th></tr></thead><tbody>
            <?php foreach ($purchases as $purchase): $balance = (float)$purchase['total_amount'] - (float)$purchase['paid_total'] - (float)$purchase['return_total']; ?>
                <tr><td><div class="fw-semibold"><?= htmlspecialchars($purchase['purchase_code'], ENT_QUOTES, 'UTF-8'); ?></div><small class="text-muted"><?= htmlspecialchars($purchase['supplier_invoice'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                <td><?= htmlspecialchars($purchase['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= date('d M Y', strtotime($purchase['purchase_date'])); ?></td>
                <td>৳<?= number_format((float)$purchase['total_amount'], 2); ?></td><td>৳<?= number_format((float)$purchase['paid_total'], 2); ?></td><td>৳<?= number_format((float)$purchase['return_total'], 2); ?></td>
                <td class="<?= $balance > 0 ? 'text-danger' : 'text-success'; ?>">৳<?= number_format(abs($balance), 2); ?> <?= $balance > 0 ? 'due' : ($balance < 0 ? 'credit' : 'paid'); ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="purchase_details.php?id=<?= (int)$purchase['id']; ?>">Details</a></td></tr>
            <?php endforeach; ?>
            <?php if (!$purchases): ?><tr><td colspan="8" class="text-center text-muted py-4">No purchases found.</td></tr><?php endif; ?>
            </tbody></table></div></div>
    <?php endif; ?>
</div>
<?php if ($active_tab === 'new'): ?>
<script>
(() => {
    const body = document.getElementById('purchaseLineRows');
    const template = document.getElementById('purchaseLineTemplate');
    const form = body.closest('form');
    const products = <?= json_encode($product_lookup, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const money = value => `৳${Number(value || 0).toFixed(2)}`;
    const findProduct = code => products.find(product =>
        product.product_code.trim().toLocaleLowerCase() === code.trim().toLocaleLowerCase()
    );
    const updateProduct = (row, setDefaultCost = false) => {
        const mode = row.querySelector('.product-mode').value;
        const productId = row.querySelector('.selected-product-id');
        const stockCell = row.querySelector('.current-stock');
        const match = row.querySelector('.product-match');
        productId.value = '';
        if (mode === 'restock') {
            const codeInput = row.querySelector('.product-code-input');
            const product = findProduct(codeInput.value);
            codeInput.setCustomValidity(codeInput.value.trim() && !product ? 'No product found with this code.' : '');
            if (product) {
                productId.value = product.id;
                stockCell.textContent = product.stock;
                match.textContent = `${product.name}${product.size ? ` · ${product.size}` : ''}`;
                match.classList.remove('text-danger');
                if (setDefaultCost) {
                    row.querySelector('.line-cost').value = Number(product.purchase_price).toFixed(2);
                    row.querySelector('.reseller-markup').value = Number(product.reseller_markup_percent).toFixed(2);
                    row.querySelector('.sell-markup').value = Number(product.sell_markup_percent).toFixed(2);
                    row.querySelector('.discount-percent').value = Number(product.discount_percent).toFixed(2);
                }
            } else {
                stockCell.textContent = '—';
                match.textContent = codeInput.value.trim() ? 'Product code not found.' : '';
                match.classList.toggle('text-danger', Boolean(codeInput.value.trim()));
            }
        } else {
            row.querySelector('.product-code-input').setCustomValidity('');
            row.querySelector('.product-match').textContent = '';
            const name = row.querySelector('.new-product-name').value.trim();
            const code = row.querySelector('.new-product-code').value.trim();
            stockCell.textContent = name && code ? '0 (new)' : '—';
        }
    };
    const setMode = (row, mode) => {
        row.querySelector('.product-mode').value = mode;
        row.querySelector('.restock-fields').classList.toggle('d-none', mode !== 'restock');
        row.querySelector('.new-fields').classList.toggle('d-none', mode !== 'new');
        row.querySelector('.product-code-input').required = mode === 'restock';
        row.querySelector('.new-product-name').required = mode === 'new';
        row.querySelector('.new-product-code').required = mode === 'new';
        row.querySelectorAll('.product-mode-btn').forEach(button => {
            const active = button.dataset.mode === mode;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        updateProduct(row);
    };
    const update = () => {
        let subtotal = 0;
        body.querySelectorAll('.purchase-line').forEach(row => {
            updateProduct(row);
            const qty = Number(row.querySelector('.line-quantity').value || 0);
            const cost = Number(row.querySelector('.line-cost').value || 0);
            const lineTotal = qty * cost;
            row.querySelector('.line-total').textContent = money(lineTotal);
            subtotal += lineTotal;
        });
        const discount = Number(document.getElementById('discountAmount').value || 0);
        const extra = Number(document.getElementById('extraCost').value || 0);
        document.getElementById('purchaseSubtotal').textContent = money(subtotal);
        document.getElementById('purchaseTotal').textContent = money(subtotal - discount + extra);
        body.querySelectorAll('.remove-line').forEach(button => { button.disabled = body.querySelectorAll('.purchase-line').length === 1; });
        const paid = document.getElementById('paidAmount');
        paid.max = Math.max(0, subtotal - discount + extra).toFixed(2);
    };
    document.getElementById('addPurchaseLine').addEventListener('click', () => {
        body.append(template.content.cloneNode(true));
        setMode(body.lastElementChild, 'restock');
        update();
    });
    body.addEventListener('input', event => {
        if (event.target.matches('.product-code-input, .new-product-name, .new-product-code')) {
            updateProduct(event.target.closest('.purchase-line'), event.target.matches('.product-code-input'));
        }
        update();
    });
    body.addEventListener('change', update);
    body.addEventListener('click', event => {
        const modeButton = event.target.closest('.product-mode-btn');
        if (modeButton) {
            setMode(modeButton.closest('.purchase-line'), modeButton.dataset.mode);
            update();
            return;
        }
        const button = event.target.closest('.remove-line');
        if (button && body.querySelectorAll('.purchase-line').length > 1) {
            button.closest('tr').remove();
            update();
        }
    });
    document.querySelectorAll('.purchase-adjustment').forEach(input => input.addEventListener('input', update));
    body.querySelectorAll('.purchase-line').forEach(row => setMode(row, row.querySelector('.product-mode').value));
    form.addEventListener('submit', event => {
        body.querySelectorAll('.purchase-line').forEach(row => updateProduct(row));
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });
    update();
})();
</script>
<?php endif; ?>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
