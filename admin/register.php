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
// Handle Registration (POST)
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

    // Check if email exists
    $check = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {

        $_SESSION['flash'] = "⚠️ Email already registered!";
        mailLog("Registration Failed - Email Exists", "Email: {$email}", 'register');

        header("Location: register.php");
        exit;
    }

    // Insert new user
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
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* Same layout as login page */
.register-page {
    max-width: 400px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 2px rgba(0,0,0,0.1);
}

.register-page h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 25px;
}

/* Label names */

label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
    color: #333;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 18px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background: #1E90FF;
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background: #187bcd;
}

.message {
    padding: 10px;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 20px;
}

.message.error {
    background-color: #f8d7da;
    color: #721c24;
}

.message.success {
    background-color: #d4edda;
    color: #155724;
}

.login-link {
    text-align: center;
    margin-top: 15px;
}
.login-link a {
    color: #1E90FF;
    font-weight: bold;
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
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <label for="name">Full Name</label>
    <input type="text" id="name" name="name" placeholder="Full Name" required>

    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" placeholder="Email Address" required>

    <label for="phone">Phone Number</label>
    <input type="text" id="phone" name="phone" placeholder="Phone Number" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Password" required>

    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

    <button type="submit">Register</button>
</form>


    <div class="login-link">
        Already have an account? <a href="login.php">Login Here</a>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
