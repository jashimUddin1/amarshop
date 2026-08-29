<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');

    if (!empty($address)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET address = :address WHERE id = :id");
            $stmt->execute([':address' => $address, ':id' => $user_id]);
            $message = 'Address saved successfully!';
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// বর্তমান ঠিকানা লোড
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Addresses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-geo-alt text-danger me-2"></i>Saved Address</h4>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3">Home</a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <?= htmlspecialchars($message); ?>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="addresses.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Default Shipping Address *</label>
                        <textarea name="address" class="form-control" rows="4" placeholder="House no, Road no, Area, City..." required><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        <small class="text-muted mt-1 d-block">This address will automatically pre-fill during your checkout process.</small>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 fw-bold py-2">Update Address</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>