<?php //login_process.php
require_once('../auth/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $login_identity = trim($_POST['login_identity'] ?? '');
    $password       = $_POST['password'] ?? '';
    $remember       = isset($_POST['remember']); // Checkbox চেক করা আছে কিনা

    if (empty($login_identity) || empty($password)) {
        $_SESSION['status']  = 'warning';
        $_SESSION['message'] = 'Please fill in all fields!';
        header('Location: login.php');
        exit;
    }

    try {
        // ইমেইল বা ফোন নম্বর দিয়ে ইউজার খোঁজা
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :identity OR phone = :identity LIMIT 1");
        $stmt->execute([':identity' => $login_identity]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            // ইমেইল ভেরিফাইড কিনা চেক
            if ($user['verify_status'] != 1) {
                $_SESSION['verify_email'] = $user['email'];
                $_SESSION['status']       = 'warning';
                $_SESSION['message']      = 'Your email is not verified yet. Please verify your OTP!';
                session_write_close();
                header('Location: otp_verify.php');
                exit;
            }

            // সেশন ডাটা সেট
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['customar_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            // --- Remember Me (১৫ দিন কুকি সেট আপ) ---
            if ($remember) {
                // একটি র্যান্ডম ও সিকিউর টোকেন তৈরি
                $remember_token = bin2hex(random_bytes(32));

                // ডাটাবেজে টোকেন সেভ
                $update = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $update->execute([
                    ':token' => $remember_token,
                    ':id'    => $user['id']
                ]);

                // 30 দিনের মেয়াদ দিয়ে কুকি সেট (15 days * 24 hours * 60 mins * 60 secs)
                $expiry_time = time() + (86400 * 30);
                setcookie('remember_me', $remember_token, $expiry_time, "/", "", false, true); // httponly: true
            }

            $_SESSION['status']  = 'success';
            $_SESSION['message'] = 'Loggin Successful, ' . htmlspecialchars($user['customar_name']) . '!';
            session_write_close();
            
            // লগইন সফল হলে ড্যাশবোর্ড বা হোম পেজে রিডাইরেক্ট
            header('Location: ../index.php');
            exit;

        } else {
            $_SESSION['status']  = 'danger';
            $_SESSION['message'] = 'Invalid email/phone or password!';
            session_write_close();
            header('Location: ../auth/login.php');
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'Database Error: ' . $e->getMessage();
        session_write_close();
        header('Location: ../auth/login.php');
        exit;
    }

} else {
    header('Location: ../auth/login.php');
    exit;
}