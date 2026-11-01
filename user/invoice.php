<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/../core/order_status.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($order_id === false || $order_id === null || $order_id <= 0) {
    http_response_code(400);
    exit('Invalid order ID.');
}

$order_stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$order_stmt->execute([':id' => $order_id, ':user_id' => (int)$_SESSION['user_id']]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.sell_price AS mrp
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
    ORDER BY oi.id ASC
");
$items_stmt->execute([':order_id' => $order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$mrp_total = 0.0;
foreach ($items as $item) {
    $mrp_total += max((float)($item['mrp'] ?? 0), (float)$item['price']) * (int)$item['quantity'];
}
$subtotal = (float)($order['subtotal'] ?? 0);
$discount_total = max(0, $mrp_total - $subtotal);
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$display_order_number = order_shipment_number((string)$order['order_number']);
$page_title = 'Invoice ' . $display_order_number;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($page_title); ?> · AmarShop</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #17212b; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f4; }
        .invoice { width: min(210mm, calc(100% - 32px)); min-height: 270mm; margin: 24px auto; padding: 14mm; background: #fff; box-shadow: 0 2px 14px #00000014; }
        .top, .meta, .parties, .totals { display: flex; justify-content: space-between; gap: 20px; }
        .top { align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #087f79; }
        .brand { color: #087f79; font-size: 30px; font-weight: 800; letter-spacing: -.8px; }
        .brand-note { margin-top: 3px; color: #64748b; font-size: 12px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-family: Georgia, serif; font-size: 27px; font-weight: 500; }
        .invoice-title p { margin: 6px 0 0; color: #64748b; font-size: 12px; }
        .meta { flex-wrap: wrap; padding: 14px 0; border-bottom: 1px solid #cbd5e1; font-size: 12px; }
        .parties { padding: 16px 0; border-bottom: 1px solid #cbd5e1; }
        .party { flex: 1; min-width: 0; }
        .party + .party { padding-left: 20px; border-left: 1px solid #cbd5e1; }
        .party h2 { margin: 0 0 9px; font-size: 13px; }
        .party p { margin: 4px 0; font-size: 12px; line-height: 1.5; overflow-wrap: anywhere; }
        table { width: 100%; margin-top: 22px; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 8px 7px; border: 1px solid #cbd5e1; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .number { text-align: right; white-space: nowrap; }
        .totals { justify-content: flex-end; margin-top: 18px; }
        .totals table { width: min(360px, 100%); margin: 0; }
        .totals td { padding: 7px 9px; }
        .grand-total { color: #087f79; font-weight: 700; }
        .thank-you { margin-top: 46px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center; color: #475569; font-size: 13px; }
        .actions { display: flex; justify-content: center; gap: 10px; margin: 24px auto 0; }
        .actions button { padding: 10px 18px; border: 1px solid #087f79; border-radius: 6px; background: #087f79; color: #fff; cursor: pointer; font-size: 14px; }
        .actions .close { background: #fff; color: #087f79; }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: #fff; }
            .invoice { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .actions { display: none; }
            tr { break-inside: avoid; }
        }
        @media (max-width: 600px) {
            .invoice { width: 100%; min-height: 100vh; margin: 0; padding: 20px; }
            .top, .parties { gap: 12px; }
            .brand { font-size: 25px; }
            .invoice-title h1 { font-size: 22px; }
            table { font-size: 10px; }
            th, td { padding: 6px 4px; }
        }
    </style>
</head>
<body>
<main class="invoice">
    <header class="top">
        <div><div class="brand">AmarShop</div><div class="brand-note">Your order, delivered with care</div></div>
        <div class="invoice-title"><h1>INVOICE</h1><p>Order #<?= $escape($display_order_number); ?></p></div>
    </header>
    <section class="meta" aria-label="Invoice details">
        <span><strong>Order ID:</strong> #<?= $escape($display_order_number); ?></span>
        <span><strong>Invoice No:</strong> <?= $escape($display_order_number); ?></span>
        <span><strong>Order Date:</strong> <?= $escape(!empty($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : 'N/A'); ?></span>
        <span><strong>Invoice Date:</strong> <?= date('d/m/Y'); ?></span>
    </section>
    <p class="brand-note">MRP and discount figures use current catalog prices where historical MRP was not recorded.</p>
    <section class="parties">
        <div class="party">
            <h2>Seller</h2>
            <p><strong>AmarShop</strong></p>
            <p>Customer support: <?= $escape('01950047790'); ?></p>
        </div>
        <div class="party">
            <h2>Billed To</h2>
            <p><strong><?= $escape($order['fullname'] ?? ''); ?></strong></p>
            <?php if (!empty($order['phone'])): ?><p><?= $escape($order['phone']); ?></p><?php endif; ?>
            <?php if (!empty($order['email'])): ?><p><?= $escape($order['email']); ?></p><?php endif; ?>
        </div>
        <div class="party">
            <h2>Deliver To</h2>
            <p><strong><?= $escape($order['fullname'] ?? ''); ?></strong></p>
            <p><?= nl2br($escape($order['address'] ?? '')); ?></p>
        </div>
    </section>
    <table>
        <thead>
            <tr><th>SL No.</th><th>Products</th><th>Product code / size</th><th>Quantity</th><th class="number">MRP</th><th class="number">Discount</th><th class="number">Amount</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
                <?php
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$item['price'];
                $unit_mrp = max((float)($item['mrp'] ?? 0), $unit_price);
                $line_total = (float)($item['total_price'] ?? ($unit_price * $quantity));
                $line_discount = max(0, ($unit_mrp - $unit_price) * $quantity);
                ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= $escape($item['name'] ?? $item['product_code'] ?? 'Product'); ?></td>
                    <td><?= $escape($item['product_code'] ?? 'N/A'); ?><?= !empty($item['size']) ? ' / ' . $escape($item['size']) : ''; ?></td>
                    <td><?= $quantity; ?> × ৳<?= number_format($unit_price, 2); ?></td>
                    <td class="number">৳<?= number_format($unit_mrp * $quantity, 2); ?></td>
                    <td class="number">৳<?= number_format($line_discount, 2); ?></td>
                    <td class="number">৳<?= number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <section class="totals">
        <table>
            <tbody>
                <tr><td>MRP</td><td class="number">৳<?= number_format($mrp_total, 2); ?></td></tr>
                <tr><td>Discount applied</td><td class="number">-৳<?= number_format($discount_total, 2); ?></td></tr>
                <tr><td>Coupon applied</td><td class="number">৳0.00</td></tr>
                <tr><td>Subtotal</td><td class="number">৳<?= number_format($subtotal, 2); ?></td></tr>
                <tr><td>Delivery charge</td><td class="number"><?= (float)($order['shipping_charge'] ?? 0) <= 0 ? 'Free' : '৳' . number_format((float)$order['shipping_charge'], 2); ?></td></tr>
                <tr><td>Rounding off</td><td class="number">৳0.00</td></tr>
                <tr class="grand-total"><td>Amount payable</td><td class="number">৳<?= number_format((float)$order['grand_total'], 2); ?></td></tr>
                <tr><td>Payment method</td><td class="number"><?= $escape($order['payment_method'] ?? 'N/A'); ?></td></tr>
            </tbody>
        </table>
    </section>
    <footer class="thank-you">Thank you for shopping with AmarShop.</footer>
    <div class="actions">
        <button type="button" onclick="window.print()">Download / Print Invoice</button>
        <button type="button" class="close" onclick="window.close()">Close</button>
    </div>
</main>
<script>
window.addEventListener('load', () => window.setTimeout(() => window.print(), 200));
</script>
</body>
</html>
