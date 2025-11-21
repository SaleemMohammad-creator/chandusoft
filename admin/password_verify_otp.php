<?php
require_once __DIR__ . '/../app/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";

// User must come from forgot password
if (empty($_SESSION['reset_email'])) {
    die("❌ Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($_SESSION['reset_otp'])) {
        $message = "❌ OTP not generated.";
    }
    // Check expiry
    elseif (time() > $_SESSION['otp_expiry']) {
        $message = "❌ OTP expired. Please request again.";
    }
    // Check match
    elseif ($otp == $_SESSION['reset_otp']) {

        // OTP success → remove OTP
        unset($_SESSION['reset_otp']);
        unset($_SESSION['otp_expiry']);

        header("Location: reset_password.php");
        exit;
    }
    else {
        $message = "❌ Invalid OTP.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* CARD BOX */
.verify-box {
    width: 100%;
    max-width: 450px;
    margin: 70px auto;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Title */
.verify-box h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 25px;
    font-size: 26px;
    font-weight: bold;
}

/* Labels */
.verify-box label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

/* Inputs */
input[type="text"] {
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

/* Back link */
.back-link {
    text-align: center;
    margin-top: 15px;
}
.back-link a {
    color: #1E90FF;
    text-decoration: none;
    font-weight: bold;
}
.back-link a:hover {
    text-decoration: underline;
}
</style>

</head>
<body>

<div class="verify-box">
    <h2>Verify OTP</h2>

    <?php if ($message): ?>
        <p class="message <?= (strpos($message, '❌') !== false) ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Enter OTP</label>
        <input type="text" name="otp" required placeholder="Enter 6-digit OTP">

        <button type="submit">Verify OTP</button>
    </form>

    <div class="back-link">
        <a href="forgot_password.php">← Back to Forgot Password</a>
    </div>
</div>

</body>
</html>
