<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php';

/* ============================================================
   Secure Session
============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $secure,
        'cookie_samesite' => 'Strict'
    ]);
}

/* ============================================================
   Remember Me (Auto Login)
============================================================ */
$tokenDir  = __DIR__ . '/../storage/tokens';
if (!is_dir($tokenDir)) mkdir($tokenDir, 0755, true);
$tokenFile = $tokenDir . '/remember_me_tokens.json';

function load_json($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
}
function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $tokenHash = hash('sha256', $token);
    $stored = load_json($tokenFile);

    if (!empty($stored[$tokenHash])) {
        $entry = $stored[$tokenHash];

        if ($entry['expires'] > time()) {
            // Fetch user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$entry['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                // Auto-login
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header("Location: dashboard.php");
                exit;
            }
        }
    }
}

/* ============================================================
   Redirect if already logged in
============================================================ */
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

/* ============================================================
   Flash Message + CSRF
============================================================ */
$message = $_SESSION['flash_message'] ?? '';
$_SESSION['flash_message'] = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ============================================================
   Login Attempt Protection
============================================================ */
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);

$attemptFile = $logDir . "/login_attempts.json";
$attempts = load_json($attemptFile);

define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCK_TIME', 01 * 60);  // 5 minutes lock

/* ============================================================
   Handle LOGIN POST
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $csrf = $_POST['csrf_token'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $key = $ip . "_" . strtolower($email);
    $now = time();
    $timestamp = date("Y-m-d H:i:s");

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $_SESSION['flash_message'] = "Invalid CSRF token";
        header("Location: login.php");
        exit;
    }

    // Check lockout
    if (!empty($attempts[$key]['lock_until']) && $attempts[$key]['lock_until'] > $now) {
        $_SESSION['flash_message'] = "Too many login attempts. Try again after 1 minutes.";
        header("Location: login.php");
        exit;
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    /* ============================================================
       SUCCESSFUL LOGIN
    ============================================================ */
    if ($user && password_verify($password, $user['password'])) {

        // Clear attempts
        unset($attempts[$key]);
        save_json($attemptFile, $attempts);

        // Set session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Log success
        mailLog("Admin Logged In", "Email: $email\nIP: $ip\nTime: $timestamp");
        log_action($user['id'], "Login Success", "$email logged in.");

        /* --------------------------- 
           Remember Me
        --------------------------- */
        if (!empty($_POST['remember_me'])) {
            $tokens = load_json($tokenFile);

            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);

            $expires = time() + (30 * 24 * 60 * 60); // 30 days

            $tokens[$hash] = [
                'user_id' => $user['id'],
                'expires' => $expires
            ];
            save_json($tokenFile, $tokens);

            setcookie(
                "remember_me",
                $token,
                [
                    "expires" => $expires,
                    "path" => "/",
                    "secure" => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    "httponly" => true,
                    "samesite" => "Strict"
                ]
            );
        }

        header("Location: dashboard.php");
        exit;
    }

    /* ============================================================
       FAILED LOGIN
    ============================================================ */
    $attempts[$key]['attempts'][] = $now;

    if (count($attempts[$key]['attempts']) >= MAX_LOGIN_ATTEMPTS) {
        $attempts[$key]['lock_until'] = $now + LOCK_TIME;
        $_SESSION['flash_message'] = "Too many login attempts. Try again after 5 minutes.";
    } else {
        $_SESSION['flash_message'] = "Invalid email or password";
    }

    save_json($attemptFile, $attempts);

    // Log attempt
    mailLog("Failed Login Attempt", "Email: $email\nIP: $ip\nTime: $timestamp");

    header("Location: login.php");
    exit;
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
    padding: 10px 40px;
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

/* EMAIL FIXED */
input[type="email"] {
    width: 100%;
    height: 48px;
    padding: 0 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
}

/* PASSWORD WRAPPER – FULLY FIXED */
.password-wrapper {
    position: relative;
    width: 100%;
    margin-bottom: 20px;
}

.password-wrapper input {
    width: 100%;
    height: 48px !important;
    line-height: 48px !important;
    padding: 0 42px 0 12px !important;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
    appearance: none;
}

/* Remove built-in clear/eye icons (Windows/Chrome) */
.password-wrapper input::-ms-reveal,
.password-wrapper input::-ms-clear {
    display: none !important;
}

/* Eye Icon */
.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: #777;
}

.toggle-password:hover {
    color: #1E90FF;
}

/* BUTTON */
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

/* MESSAGE BOX */
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

/* Forgot Password Link */
.forgot-password {
    text-align: right;
    margin-top: -10px;
    margin-bottom: 20px;
}

.forgot-password a {
    color: #1E90FF;
    font-weight: bold;
    font-size: 14px;
}

/* Register Link */
.register-link {
    text-align: center;
    margin-top: 20px;
}
.register-link a {
    color: #1E90FF;
    font-weight: bold;
}


/* ==========================================================
   NEW CSS ADDED BELOW (No changes above)
   Remember Me (left) + Forgot Password (right)
========================================================== */

/* Row: Remember Me + Forgot Password */
.remember-forgot-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* LEFT: Remember Me */
.remember-me {
    display: flex;
    align-items: center;
    font-size: 14px;
    margin: 0;
    cursor: pointer;
}

.remember-me input[type="checkbox"] {
    margin-right: 6px;
}

/* RIGHT: Forgot Password (new class) */
.forgot-password-link a {
    color: #1E90FF;
    font-weight: bold;
    font-size: 14px;
    text-decoration: none;
}

.forgot-password-link a:hover {
    text-decoration: underline;
}


</style>
</head>

<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="login-page">
    <h2>Login</h2>

    <?php if ($message): ?>
        <p class="message error"><?= htmlspecialchars($message) ?></p>
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

    <!-- Remember Me + Forgot Password -->
    <div class="remember-forgot-row">

        <!-- LEFT: Remember Me -->
        <label class="remember-me">
            <input type="checkbox" name="remember_me">
            Remember Me
        </label>

        <!-- RIGHT: Forgot Password -->
        <div class="forgot-password-link">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

    </div>

    <button type="submit">Login</button>
</form>


    <div class="register-link">
        Don't have an account? <a href="register.php">Register Here</a>
    </div>

</main>

<script>
function togglePassword() {
    const field = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (field.type === "password") {
        field.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
