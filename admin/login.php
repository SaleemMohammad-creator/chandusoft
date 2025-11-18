<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ For Mailpit Logging
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ For Admin Activity Logging

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

// ---------------------------
// Redirect if already logged in
// ---------------------------
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    $_SESSION['flash_message'] = "You are already logged in!";
    header("Location: dashboard.php");
    exit;
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
        header("Location: login.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Session Set
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? 'User';
        $_SESSION['user_role'] = $user['role'] ?? 'Admin';

        // ✅ File Log
        file_put_contents($logFile, "[{$timestamp}] ✅ SUCCESS login | Email: {$email} | IP: {$ip}\n", FILE_APPEND | LOCK_EX);

        // ✅ Mailpit Log
        $role = $user['role'] ?? 'User';
        mailLog(
            "✅ {$role} Logged In",
            "Email: {$email}\nRole: {$role}\nIP Address: {$ip}\nTime: {$timestamp}"
        );

        // ✅ Database Log (SUCCESS)
        log_action($user['id'], 'Login Success', "User {$email} logged in from IP {$ip}");

        header("Location: dashboard.php");
        exit;
    } else {
        // ✅ File Log
        file_put_contents($logFile, "[{$timestamp}] ❌ FAILED login | Email: {$email} | IP: {$ip}\n", FILE_APPEND | LOCK_EX);

        // ✅ Mailpit Log
        mailLog(
            "❌ Failed Login Attempt",
            "Email: {$email}\nAttempted Role: Unknown\nIP Address: {$ip}\nTime: {$timestamp}"
        );

        // ✅ Database Log (FAILED)
        log_action(null, 'Login Failed', "Failed login attempt for {$email} from {$ip}");

        $_SESSION['flash_message'] = "Invalid email or password";
        header("Location: login.php");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - <?= sanitize($site_name) ?></title>
<style>
/* ===============================
   GLOBAL PAGE STYLE
================================*/
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #e3f2ff, #f3f7ff);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

/* ===============================
   LOGIN CONTAINER
================================*/
.login-container {
    background: #ffffff;
    padding: 40px;
    border-radius: 14px;
    width: 380px;
    max-width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    animation: fadeSlideIn 0.5s ease-out;
}

/* Slide-in animation */
@keyframes fadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===============================
   HEADINGS
================================*/
h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #007bff;
    font-size: 26px;
    letter-spacing: 0.5px;
}

/* ===============================
   FORM ELEMENTS
================================*/
label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 18px;
    border: 1px solid #ccd4dd;
    border-radius: 6px;
    font-size: 15px;
    background: #fafbfd;
    transition: border-color 0.25s, box-shadow 0.25s;
}

input[type="email"]:focus,
input[type="password"]:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
    outline: none;
}

/* ===============================
   LOGIN BUTTON
================================*/
button {
    width: 100%;
    padding: 13px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    letter-spacing: 0.3px;
    transition: background-color 0.3s, transform 0.2s;
}

button:hover {
    background: #005ecb;
    transform: translateY(-2px);
}

/* ===============================
   MESSAGES
================================*/
p.message {
    background: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 20px;
    animation: fadeMsg 4s forwards;
}

@keyframes fadeMsg {
    0%   { opacity: 1; }
    80%  { opacity: 1; }
    100% { opacity: 0; }
}

/* ===============================
   REGISTER LINK
================================*/
.register-link {
    text-align: center;
    margin-top: 15px;
}

.register-link a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.register-link a:hover {
    text-decoration: underline;
}


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
