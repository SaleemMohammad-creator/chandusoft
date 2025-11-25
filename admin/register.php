<?php
// ---------------------------
// Secure Session Start (MUST BE FIRST)
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
// Load Config + Helpers
// ---------------------------
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';

// ---------------------------
// Flash Message (PRG pattern)
// ---------------------------
$flash = $_SESSION['flash'] ?? '';
$_SESSION['flash'] = '';

// ---------------------------
// Handle Registration
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        die("❌ Invalid CSRF token");
    }

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = "❌ Invalid email format";
        mailLog("Registration Failed - Invalid Email", "Email: {$email}", 'register');
        header("Location: register.php");
        exit;
    }

    // Password match validation
    if ($password !== $confirm_password) {
        $_SESSION['flash'] = "❌ Passwords do not match";
        mailLog("Registration Failed - Password Mismatch", "Email: {$email}", 'register');
        header("Location: register.php");
        exit;
    }

    // Check existing email
    $check = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $_SESSION['flash'] = "⚠️ Email already registered!";
        mailLog("Registration Failed - Email Exists", "Email: {$email}", 'register');
        header("Location: register.php");
        exit;
    }

    // Insert user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $passwordHash]);

    $_SESSION['flash'] = "✅ Registration Successful! <a href='login.php'>Login Now</a>";
    mailLog("New User Registered", "Email: {$email}", 'register');

    header("Location: register.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - <?= sanitize($site_name) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* MAIN CARD - Same as Login */
.register-page {
    width: 100%;
    max-width: 450px;
    margin: 60px auto;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Heading */
.register-page h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 30px;
    font-size: 26px;
    font-weight: bold;
}

/* Labels */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

/* Inputs */
input {
    width: 100%;
    padding: 14px 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
}

input:focus {
    border-color: #1E90FF;
    outline: none;
    box-shadow: 0 0 5px rgba(30,144,255,0.25);
}

/* Password Wrapper */
.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 45px !important;
}

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

/* Button */
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

/* Flash message */
.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 15px;
}
.message.error {
    background: #f8d7da;
    color: #721c24;
}
.message.success {
    background: #d4edda;
    color: #155724;
}

/* Link */
.login-link {
    text-align: center;
    margin-top: 20px;
}
.login-link a {
    color: #1E90FF;
    font-weight: bold;
    text-decoration: none;
}
.login-link a:hover {
    text-decoration: underline;
}

/* =====================================================
   ADDED BLOCK — ICON INPUT SUPPORT (same as login)
   (Does NOT modify your existing CSS)
===================================================== */

/* WRAPPER WITH LEFT ICON */
.input-with-icon {
    position: relative;
    width: 100%;
    margin-bottom: 20px;
}

.input-with-icon input {
    width: 100%;
    height: 48px;
    padding: 0 45px 0 44px;   /* left icon + right eye */
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
}

/* LEFT ICON */
.field-icon-left {
    position: absolute;
    left: 12px;
    top: 50%;
    font-size: 19px;
    color: #555;
    transform: translateY(-50%);
}

/* RIGHT EYE ICON */
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
</style>
</head>

<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="register-page">

    <h2>Create Account</h2>

    <?php if ($flash): ?>
        <?php $isSuccess = stripos($flash, 'successful') !== false; ?>
        <p class="message <?= $isSuccess ? 'success' : 'error' ?>">
            <?= $flash ?>
        </p>
    <?php endif; ?>

    <form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<!-- Full Name -->
<div class="input-with-icon">
    <i class="bi bi-person field-icon-left"></i>
    <input type="text" id="name" name="name" placeholder="Full Name" required>
</div>

<!-- Email -->
<div class="input-with-icon">
    <i class="bi bi-envelope field-icon-left"></i>
    <input type="email" id="email" name="email" placeholder="Email Address" required>
</div>

<!-- Phone -->
<div class="input-with-icon">
    <i class="bi bi-telephone field-icon-left"></i>
    <input type="text" id="phone" name="phone" placeholder="Phone Number" required>
</div>

<!-- Password -->
<div class="input-with-icon">
    <i class="bi bi-shield-lock-fill field-icon-left"></i>
    <input type="password" id="password" name="password" placeholder="Password" required>
    
    <span class="toggle-password" onclick="togglePassword('password','eye1')">
        <i class="fa-solid fa-eye" id="eye1"></i>
    </span>
</div>

<!-- Confirm Password -->
<div class="input-with-icon">
    <i class="bi bi-shield-lock-fill field-icon-left"></i>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

    <span class="toggle-password" onclick="togglePassword('confirm_password','eye2')">
        <i class="fa-solid fa-eye" id="eye2"></i>
    </span>
</div>

<button type="submit">Register</button>
</form>

    <div class="login-link">
        Already Have An Account? <a href="login.php">Login Here</a>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- JS -->
<script>
function togglePassword(fieldId, eyeId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(eyeId);

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
