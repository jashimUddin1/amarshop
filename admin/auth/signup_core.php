<?php
require_once 'config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $expected_csrf = $_SESSION['admin_signup_csrf'] ?? '';
    $first_name_input = $_POST['first_name'] ?? '';
    $last_name_input = $_POST['last_name'] ?? '';
    $email_input = $_POST['email'] ?? '';
    $phone_input = $_POST['phone'] ?? '';
    $password_input = $_POST['password'] ?? '';
    $confirm_input = $_POST['confirm_password'] ?? '';
    $role_input = $_POST['role'] ?? '';
    $first_name = is_string($first_name_input) ? trim($first_name_input) : '';
    $last_name = is_string($last_name_input) ? trim($last_name_input) : '';
    $email = is_string($email_input) ? filter_var(trim($email_input), FILTER_SANITIZE_EMAIL) : '';
    $phone = is_string($phone_input) ? trim($phone_input) : '';
    $password = is_string($password_input) ? $password_input : '';
    $confirm_pwd = is_string($confirm_input) ? $confirm_input : '';
    $role = is_string($role_input) ? trim($role_input) : '';
    $allowed_roles = ['CEO', 'MD', 'MANAGER', 'ASS. MANAGER', 'SALESMAN'];

    if (!is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
        http_response_code(400);
        exit("Invalid request token.");
    }

    if ($first_name === '' || $last_name === '' || $email === '' || $phone === '' || $password === '' ||
        !in_array($role, $allowed_roles, true)) {
        die("Please fill in all required fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    if (!is_string($password) || strlen($password) < 8 || !is_string($confirm_pwd) || !hash_equals($password, $confirm_pwd)) {
        die("Password and Confirm Password do not match.");
    }

    try {
        ensure_admin_account_tables($pdo);
        $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = :email OR phone = :phone LIMIT 1");
        $checkStmt->execute([':email' => $email, ':phone' => $phone]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            exit("Email or phone number is already registered for an admin account.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO admin_users (customar_name, email, phone, password, role)
            VALUES (:name, :email, :phone, :password, :role)
        ");
        $stmt->execute([
            ':name' => trim($first_name . ' ' . $last_name),
            ':email' => $email,
            ':phone' => $phone,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $role,
        ]);

        header("Location: ../users.php?staff=created");
        exit;
    } catch (PDOException $e) {
        error_log('Admin account creation failed: ' . $e->getMessage());
        http_response_code(500);
        exit("The admin account could not be created. Please try again.");
    }
} else {
    header("Location: signup.php");
    exit;
}