<?php
require_once('config.php');

// ১. ডাটাবেজ থেকে remember_token ডিলিট
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
}

// ২. সেশন খালি ও ধ্বংস করা
$_SESSION = array();
session_destroy();

// ৩. কুকি রিমুভ (মেয়াদ অতীতে সেট করে দিয়ে)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/");
}

session_start();
$_SESSION['status']  = 'success';
$_SESSION['message'] = 'You have logged out successfully.';
session_write_close();

header('Location: login.php');
exit;