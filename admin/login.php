<?php
require_once __DIR__ . '/../app/config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        $message = "Invalid CSRF token";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Store user as array
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ];
            header("Location: pages.php");
            exit;
        } else {
            $message = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
body { font-family: Arial; background:#f7f7f7; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#fff; padding:40px 50px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); width:400px; max-width:90%; }
h2 { text-align:center; margin-bottom:20px; }
label { display:block; margin-bottom:8px; font-weight:bold; }
input { width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px; }
button { width:100%; padding:10px; background:#1E90FF; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; }
button:hover { background:#1C86EE; }
p.message { padding:10px; border-radius:4px; text-align:center; margin-bottom:15px; background:#f8d7da; color:#721c24; }
</style>
</head>
<body>
<div class="container">
    <h2>Admin Login</h2>
    <?php if ($message) echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
