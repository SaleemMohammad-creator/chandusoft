<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php';

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

// Log setup
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/login_attempts.log';

// ---------------------------
// Handle Login Submission
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

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? 'User';
        $_SESSION['user_role'] = $user['role'] ?? 'Admin';

        file_put_contents($logFile, "[{$timestamp}] SUCCESS login | {$email} | IP: {$ip}\n", FILE_APPEND);
        mailLog("Admin Logged In", "Email: $email\nIP: $ip\nTime: $timestamp");

        log_action($user['id'], 'Login Success', "User $email logged in from $ip");

        header("Location: dashboard.php");
        exit;
    } 
    else {
        file_put_contents($logFile, "[{$timestamp}] FAILED login | {$email} | IP: {$ip}\n", FILE_APPEND);
        mailLog("Failed Login Attempt", "Email: $email\nIP: $ip\nTime: $timestamp");

        log_action(null, 'Login Failed', "Failed login attempt for $email from $ip");

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - <?= sanitize($site_name) ?></title>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* LOGIN CARD */
.login-page {
    width: 100%;
    max-width: 450px;
    margin: 60px auto;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.login-page h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 30px;
    font-size: 26px;
    font-weight: bold;
}

.login-page label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 14px 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
}

/* FIXED PASSWORD HEIGHT (NO SHRINKING BUG) */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding: 14px 40px 14px 12px !important; /* FIXED */
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
}

/* Eye Icon */
.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #777;
    font-size: 18px;
}

.toggle-password:hover {
    color: #1E90FF;
}

button {
    width: 100%;
    padding: 14px;
    background: #1E90FF;
    color: #fff;
    font-size: 17px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
button:hover {
    background: #187bcd;
}

.message {
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 20px;
}
.message.error {
    background: #f8d7da;
    color: #721c24;
}
.message.success {
    background: #d4edda;
    color: #155724;
}

.forgot-password {
    text-align: right;
    margin-top: -12px;
    margin-bottom: 20px;
}
.forgot-password a {
    color: #1E90FF;
    font-weight: bold;
    font-size: 14px;
}

.register-link {
    text-align: center;
    margin-top: 20px;
}
.register-link a {
    color: #1E90FF;
    font-weight: bold;
}
</style>
</head>

<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="login-page">
    <h2>Login</h2>

    <?php if ($message): ?>
        <?php $isSuccess = stripos($message, 'success') !== false; ?>
        <p class="message <?= $isSuccess ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label>Email</label>
        <input type="email" name="email" required placeholder="Enter your email">

        <label>Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" required placeholder="Enter your password">
            <span class="toggle-password" onclick="togglePassword()">
                <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </span>
        </div>

        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Register Here</a>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
function togglePassword() {
    const field = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>
