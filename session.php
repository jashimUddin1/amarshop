<?php
session_start();

// Display session data
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Logout logic when button/link is clicked
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // 1. Unset all session variables
    $_SESSION = array();

    // 2. Destroy the session cookie if exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }

    // 3. Destroy the session
    session_destroy();

    // 4. Redirect to login page
    header("Location: login.php");
    exit;
}
?>

<!-- Logout Link / Button -->
<?php if (!empty($_SESSION)): ?>
    <a href="?action=logout" style="padding: 10px 15px; background: red; color: white; text-decoration: none; border-radius: 5px;">
        Logout
    </a>
<?php else: ?>
    <p>No active session to log out from.</p>
<?php endif; ?>
