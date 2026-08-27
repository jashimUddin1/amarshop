<?php
require_once('../auth/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $email     = $_SESSION['verify_email'] ?? '';

    if (empty($otp_input) || empty($email)) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'Invalid request or OTP!';
        header('Location: ' . base_url('auth/otp_verify.php'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // ১. OTP ম্যাচিং এবং টাইম ভ্যালিডেশন চেক
            if ($user['otp_code'] === $otp_input) {
                if (strtotime($user['otp_expires_at']) >= time()) {
                    
                    // ভেরিফিকেশন সফল - verify_status = 1 এবং OTP মুছে ফেলা
                    $update = $pdo->prepare("UPDATE users SET verify_status = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = :id");
                    $update->execute([':id' => $user['id']]);

                    unset($_SESSION['verify_email']);
                    $_SESSION['status']  = 'success';
                    $_SESSION['message'] = 'Email verified successfully! You can log in Now.';
                    header('Location: ' . base_url('auth/login.php'));
                    exit;

                } else {
                    $_SESSION['status']  = 'danger';
                    $_SESSION['message'] = 'OTP has expired! Please sign up again.';
                    header('Location: ' . base_url('auth/otp_verify.php'));
                    exit;
                }
            } else {
                $_SESSION['status']  = 'danger';
                $_SESSION['message'] = 'Invalid OTP Code!';
                header('Location: ' . base_url('auth/otp_verify.php'));
                exit;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'Database Error: ' . $e->getMessage();
        header('Location: ' . base_url('auth/otp_verify.php'));
        exit;
    }
}