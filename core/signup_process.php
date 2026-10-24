<?php
require_once('../auth/config.php');

// PHPMailer (ভবিষ্যতে ইমেইল ওটিপি পাঠানোর জন্য প্রস্তুত রাখা হয়েছে)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
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

    // ৩. ইমেইল অপশনাল (যদি দেওয়া হয় তবে ভ্যালিড হতে হবে)
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

        // ৩. আপাতত কোনো ভেরিফাই ছাড়াই একাউন্ট তৈরি হবে
        // verify_status = 1 (সরাসরি একটিভ), phone_verified = 0, email_verified = 0
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
            ':email'          => $email !== '' ? $email : null,
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

            /*
            // ========================================================
            // ইমেইল OTP ভেরিফিকেশন (অনুরোধ অনুযায়ী আপাতত কমেন্ট করা হলো)
            // ভবিষ্যতে SMTP_PASS যুক্ত করার পর নিচের কোড আনকমেন্ট করলেই স্বয়ংক্রিয়ভাবে মেইল যাবে:
            // ========================================================
            if ($email !== '' && defined('SMTP_PASS') && SMTP_PASS !== '') {
                try {
                    $otp_code = rand(100000, 999999);
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    $pdo->prepare("UPDATE users SET otp_code = :otp, otp_expires_at = :exp WHERE id = :id")
                        ->execute([':otp' => $otp_code, ':exp' => $otp_expires, ':id' => $new_user_id]);

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;

                    $mail->setFrom(SMTP_FROM, SMTP_NAME);
                    $mail->addAddress($email, $customar_name);
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Email - AmarShop';
                    $mail->Body    = "<h3>Hello {$customar_name},</h3><p>Your OTP verification code for AmarShop is: <b style='font-size: 20px; color: #198754;'>{$otp_code}</b></p><p>This code will expire in 10 minutes.</p>";
                    $mail->send();
                } catch (\Throwable $mEx) {
                    error_log('Signup email send error: ' . $mEx->getMessage());
                }
            }
            */

            // সরাসরি সেশন সেট করে লগইন করিয়ে দেওয়া হলো
            $_SESSION['user_id']    = $new_user_id;
            $_SESSION['user_name']  = $customar_name;
            $_SESSION['user_email'] = $email ?: '';
            $_SESSION['user_role']  = $role;

            $_SESSION['status']  = 'success';
            $_SESSION['message'] = 'একাউন্ট সফলভাবে তৈরি হয়েছে! স্বাগতম, ' . htmlspecialchars($customar_name) . '।';

            header('Location: ' . base_url('index.php'));
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
