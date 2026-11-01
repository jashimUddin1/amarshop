<?php
require_once('../auth/config.php');
require_once __DIR__ . '/email_verification.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . base_url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $customar_name    = trim($_POST['customar_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // ১. নাম যাচাই
    if ($customar_name === '') {
        $errors['customar_name'] = 'Please enter your full name.';
    }

    // ২. ফোন নম্বর বাধ্যতামূলক যাচাই
    $phone_digits = preg_replace('/\D/', '', $phone);
    if ($phone === '') {
        $errors['phone'] = 'Please enter your phone number.';
    } elseif (preg_match('/^[0-9+\s().-]+$/', $phone) !== 1 || strlen($phone_digits) < 8) {
        $errors['phone'] = 'Phone number must contain at least 8 digits.';
    }

    // Email is required because accounts must verify ownership before login.
    if ($email === '') {
        $errors['email'] = 'Email address is required for account verification.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // ৪. পাসওয়ার্ড যাচাই
    if ($password === '') {
        $errors['password'] = 'Please enter a password.';
    } elseif (strlen($password) < 5) {
        $errors['password'] = 'Password must be at least 5 characters long.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['signup_old'] = [
            'customar_name' => $customar_name,
            'phone'         => $phone,
            'email'         => $email,
        ];
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

    try {
        // ৪. একই নাম্বার দিয়ে একাধিক একাউন্ট চেক (Duplicate Phone Check)
        $phone_check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
        $phone_check->execute([':phone' => $phone]);
        if ($phone_check->fetch()) {
            $errors['phone'] = 'নাম্বার আগেই আছে। অন্য নাম্বার দিয়ে সাইন আপ করুন অথবা <a href="login.php" class="text-success fw-bold text-decoration-underline">লগইন করুন</a>।';
        }

        // ইমেইল দেওয়া থাকলে ডুপ্লিকেট ইমেইল চেক
        if ($email !== '') {
            $email_check = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $email_check->execute([':email' => $email]);
            if ($email_check->fetch()) {
                $errors['email'] = 'এই ইমেইল আগেই  একাউন্টের সাথে যোগ করা আছে, নতুন ইমেল দিন অথবা সাইট এডমিনের সাথে যোগাযোগ করুন ';
            }
        }

        if (!empty($errors)) {
            $_SESSION['signup_errors'] = $errors;
            $_SESSION['signup_old'] = [
                'customar_name' => $customar_name,
                'phone'         => $phone,
                'email'         => $email,
            ];
            if (isset($errors['phone'])) {
                $_SESSION['status'] = 'warning';
                $_SESSION['message'] = 'এই নাম্বার দিয়ে আগেই একাউন্ট তৈরি করা হয়েছে। অন্য নাম্বার দিয়ে সাইন আপ করুন অথবা <a href="login.php" class="alert-link text-decoration-underline fw-bold">লগইন করুন</a>।';
            }
            header('Location: ' . base_url('auth/signup.php'));
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $verify_token    = md5(rand() . time());
        $created_at      = date('Y-m-d H:i:s');
        $role            = 'user';
        
        $user_ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $client_ip   = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $user_ip;
        $remote_host = $user_ip ? @gethostbyaddr($user_ip) : null;
        $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Account access remains blocked until the email OTP is verified.
        $sql = "INSERT INTO users (
                    customar_name, phone, email, password, 
                    verify_token, verify_status, phone_verified, email_verified, created_at, role, 
                    user_ip, client_ip, remote_host, user_agent, 
                    reset_count, pwd_up_count
                ) VALUES (
                    :customar_name, :phone, :email, :password, 
                    :verify_token, 1, 0, 0, :created_at, :role, 
                    :user_ip, :client_ip, :remote_host, :user_agent, 
                    0, 0
                )";

        $insert_stmt = $pdo->prepare($sql);
        $executed = $insert_stmt->execute([
            ':customar_name'  => $customar_name,
            ':phone'          => $phone,
            ':email'          => $email,
            ':password'       => $hashed_password,
            ':verify_token'   => $verify_token,
            ':created_at'     => $created_at,
            ':role'           => $role,
            ':user_ip'        => $user_ip,
            ':client_ip'      => $client_ip,
            ':remote_host'    => $remote_host,
            ':user_agent'     => $user_agent
        ]);

        if ($executed) {
            $new_user_id = (int)$pdo->lastInsertId();
            $_SESSION['verify_user_id'] = $new_user_id;
            $_SESSION['verify_email'] = $email;
            try {
                send_email_verification_otp($pdo, $new_user_id, $email, $customar_name);
                $_SESSION['status'] = 'success';
                $_SESSION['message'] = 'আপনার ইমেইলে ৬ সংখ্যার OTP পাঠানো হয়েছে। ১০ মিনিটের মধ্যে যাচাই করুন।';
            } catch (Throwable $mailError) {
                error_log('Signup email verification send failed: ' . $mailError->getMessage());
                $_SESSION['status'] = 'danger';
                $_SESSION['message'] = 'অ্যাকাউন্ট তৈরি হয়েছে, কিন্তু OTP ইমেইল পাঠানো যায়নি। SMTP সেটিংস ঠিক করে OTP আবার পাঠান।';
            }
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }

    } catch (PDOException $e) {
        error_log('Signup database error: ' . $e->getMessage());
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'রেজিস্ট্রেশন সম্পন্ন করা সম্ভব হয়নি। অনুগ্রহ করে আবার চেষ্টা করুন।';
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }
}
