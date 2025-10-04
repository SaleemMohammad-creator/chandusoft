<?php
require "config.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'])) {
        die("❌ Invalid CSRF token");
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "❌ Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
/* Same styles as register.php for consistency */
body { font-family: Arial; background:#f7f7f7; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); width:350px; }
h2 { text-align:center; margin-bottom:20px; }
input { width:100%; padding:10px; margin:8px 0 15px 0; border:1px solid #ccc; border-radius:4px; }
button { width:100%; padding:10px; background:#4CAF50; color:white; border:none; border-radius:4px; cursor:pointer; font-size:16px; }
button:hover { background:#45a049; }
p.message { padding:10px; border-radius:4px; text-align:center; margin-bottom:15px; }
p.message.error { background:#f8d7da; color:#721c24; }
p.register-link { text-align:center; margin-top:15px; }
</style>
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <?php
    if ($message) {
        echo "<p class='message error'>".htmlspecialchars($message)."</p>";
    }
    ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p class="register-link">Don't have an account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
