<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        die("❌ Invalid CSRF token");
    }

    // Check user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {

        // Generate OTP
        $otp = rand(100000, 999999);

        // Store OTP in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

        // Send email
        mailLog(
            "🔐 Chandusoft Admin OTP",
            "Your OTP is: {$otp}\n\nThis OTP expires in 5 minutes."
        );

        // Redirect to OTP page
        header("Location: password_verify_otp.php");
        exit;

    } else {
        $message = "❌ Email not found.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.box {
    width: 420px;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Heading */
h2 {
    text-align: center;
    color: #1E90FF;
    font-size: 26px;
    margin-bottom: 25px;
}

/* Labels */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
    font-size: 15px;
}

/* Input fields */
input[type="text"],
input[type="password"],
input[type="email"] {
    width: 100%;
    padding: 14px 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
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

/* Messages */
.message {
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 20px;
    font-size: 15px;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
}

.success {
    background-color: #d4edda;
    color: #155724;
}

    </style>
</head>
<body>

<div class="box">
    <h2>Reset Password</h2>

    <?php if ($message): ?>
        <div class="message <?= stripos($message,'✔')!==false ? 'success':'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label>Email Address</label>
        <input type="email" name="email" required placeholder="Enter your email">

        <button type="submit">Send OTP</button>
    </form>

</div>

</body>
</html>
