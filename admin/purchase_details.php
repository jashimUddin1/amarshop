<?php
require_once __DIR__ . '/auth/config.php';
require_once __DIR__ . '/../core/purchase_admin.php';
require_admin();
ensure_purchase_tables($pdo);

$purchase_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($purchase_id === false || $purchase_id === null || $purchase_id <= 0) {
    http_response_code(400);
    exit('Invalid purchase ID.');
}
$csrf_token = $_SESSION['purchase_details_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['purchase_details_csrf'] = $csrf_token;
$notice = is_array($_SESSION['purchase_details_notice'] ?? null) ? $_SESSION['purchase_details_notice'] : null;
unset($_SESSION['purchase_details_notice']);

if (isset($_GET['created'])) {
    $notice = ['type' => 'success', 'message' => 'Purchase recorded and stock updated.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $csrf)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $pdo->beginTransaction();
        $purchase_stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id FOR UPDATE');
        $purchase_stmt->execute([':id' => $purchase_id]);
        $purchase = $purchase_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$purchase) {
            throw new InvalidArgumentException('Purchase not found.');
        }
        $payments_stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM purchase_payments WHERE purchase_id = :id');
        $payments_stmt->execute([':id' => $purchase_id]);
        $paid_total = (float)$payments_stmt->fetchColumn();
        $returns_stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM purchase_returns WHERE purchase_id = :id');
        $returns_stmt->execute([':id' => $purchase_id]);
        $return_total = (float)$returns_stmt->fetchColumn();

        if ($action === 'add_payment') {
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $method = is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : '';
            $reference = trim((string)($_POST['reference_no'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            $balance = (float)$purchase['total_amount'] - $paid_total - $return_total;
            if ($amount === false || $amount <= 0 || $amount > $balance + 0.001 ||
                !in_array($method, ['cash', 'bank', 'mobile_banking', 'cheque', 'other'], true) ||
                strlen($reference) > 100 || strlen($note) > 500) {
                throw new InvalidArgumentException('Payment must be positive and cannot exceed the supplier balance.');
            }
            $pdo->prepare("
                INSERT INTO purchase_payments
                    (purchase_id, amount, payment_method, reference_no, note, received_by_admin_id)
                VALUES (:purchase, :amount, :method, :reference, :note, :admin_id)
            ")->execute([
                ':purchase' => $purchase_id,
                ':amount' => $amount,
                ':method' => $method,
                ':reference' => $reference !== '' ? $reference : null,
                ':note' => $note !== '' ? $note : null,
                ':admin_id' => (int)$_SESSION['admin_user_id'],
            ]);
            $pdo->commit();
            $_SESSION['purchase_details_notice'] = ['type' => 'success', 'message' => 'Supplier payment recorded.'];
        } elseif ($action === 'create_return') {
            $item_ids = $_POST['purchase_item_id'] ?? [];
            $quantities = $_POST['return_quantity'] ?? [];
            $reason = trim((string)($_POST['reason'] ?? ''));
            if (!is_array($item_ids) || !is_array($quantities) ||
                count($item_ids) === 0 || count($item_ids) > 100 ||
                count($item_ids) !== count($quantities) || strlen($reason) > 500) {
                throw new InvalidArgumentException('Select at least one valid purchase line to return.');
            }
            $return_lines = [];
            $seen_items = [];
            $line_stmt = $pdo->prepare("
                SELECT id, product_id, quantity, returned_quantity, unit_cost
                FROM purchase_items WHERE id = :id AND purchase_id = :purchase FOR UPDATE
            ");
            $product_stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
            foreach ($item_ids as $index => $item_id_input) {
                $item_id = filter_var($item_id_input, FILTER_VALIDATE_INT);
                $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT);
                if ($item_id === false || $item_id === null || $item_id <= 0 ||
                    $quantity === false || $quantity === null || $quantity < 0 || $quantity > 100000) {
                    throw new InvalidArgumentException('Return quantities must be valid non-negative integers.');
                }
                if ($quantity === 0) {
                    continue;
                }
                if (isset($seen_items[$item_id])) {
                    throw new InvalidArgumentException('A purchase line can only appear once in one return.');
                }
                $seen_items[$item_id] = true;
                $line_stmt->execute([':id' => $item_id, ':purchase' => $purchase_id]);
                $line = $line_stmt->fetch(PDO::FETCH_ASSOC);
                if (!$line || $quantity > (int)$line['quantity'] - (int)$line['returned_quantity']) {
                    throw new InvalidArgumentException('A return quantity exceeds the remaining purchased quantity.');
                }
                $product_stmt->execute([':id' => (int)$line['product_id']]);
                $product_stock = $product_stmt->fetchColumn();
                if ($product_stock === false || (int)$product_stock < $quantity) {
                    throw new InvalidArgumentException('Current stock is lower than the quantity requested for return.');
                }
                $return_lines[] = [
                    'purchase_item_id' => $item_id,
                    'product_id' => (int)$line['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => (float)$line['unit_cost'],
                    'line_total' => 0.0,
                    'stock_before' => (int)$product_stock,
                ];
            }
            if (!$return_lines) {
                throw new InvalidArgumentException('Enter a return quantity greater than zero.');
            }
            $returned_gross_stmt = $pdo->prepare("
                SELECT COALESCE(SUM(unit_cost * returned_quantity), 0)
                FROM purchase_items WHERE purchase_id = :id
            ");
            $returned_gross_stmt->execute([':id' => $purchase_id]);
            $previously_returned_gross = (float)$returned_gross_stmt->fetchColumn();
            $subtotal = (float)$purchase['subtotal'];
            $discount = (float)$purchase['discount_amount'];
            $gross_return_total = array_sum(array_map(
                static fn(array $line): float => $line['unit_cost'] * $line['quantity'],
                $return_lines
            ));
            $discount_before = $subtotal > 0
                ? round($discount * $previously_returned_gross / $subtotal, 2)
                : 0.0;
            $discount_after = $subtotal > 0
                ? round($discount * ($previously_returned_gross + $gross_return_total) / $subtotal, 2)
                : 0.0;
            $discount_to_allocate = $discount_after - $discount_before;
            $allocated_discount = 0.0;
            $largest_line_index = 0;
            $largest_line_gross = -1.0;
            foreach ($return_lines as $index => $line) {
                $line_gross = round($line['unit_cost'] * $line['quantity'], 2);
                $return_lines[$index]['discount_share'] = $gross_return_total > 0
                    ? round($discount_to_allocate * ($line_gross / $gross_return_total), 2)
                    : 0.0;
                $allocated_discount += $return_lines[$index]['discount_share'];
                if ($line_gross > $largest_line_gross) {
                    $largest_line_index = $index;
                    $largest_line_gross = $line_gross;
                }
            }
            $return_lines[$largest_line_index]['discount_share'] += round($discount_to_allocate - $allocated_discount, 2);
            foreach ($return_lines as &$line) {
                $line['line_total'] = round(
                    ($line['unit_cost'] * $line['quantity']) - $line['discount_share'],
                    2
                );
            }
            unset($line);
            $return_amount = round(array_sum(array_column($return_lines, 'line_total')), 2);
            $return_code = 'RET-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $pdo->prepare("
                INSERT INTO purchase_returns (return_code, purchase_id, total_amount, reason, created_by_admin_id)
                VALUES (:code, :purchase, :amount, :reason, :admin_id)
            ")->execute([
                ':code' => $return_code,
                ':purchase' => $purchase_id,
                ':amount' => $return_amount,
                ':reason' => $reason !== '' ? $reason : null,
                ':admin_id' => (int)$_SESSION['admin_user_id'],
            ]);
            $return_id = (int)$pdo->lastInsertId();
            $insert_return_item = $pdo->prepare("
                INSERT INTO purchase_return_items
                    (return_id, purchase_item_id, product_id, quantity, unit_cost, line_total)
                VALUES (:return, :purchase_item, :product, :quantity, :unit_cost, :line_total)
            ");
            $update_purchase_item = $pdo->prepare('UPDATE purchase_items SET returned_quantity = returned_quantity + :quantity WHERE id = :id');
            $reduce_stock = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :minimum');
            $movement = $pdo->prepare("
                INSERT INTO inventory_movements
                    (product_id, movement_type, quantity, stock_before, stock_after, reference_type,
                     reference_id, note, admin_user_id)
                VALUES (:product, 'purchase_return', :quantity, :before, :after, 'purchase_return', :reference, :note, :admin_id)
            ");
            foreach ($return_lines as $line) {
                $insert_return_item->execute([
                    ':return' => $return_id,
                    ':purchase_item' => $line['purchase_item_id'],
                    ':product' => $line['product_id'],
                    ':quantity' => $line['quantity'],
                    ':unit_cost' => $line['unit_cost'],
                    ':line_total' => $line['line_total'],
                ]);
                $update_purchase_item->execute([':quantity' => $line['quantity'], ':id' => $line['purchase_item_id']]);
                $reduce_stock->execute([
                    ':quantity' => $line['quantity'],
                    ':id' => $line['product_id'],
                    ':minimum' => $line['quantity'],
                ]);
                if ($reduce_stock->rowCount() !== 1) {
                    throw new InvalidArgumentException('Stock changed during the return. Refresh and retry.');
                }
                $movement->execute([
                    ':product' => $line['product_id'],
                    ':quantity' => -$line['quantity'],
                    ':before' => $line['stock_before'],
                    ':after' => $line['stock_before'] - $line['quantity'],
                    ':reference' => $return_id,
                    ':note' => 'Return ' . $return_code,
                    ':admin_id' => (int)$_SESSION['admin_user_id'],
                ]);
            }
            $pdo->commit();
            $_SESSION['purchase_details_notice'] = ['type' => 'success', 'message' => 'Supplier return ' . $return_code . ' recorded; stock and supplier balance updated.'];
        } else {
            throw new InvalidArgumentException('Invalid purchase action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['purchase_details_notice'] = ['type' => 'warning', 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Purchase update failed: ' . $e->getMessage());
        $_SESSION['purchase_details_notice'] = ['type' => 'danger', 'message' => 'Purchase update could not be saved. Please try again.'];
    }
    header('Location: purchase_details.php?id=' . (int)$purchase_id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone, s.email AS supplier_email, s.address AS supplier_address
    FROM purchases p JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = :id
");
$stmt->execute([':id' => $purchase_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$purchase) {
    http_response_code(404);
    exit('Purchase not found.');
}
$items_stmt = $pdo->prepare('SELECT * FROM purchase_items WHERE purchase_id = :id ORDER BY id');
$items_stmt->execute([':id' => $purchase_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
$payments_stmt = $pdo->prepare('SELECT * FROM purchase_payments WHERE purchase_id = :id ORDER BY paid_at, id');
$payments_stmt->execute([':id' => $purchase_id]);
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
$returns_stmt = $pdo->prepare("
    SELECT r.*, ri.purchase_item_id, ri.quantity, ri.line_total, pi.product_name, pi.product_code, pi.size
    FROM purchase_returns r JOIN purchase_return_items ri ON ri.return_id = r.id
    JOIN purchase_items pi ON pi.id = ri.purchase_item_id
    WHERE r.purchase_id = :id ORDER BY r.created_at DESC, ri.id
");
$returns_stmt->execute([':id' => $purchase_id]);
$returns = $returns_stmt->fetchAll(PDO::FETCH_ASSOC);
$paid_total = array_sum(array_map(static fn(array $payment): float => (float)$payment['amount'], $payments));
$return_total = array_sum(array_map(static fn(array $return): float => (float)$return['total_amount'], array_values(array_reduce($returns, static function (array $carry, array $row): array {
    $carry[(int)$row['id']] = $row;
    return $carry;
}, []))));
$balance = (float)$purchase['total_amount'] - $paid_total - $return_total;
$page_title = 'Purchase Details';
$current_page = 'purchases';
include_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 pt-3 pb-2">
    <div><a class="btn btn-sm btn-outline-secondary mb-2" href="purchases.php">Back to purchases</a><h1 class="h4 fw-bold mb-1">Purchase <?= htmlspecialchars($purchase['purchase_code'], ENT_QUOTES, 'UTF-8'); ?></h1><div class="text-muted"><?= htmlspecialchars($purchase['supplier_name'], ENT_QUOTES, 'UTF-8'); ?> · <?= date('d M Y', strtotime($purchase['purchase_date'])); ?></div></div>
    <button class="btn btn-outline-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print purchase</button>
</div>
<div class="overflow-y-auto px-2 pb-3">
    <?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Purchase total</div><div class="h5 mb-0">৳<?= number_format((float)$purchase['total_amount'], 2); ?></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Paid</div><div class="h5 text-success mb-0">৳<?= number_format($paid_total, 2); ?></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Returns</div><div class="h5 mb-0">৳<?= number_format($return_total, 2); ?></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted"><?= $balance < 0 ? 'Supplier credit' : 'Balance due'; ?></div><div class="h5 <?= $balance > 0 ? 'text-danger' : 'text-success'; ?> mb-0">৳<?= number_format(abs($balance), 2); ?></div></div></div></div>
    </div>
    <div class="card shadow-sm mb-3"><div class="card-header fw-semibold">Purchased products</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Product</th><th>SKU / size</th><th>Purchased</th><th>Returned</th><th>Unit cost</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?><tr><td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars($item['product_code'] . ($item['size'] !== '' ? ' · ' . $item['size'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?= (int)$item['quantity']; ?></td><td><?= (int)$item['returned_quantity']; ?></td><td>৳<?= number_format((float)$item['unit_cost'], 2); ?></td><td>৳<?= number_format((float)$item['line_total'], 2); ?></td></tr><?php endforeach; ?>
        <tr><th colspan="5" class="text-end">Subtotal</th><td>৳<?= number_format((float)$purchase['subtotal'], 2); ?></td></tr>
        <tr><th colspan="5" class="text-end">Discount</th><td>- ৳<?= number_format((float)$purchase['discount_amount'], 2); ?></td></tr>
        <tr><th colspan="5" class="text-end">Other costs</th><td>৳<?= number_format((float)$purchase['extra_cost'], 2); ?></td></tr>
        <tr><th colspan="5" class="text-end">Purchase total</th><th>৳<?= number_format((float)$purchase['total_amount'], 2); ?></th></tr>
        </tbody></table></div></div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Supplier payments</div><div class="card-body">
            <?php if ($balance > 0): ?><form method="post" class="row g-2 mb-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="add_payment">
                <div class="col-sm-4"><label class="form-label" for="paymentAmount">Amount</label><input class="form-control" id="paymentAmount" name="amount" type="number" min="0.01" max="<?= number_format($balance, 2, '.', ''); ?>" step="0.01" required></div>
                <div class="col-sm-4"><label class="form-label" for="paymentMethod">Method</label><select class="form-select" id="paymentMethod" name="payment_method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile_banking">Mobile banking</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                <div class="col-sm-4"><label class="form-label" for="paymentReference">Reference</label><input class="form-control" id="paymentReference" name="reference_no" maxlength="100"></div>
                <div class="col-12"><label class="form-label" for="paymentNote">Note</label><input class="form-control" id="paymentNote" name="note" maxlength="500"></div>
                <div class="col-12"><button class="btn btn-success" type="submit">Record payment</button></div>
            </form><?php elseif ($balance <= 0): ?><div class="alert alert-success">No amount is currently due for this purchase.</div><?php endif; ?>
            <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>Method / reference</th><th>Amount</th><th>Note</th></tr></thead><tbody>
                <?php foreach ($payments as $payment): ?><tr><td><?= date('d M Y, h:i A', strtotime($payment['paid_at'])); ?></td><td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment['payment_method'])) . (!empty($payment['reference_no']) ? ' · ' . $payment['reference_no'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td>৳<?= number_format((float)$payment['amount'], 2); ?></td><td><?= htmlspecialchars($payment['note'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
                <?php if (!$payments): ?><tr><td colspan="4" class="text-center text-muted">No payments recorded.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div></div>
        <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Return products to supplier</div><div class="card-body">
            <?php $returnable_items = array_filter($items, static fn(array $item): bool => (int)$item['quantity'] > (int)$item['returned_quantity']); ?>
            <?php if ($returnable_items): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="create_return">
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Product</th><th>Remaining</th><th>Return qty</th></tr></thead><tbody>
                    <?php foreach ($returnable_items as $item): $remaining = (int)$item['quantity'] - (int)$item['returned_quantity']; ?>
                        <tr><td><?= htmlspecialchars($item['product_name'] . ' · ' . $item['product_code'] . ($item['size'] !== '' ? ' · ' . $item['size'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?= $remaining; ?></td><td><input class="form-control form-control-sm" type="number" name="return_quantity[]" min="0" max="<?= $remaining; ?>" step="1" value="0" required><input type="hidden" name="purchase_item_id[]" value="<?= (int)$item['id']; ?>"></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
                <label class="form-label" for="returnReason">Return reason (optional)</label><input class="form-control mb-3" id="returnReason" name="reason" maxlength="500">
                <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Record this supplier return and reduce current stock?')">Record return &amp; reduce stock</button>
            </form><?php else: ?><div class="text-muted">All purchased quantities have already been returned.</div><?php endif; ?>
        </div></div></div>
    </div>
    <?php if ($returns): ?><div class="card shadow-sm mt-3"><div class="card-header fw-semibold">Return history</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Return</th><th>Product</th><th>Quantity</th><th>Credit</th><th>Reason</th><th>Date</th></tr></thead><tbody>
        <?php foreach ($returns as $return): ?><tr><td><?= htmlspecialchars($return['return_code'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= htmlspecialchars($return['product_name'] . ' · ' . $return['product_code'], ENT_QUOTES, 'UTF-8'); ?></td><td><?= (int)$return['quantity']; ?></td><td>৳<?= number_format((float)$return['line_total'], 2); ?></td><td><?= htmlspecialchars($return['reason'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td><td><?= date('d M Y', strtotime($return['created_at'])); ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div><?php endif; ?>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
