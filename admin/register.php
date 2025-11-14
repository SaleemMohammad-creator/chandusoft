<?php
// ✅ MUST BE FIRST - start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Load config + helpers (CSRF token gets generated in config.php ONLY)
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ✅ CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        die("❌ Invalid CSRF token");
    }

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format";

        mailLog("Registration Failed - Invalid Email", "Email: {$email}", 'register');
    }
    // Validate password match
    elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match";

        mailLog("Registration Failed - Password Mismatch", "Email: {$email}", 'register');
    }
    else {

        // Hash user password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Check if email exists
        $check = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "⚠️ Email Already Registered!";

            mailLog("Registration Failed - Email Exists", "Email: {$email}", 'register');
        } else {

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $passwordHash]);
            $message = "✅ Registration Successful! <a href='login.php'>Login Now</a>";

            mailLog("New User Registered", "Email: {$email}", 'register');
        }
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
       body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
            border-radius: 8px;
            width: 350px;
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        .message {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
            color: #e74c3c;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Create Account</h2>

    <?php if ($message) echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <!-- ✅ CSRF Token (now stable and not re-generated elsewhere) -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <input type="text" name="name" placeholder="Full Name" required />
        <input type="email" name="email" placeholder="Email Address" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />

        <button type="submit">Register</button>
    </form>

    <div class="message">Already have an account? <a href="login.php">Login</a></div>
</div>

</body>
</html>
