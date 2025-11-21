<?php
require_once __DIR__ . '/../app/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$redirect = false;

// Must only access after OTP verification
if (empty($_SESSION['reset_email'])) {
    die("❌ Unauthorized access.");
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "❌ Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->execute([$hashed, $email]);

        unset($_SESSION['reset_email']);

        $message = "✔ Password successfully updated! Redirecting to login...";
        $redirect = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>

<?php if ($redirect): ?>
<!-- Auto redirect after 2 seconds -->
<meta http-equiv="refresh" content="2; URL=login.php">
<?php endif; ?>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* CARD BOX */
.reset-box {
    width: 100%;
    max-width: 450px;
    margin: 70px auto;
    background: #fff;
    padding: 35px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Title */
.reset-box h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 25px;
    font-size: 26px;
    font-weight: bold;
}

/* Labels */
.reset-box label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

/* Inputs */
input[type="password"] {
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

<div class="reset-box">
    <h2>Reset Password</h2>

    <?php if ($message): ?>
        <p class="message <?= (strpos($message, '✔') !== false) ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>New Password</label>
        <input type="password" name="password" required placeholder="Enter new password">

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required placeholder="Re-enter new password">

        <button type="submit">Reset Password</button>
    </form>
</div>

</body>
</html>
