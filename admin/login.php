<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// ---------------------------
// Secure session start
// ---------------------------
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $secure,
        'cookie_samesite' => 'Strict'
    ]);
}

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Flash message
$message = $_SESSION['flash_message'] ?? '';
$_SESSION['flash_message'] = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log directory
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/login_attempts.log';

// ---------------------------
// Handle form submission
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $timestamp = date('Y-m-d H:i:s');

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $_SESSION['flash_message'] = "Invalid CSRF token";
        redirect('login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? 'User';
        $_SESSION['user_role'] = $user['role'] ?? 'Admin';

        logMessage("✅ Successful login: {$email}");
        redirect('dashboard.php');
    } else {
        file_put_contents($logFile, "[{$timestamp}] ❌ FAILED login | Email: {$email} | IP: {$ip}\n", FILE_APPEND | LOCK_EX);
        $_SESSION['flash_message'] = "Invalid email or password";
        redirect('login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - <?= sanitize($site_name) ?></title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f2f5;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.login-container {
    background: #fff;
    padding: 40px;
    border-radius: 10px;
    width: 400px;
    max-width: 90%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
h2 { text-align: center; margin-bottom: 20px; color: #333; }
label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
input[type="email"], input[type="password"] {
    width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 6px;
}
button {
    width: 100%; padding: 12px; background: #1E90FF; color: #fff; border: none; border-radius: 6px;
    font-weight: bold; cursor: pointer; transition: background 0.3s;
}
button:hover { background: #187bcd; }
p.message {
    background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px;
    text-align: center; margin-bottom: 20px; animation: fadeout 5s forwards;
}
@keyframes fadeout { 0% {opacity:1;} 80% {opacity:1;} 100% {opacity:0; display:none;} }
.register-link { text-align: center; margin-top: 10px; }
.register-link a { color: #1E90FF; text-decoration: none; }
.register-link a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <label>Email</label>
        <input type="email" name="email" required placeholder="Enter your email">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter your password">
        <button type="submit">Login</button>
    </form>
    <div class="register-link">
        Don’t have an account? <a href="register.php">Register Here</a>
    </div>
</div>
</body>
</html>
