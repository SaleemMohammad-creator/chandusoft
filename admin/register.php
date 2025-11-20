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
    $phone = trim($_POST["phone"]);
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
            $stmt = $pdo->prepare(
    "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$name, $email, $phone, $passwordHash]);
            $message = "✅ Registration Successful! <a href='login.php'>Login Now</a>";

            mailLog("New User Registered", "Email: {$email}", 'register');
        }
    }
}

?>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register</title>
<link rel="stylesheet" href="/styles.css">
    <style>
      /* ===============================
   GLOBAL PAGE STYLE
================================*/
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #e8f1ff, #f7faff);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh; /* replace height */
    margin: 0;

    padding-top: 120px; /* space below header */
    box-sizing: border-box;
}


/* ===============================
   FORM CONTAINER
================================*/
.form-container {
    background: #ffffff;
    padding: 35px;
    border-radius: 12px;
    width: 360px;
    max-width: 90%;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
    box-sizing: border-box;
    animation: fadeInUp 0.5s ease-out;
}

/* Smooth fade + slide animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
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
    color: #2c3e50;
    letter-spacing: 0.5px;
}

/* ===============================
   FORM INPUTS
================================*/
input {
    width: 100%;
    padding: 12px 14px;
    margin: 12px 0;
    border: 1px solid #ccd4dd;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 15px;
    background: #fbfcfe;
    transition: border-color 0.25s, box-shadow 0.25s;
}

input:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    outline: none;
}

/* ===============================
   BUTTON
================================*/
button {
    width: 100%;
    padding: 12px;
    background: #3498db;
    border: none;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
    letter-spacing: 0.3px;
}

button:hover {
    background: #2c82c9;
    transform: translateY(-2px);
}

/* ===============================
   MESSAGE BOX
================================*/
.message {
    margin-top: 15px;
    text-align: center;
    font-size: 14px;
    color: #e74c3c;
    animation: fadeOut 4s forwards;
}

/* Smooth fade-out for message */
@keyframes fadeOut {
    0%   { opacity: 1; }
    75%  { opacity: 1; }
    100% { opacity: 0; }
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
        <input type="phone" name="phone" placeholder="Phone Number" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />

        <button type="submit">Register</button>
    </form>

    <div class="message">Already have an account? <a href="login.php">Login</a></div>
</div>

</body>
</html>
