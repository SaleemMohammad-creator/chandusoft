<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Add this line for admin logging

// If user was logged in, log the logout action
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['user_name'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

    // ✅ Save logout event in admin_logs
    log_action($admin_id, 'Logout', "User {$admin_name} logged out from IP {$ip}");
}

// Clear all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
