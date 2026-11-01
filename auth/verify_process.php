<?php
require_once('../auth/config.php');
require_once __DIR__ . '/../core/email_verification.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'verify';
    $otp_input = is_string($_POST['otp_code'] ?? null) ? trim($_POST['otp_code']) : '';
    $email = is_string($_SESSION['verify_email'] ?? null) ? $_SESSION['verify_email'] : '';
    $userId = filter_var($_SESSION['verify_user_id'] ?? null, FILTER_VALIDATE_INT);
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expectedCsrf = $_SESSION['email_verify_csrf'] ?? '';

    if (!$userId || $email === '' || !is_string($expectedCsrf) || $expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
        http_response_code(400);
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'এই অনুরোধটি যাচাই করা যায়নি। আবার চেষ্টা করুন।';
        header('Location: ' . base_url('auth/otp_verify.php'));
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, customar_name, email, verify_status, email_verified, otp_code, otp_expires_at FROM users WHERE id = :id AND email = :email LIMIT 1');
        $stmt->execute([':id' => $userId, ':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int)$user['verify_status'] !== 1) {
            unset($_SESSION['verify_user_id'], $_SESSION['verify_email']);
            $_SESSION['status'] = 'danger';
            $_SESSION['message'] = 'এই অ্যাকাউন্টের ইমেইল যাচাই করা যাচ্ছে না। সাইট অ্যাডমিনের সঙ্গে যোগাযোগ করুন।';
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }

        if ($action === 'resend') {
            if ((int)$user['email_verified'] === 1) {
                $_SESSION['status'] = 'success';
                $_SESSION['message'] = 'এই ইমেইলটি ইতিমধ্যে যাচাই করা হয়েছে। এখন লগইন করুন।';
                header('Location: ' . base_url('auth/login.php'));
                exit;
            }
            send_email_verification_otp($pdo, (int)$user['id'], (string)$user['email'], (string)$user['customar_name']);
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'নতুন OTP আপনার ইমেইলে পাঠানো হয়েছে। কোডটি ১০ মিনিটের মধ্যে ব্যবহার করুন।';
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }

        if ($action !== 'verify' || preg_match('/\A[0-9]{6}\z/', $otp_input) !== 1) {
            $_SESSION['status'] = 'danger';
            $_SESSION['message'] = 'সঠিক ৬ সংখ্যার OTP লিখুন।';
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }

        if ((int)$user['email_verified'] === 1) {
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'এই ইমেইলটি ইতিমধ্যে যাচাই করা হয়েছে। এখন লগইন করুন।';
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }

        $expiry = !empty($user['otp_expires_at']) ? strtotime((string)$user['otp_expires_at']) : false;
        if (!is_scalar($user['otp_code']) || !hash_equals((string)$user['otp_code'], $otp_input)) {
            $_SESSION['status'] = 'danger';
            $_SESSION['message'] = 'OTP কোডটি সঠিক নয়। আবার চেষ্টা করুন।';
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }
        if ($expiry === false || $expiry < time()) {
            $_SESSION['status'] = 'warning';
            $_SESSION['message'] = 'OTP-এর মেয়াদ শেষ হয়েছে। নতুন OTP পাঠান।';
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }

        $update = $pdo->prepare('UPDATE users SET email_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = :id AND email_verified = 0 AND verify_status = 1');
        $update->execute([':id' => $userId]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Email verification status was not updated.');
        }

        unset($_SESSION['verify_user_id'], $_SESSION['verify_email']);
        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'ইমেইল যাচাই সফল হয়েছে। এখন লগইন করুন।';
        header('Location: ' . base_url('auth/login.php'));
        exit;
    } catch (Throwable $e) {
        error_log('Email verification failed: ' . $e->getMessage());
        $_SESSION['status'] = 'danger';
        $_SESSION['message'] = 'ইমেইল যাচাই সম্পন্ন করা যায়নি। SMTP সেটিংস পরীক্ষা করে আবার চেষ্টা করুন।';
        header('Location: ' . base_url('auth/otp_verify.php'));
        exit;
    }
}

header('Location: ' . base_url('auth/login.php'));
exit;