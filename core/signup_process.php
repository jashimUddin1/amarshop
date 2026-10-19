<?php
require_once('../auth/config.php');

// PHPMailer ফাইল ইনক্লুড
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // composer ব্যবহার করলে, অথবা PHPMailer ম্যানুয়ালি ইনক্লুড করুন

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $customar_name    = trim($_POST['customar_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if ($customar_name === '') {
        $errors['customar_name'] = 'Please enter your full name.';
    }
    if ($phone === '') {
        $errors['phone'] = 'Please enter your phone number.';
    } elseif (preg_match('/^[0-9+\s().-]+$/', $phone) !== 1 || strlen(preg_replace('/\D/', '', $phone)) < 8) {
        $errors['phone'] = 'Phone number must contain at least 8 digits.';
    }
    if ($email === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
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

    if ($errors) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['signup_old'] = [
            'customar_name' => $customar_name,
            'phone' => $phone,
            'email' => $email,
        ];
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

    try {
        $check_stmt = $pdo->prepare("SELECT email, phone FROM users WHERE email = :email OR phone = :phone");
        $check_stmt->execute([':email' => $email, ':phone' => $phone]);
        $errors = [];

        foreach ($check_stmt->fetchAll(PDO::FETCH_ASSOC) as $existing_user) {
            if (strcasecmp($existing_user['email'], $email) === 0) {
                $errors['email'] = 'This email address is already registered.';
            }
            if ($existing_user['phone'] === $phone) {
                $errors['phone'] = 'This phone number is already registered.';
            }
        }

        if ($errors) {
            $_SESSION['signup_errors'] = $errors;
            $_SESSION['signup_old'] = [
                'customar_name' => $customar_name,
                'phone' => $phone,
                'email' => $email,
            ];
            header('Location: ' . base_url('auth/signup.php'));
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // ৬ ডিজিটের OTP এবং ১০ মিনিটের মেয়াদ
        $otp_code       = rand(100000, 999999);
        $otp_expires_at = date("Y-m-d H:i:s", strtotime('+10 minutes'));
        
        $verify_token  = md5(rand() . time());
        $verify_status = 0; // ০ = Unverified
        $created_at    = date('Y-m-d H:i:s');
        $role          = 'user';
        
        $user_ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $client_ip   = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $user_ip;
        $remote_host = gethostbyaddr($user_ip);
        $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sql = "INSERT INTO users (
                    customar_name, phone, email, password, 
                    verify_token, otp_code, otp_expires_at, verify_status, created_at, role, 
                    user_ip, client_ip, remote_host, user_agent, 
                    reset_count, pwd_up_count
                ) VALUES (
                    :customar_name, :phone, :email, :password, 
                    :verify_token, :otp_code, :otp_expires_at, :verify_status, :created_at, :role, 
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
            ':otp_code'       => $otp_code,
            ':otp_expires_at' => $otp_expires_at,
            ':verify_status'  => $verify_status,
            ':created_at'     => $created_at,
            ':role'           => $role,
            ':user_ip'        => $user_ip,
            ':client_ip'      => $client_ip,
            ':remote_host'    => $remote_host,
            ':user_agent'     => $user_agent
        ]);

        if ($executed) {
            // Gmail দিয়ে OTP পাঠানো
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

            // ভেরিফিকেশনের জন্য সেশনে ইমেইল সেভ করা
            $_SESSION['verify_email'] = $email;
            $_SESSION['status']       = 'success';
            $_SESSION['message']      = 'OTP has been sent to your email!';
            
            header('Location: ' . base_url('auth/otp_verify.php'));
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'We could not complete your registration. Please try again.';
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }
}