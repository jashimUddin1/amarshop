<?php
// ১. কনফিগারেশন ফাইল ইনক্লুড (ভিতরে session_start() রয়েছে)
require_once('../auth/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ২. ফর্ম ইনপুট সেনিটাইজ করা
    $customar_name       = trim($_POST['customar_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ৩. খালি ফিল্ড ভ্যালিডেশন
    if (empty($customar_name) || empty($phone) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['status']  = 'warning';
        $_SESSION['message'] = 'Please fill in all required fields!';
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

    // ৪. পাসওয়ার্ড ম্যাচিং চেক
    if ($password !== $confirm_password) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'Passwords do not match!';
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

    // ৫. ইমেইল ফরম্যাট ভ্যালিডেশন
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status']  = 'warning';
        $_SESSION['message'] = 'Invalid email address format!';
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

    try {
        // ৬. ইমেইল বা ফোন নম্বর আগে থেকেই আছে কিনা চেক করা
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR phone = :phone LIMIT 1");
        $check_stmt->execute([
            ':email' => $email,
            ':phone' => $phone
        ]);

        if ($check_stmt->rowCount() > 0) {
            $_SESSION['status']  = 'warning';
            $_SESSION['message'] = 'Email or Phone number is already registered!';
            header('Location: ' . base_url('auth/signup.php'));
            exit;
        }

        // ৭. পাসওয়ার্ড হ্যাশিং
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // ৮. সিকিউরিটি এবং ট্র্যাকিং ডাটা প্রস্তুত করা
        $verify_token  = md5(rand() . time()); // ভেরিফিকেশনের জন্য র‍্যান্ডম টোকেন
        $verify_status = 0;                     // ০ = Unverified, ১ = Verified
        $created_at    = date('Y-m-d H:i:s');
        $role          = 'user';
        
        // ক্লায়েন্ট তথ্য (IP, User Agent)
        $user_ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $client_ip   = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $user_ip;
        $remote_host = gethostbyaddr($user_ip);
        $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // ৯. ডাটাবেজে ইউজার ইনসার্ট কোয়েরি
        $sql = "INSERT INTO users (
                    customar_name, phone, email, password, 
                    verify_token, verify_status, created_at, role, 
                    user_ip, client_ip, remote_host, user_agent, 
                    reset_count, pwd_up_count
                ) VALUES (
                    :customar_name, :phone, :email, :password, 
                    :verify_token, :verify_status, :created_at, :role, 
                    :user_ip, :client_ip, :remote_host, :user_agent, 
                    0, 0
                )";

        $insert_stmt = $pdo->prepare($sql);
        $executed = $insert_stmt->execute([
            ':customar_name'    => $customar_name,
            ':phone'         => $phone,
            ':email'         => $email,
            ':password'      => $hashed_password,
            ':verify_token'  => $verify_token,
            ':verify_status' => $verify_status,
            ':created_at'    => $created_at,
            ':role'          => $role,
            ':user_ip'       => $user_ip,
            ':client_ip'     => $client_ip,
            ':remote_host'   => $remote_host,
            ':user_agent'    => $user_agent
        ]);

        if ($executed) {
            $_SESSION['status']  = 'success';
            $_SESSION['message'] = 'Account created successfully! Please log in.';
            header('Location: ' . base_url('auth/login.php'));
            exit;
        } else {
            $_SESSION['status']  = 'danger';
            $_SESSION['message'] = 'Failed to create account. Please try again!';
            header('Location: ' . base_url('auth/signup.php'));
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['status']  = 'danger';
        $_SESSION['message'] = 'Database Error: ' . $e->getMessage();
        header('Location: ' . base_url('auth/signup.php'));
        exit;
    }

} else {
    header('Location: ' . base_url('auth/signup.php'));
    exit;
}