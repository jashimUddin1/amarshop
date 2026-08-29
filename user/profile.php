<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// আপডেট রিকোয়েস্ট হ্যান্ডলিং
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    if (!empty($name) && !empty($phone)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
            $update_stmt->execute([
                ':name'  => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':id'    => $user_id
            ]);
            
            // সেশন তথ্য আপডেট
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            $message = 'Profile updated successfully!';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $message = 'Name and Phone fields are required!';
        $msg_type = 'warning';
    }
}

// ইউজারের বর্তমান ডাটা লোড
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between mb-4">
                    
                    <h4 class="fw-bold mb-0"><i class="bi bi-person-vcard text-primary me-2"></i>My Profile</h4>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3"> Home</a>

                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show py-2" role="alert">
                        <?= htmlspecialchars($message); ?>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>