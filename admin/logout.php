<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php';

// ------------------------------------------
// 1. Log admin logout action
// ------------------------------------------
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['user_name'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

    log_action($admin_id, 'Logout', "User {$admin_name} logged out from IP {$ip}");
}

// ------------------------------------------
// 2. Remove Remember Me token (cookie + JSON)
// ------------------------------------------

// Files for remember-me storage
$tokenDir  = __DIR__ . '/../storage/tokens';
$tokenFile = $tokenDir . '/remember_me_tokens.json';

// Helper functions
function load_json_custom($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
}
function save_json_custom($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

if (!empty($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $hash = hash('sha256', $token);

    $tokens = load_json_custom($tokenFile);

    // Delete token from JSON
    if (isset($tokens[$hash])) {
        unset($tokens[$hash]);
        save_json_custom($tokenFile, $tokens);
    }

    // Delete cookie
    setcookie("remember_me", '', time() - 3600, "/", "", false, true);
}

// ------------------------------------------
// 3. Clear session
// ------------------------------------------
$_SESSION = [];
session_destroy();

// ------------------------------------------
// 4. Redirect to login page
// ------------------------------------------
header("Location: login.php");
exit;
?>
