<?php
require_once 'config.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone       = trim($_POST['phone'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm_pwd = $_POST['confirm_password'] ?? '';
    
    $testColumn  = 'N/A'; // table ar NOT NULL field ar jonno defualt man


    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
        die("Please fill in all required fields.");
    }

    // email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // password match
    if ($password !== $confirm_pwd) {
        die("Password and Confirm Password do not match.");
    }

    try {
        // check email if already exits in database
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR phone = :phone LIMIT 1");
        $checkStmt->execute([':email' => $email, ':phone' => $phone]);

        if ($checkStmt->fetch()) {
            die("Email or Phone Number is already registered.");
        }

        
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        //user information 
        $user_ip      = $_SERVER['REMOTE_ADDR'] ?? null;
        $client_ip    = $_SERVER['HTTP_CLIENT_IP'] ?? $user_ip;
        $remote_host  = gethostbyaddr($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $user_agent   = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $verify_token = bin2hex(random_bytes(16)); // ভেরিফিকেশন টোকেন তৈরি

        // 
        $sql = "INSERT INTO users (
                    first_name, last_name, phone, email, password, 
                    verify_token, verify_status, created_at, 
                    testColumn, user_ip, client_ip, remote_host, user_agent
                ) VALUES (
                    :first_name, :last_name, :phone, :email, :password, 
                    :verify_token, 0, NOW(), 
                    :testColumn, :user_ip, :client_ip, :remote_host, :user_agent
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':first_name'   => $first_name,
            ':last_name'    => $last_name,
            ':phone'        => $phone,
            ':email'        => $email,
            ':password'     => $hashed_password,
            ':verify_token' => $verify_token,
            ':testColumn'   => $testColumn,
            ':user_ip'      => $user_ip,
            ':client_ip'    => $client_ip,
            ':remote_host'  => $remote_host,
            ':user_agent'   => $user_agent
        ]);

        // সফল হলে লগইন পেজে রিডাইরেক্ট বা মেসেজ
        header("Location: login.php?signup=success");
        exit;

    } catch (PDOException $e) {
        die("Error saving user: " . $e->getMessage());
    }
} else {
    // সরাসরি ফাইল অ্যাক্সেস করলে রিডাইরেক্ট করা
    header("Location: signup.php");
    exit;
}