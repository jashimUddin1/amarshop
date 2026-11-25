<?php
require_once 'config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expected_csrf = is_string($_SESSION['admin_signup_csrf'] ?? null) ? $_SESSION['admin_signup_csrf'] : '';
    $first_name_input = $_POST['first_name'] ?? '';
    $last_name_input = $_POST['last_name'] ?? '';
    $email_input = $_POST['email'] ?? '';
    $phone_input = $_POST['phone'] ?? '';
    $password_input = $_POST['password'] ?? '';
    $confirm_input = $_POST['confirm_password'] ?? '';
    $role_input = $_POST['role'] ?? '';
    $first_name = is_string($first_name_input) ? trim($first_name_input) : '';
    $last_name = is_string($last_name_input) ? trim($last_name_input) : '';
    $email = is_string($email_input) ? trim($email_input) : '';
    $phone = is_string($phone_input) ? trim($phone_input) : '';
    $password = is_string($password_input) ? $password_input : '';
    $confirm_pwd = is_string($confirm_input) ? $confirm_input : '';
    $role = is_string($role_input) ? trim($role_input) : '';
    $allowed_roles = admin_assignable_roles((string)($_SESSION['user_role'] ?? ''));

    if (!is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' || !hash_equals($expected_csrf, $csrf)) {
        http_response_code(400);
        $_SESSION['admin_signup_message'] = 'Invalid request token. Reload the page and try again.';
        $_SESSION['admin_signup_message_type'] = 'danger';
        header('Location: signup.php');
        exit;
    }

    if ($first_name === '' || $last_name === '' || $email === '' || $phone === '' || $password === '' ||
        !in_array($role, $allowed_roles, true)) {
        $_SESSION['admin_signup_message'] = 'Fill in all fields and choose a role that you are allowed to create.';
        $_SESSION['admin_signup_message_type'] = 'warning';
        $_SESSION['admin_signup_old'] = compact('first_name', 'last_name', 'email', 'phone', 'role');
        header('Location: signup.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_signup_message'] = 'Enter a valid email address.';
        $_SESSION['admin_signup_message_type'] = 'warning';
        $_SESSION['admin_signup_old'] = compact('first_name', 'last_name', 'email', 'phone', 'role');
        header('Location: signup.php');
        exit;
    }
    if (!is_string($password) || strlen($password) < 8 || !is_string($confirm_pwd) || !hash_equals($password, $confirm_pwd)) {
        $_SESSION['admin_signup_message'] = 'Passwords must match and contain at least 8 characters.';
        $_SESSION['admin_signup_message_type'] = 'warning';
        $_SESSION['admin_signup_old'] = compact('first_name', 'last_name', 'email', 'phone', 'role');
        header('Location: signup.php');
        exit;
    }

    try {
        ensure_admin_account_tables($pdo);
        $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = :email OR phone = :phone LIMIT 1");
        $checkStmt->execute([':email' => $email, ':phone' => $phone]);
        if ($checkStmt->fetch()) {
            $_SESSION['admin_signup_message'] = 'Email or phone number is already registered for an admin account.';
            $_SESSION['admin_signup_message_type'] = 'danger';
            $_SESSION['admin_signup_old'] = compact('first_name', 'last_name', 'email', 'phone', 'role');
            header('Location: signup.php');
            exit;
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

        $_SESSION['admin_signup_message'] = 'Admin staff account created successfully.';
        $_SESSION['admin_signup_message_type'] = 'success';
        header("Location: signup.php");
        exit;
    } catch (PDOException $e) {
        error_log('Admin account creation failed: ' . $e->getMessage());
        $_SESSION['admin_signup_message'] = 'The admin account could not be created. Please try again.';
        $_SESSION['admin_signup_message_type'] = 'danger';
        header('Location: signup.php');
        exit;
    }
} else {
    header("Location: signup.php");
    exit;
}