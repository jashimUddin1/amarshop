<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ইউজারের সব অর্ডারের পেমেন্ট সম্পর্কিত ডাটা ফেচ
$stmt = $pdo->prepare("SELECT order_number, grand_total, payment_method, order_status, created_at FROM orders WHERE user_id = :user_id ORDER BY id DESC");
$stmt->execute([':user_id' => $user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
      
        <h4 class="fw-bold mb-0"><i class="bi bi-receipt text-info me-2"></i>Transaction History</h4>
          <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3">Home</a>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="bi bi-credit-card-2-front fs-1 text-muted"></i>
            <h5 class="mt-3 text-secondary">No transaction records found!</h5>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm p-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($tx['order_number']); ?></td>
                                <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($tx['created_at'])); ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tx['payment_method']); ?></span></td>
                                <td class="fw-bold text-success">৳<?= number_format($tx['grand_total'], 2); ?></td>
                                <td>
                                    <?php if ($tx['order_status'] === 'Delivered'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Paid</span>
                                    <?php elseif ($tx['order_status'] === 'Cancelled'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning">Pending COD</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>