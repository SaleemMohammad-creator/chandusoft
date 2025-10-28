<?php
require_once __DIR__ . '/../app/config.php';



$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf($_POST['csrf_token'])) {
        die("❌ Invalid CSRF token");
    }

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format";
    } 
    // Check password match
    elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match";
    } 
    else {
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $check = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $message = "⚠️ Email Already Registered!";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $passwordHash]);
            $message = "✅ Registration Successful! <a href='login.php'>Login Now</a>";
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
    padding: 30px; /* Equal padding all sides */
    box-shadow: 0 0 15px rgba(0,0,0,0.15);
    border-radius: 8px;
    width: 350px;
    box-sizing: border-box; /* Ensure padding included in width */
}

h2 { 
    text-align: center;
    margin-bottom: 20px;
}

input {
    width: 100%;
    padding: 12px;  /* slightly more padding for better look */
    margin: 10px 0; /* equal top & bottom spacing */
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box; /* ensure full width includes padding */
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
    color: #e74c3c; /* optional: red for errors */
}
    </style>
</head>
<body>

<div class="form-container">
    <h2>Create Account</h2>
    <?php if ($message) echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
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