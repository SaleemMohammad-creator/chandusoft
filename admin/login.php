<?php
require_once __DIR__ . '/../app/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Flash message system
if (!isset($_SESSION['flash_message'])) {
    $_SESSION['flash_message'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $csrf = $_POST['csrf_token'] ?? '';

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

    if (!verify_csrf($csrf)) {
        $_SESSION['flash_message'] = "Invalid CSRF token";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'Admin';
            $_SESSION['user_name'] = $user['name'] ?? 'User';

            // Log success
            file_put_contents($logFile, "[{$timestamp}] ✅ SUCCESS login | User: {$email} | IP: {$ip}\n", FILE_APPEND | LOCK_EX);

            header("Location: dashboard.php");
            exit;
        } else {
            // Log failure
            file_put_contents($logFile, "[{$timestamp}] ❌ FAILED login | Email: {$email} | IP: {$ip}\n", FILE_APPEND | LOCK_EX);

            // Flash message for failed login
            $_SESSION['flash_message'] = "Invalid email or password";
        }
    }

    // Redirect to same page to prevent form resubmission
    header("Location: login.php");
    exit;
}

// Capture flash message and clear it immediately
$message = $_SESSION['flash_message'] ?? '';
$_SESSION['flash_message'] = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
body { font-family: Arial; background:#f7f7f7; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#fff; padding:40px 50px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); width:400px; max-width:90%; position:relative; }
h2 { text-align:center; margin-bottom:20px; }
label { display:block; margin-bottom:8px; font-weight:bold; }
input { width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px; }
button { width:100%; padding:10px; background:#1E90FF; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; }
button:hover { background:#1C86EE; }
p.message {
    padding:10px;
    border-radius:4px;
    text-align:center;
    margin-bottom:15px;
    background:#f8d7da;
    color:#721c24;
    animation: fadeout 5s forwards;
}
@keyframes fadeout {
    0% {opacity:1;}
    80% {opacity:1;}
    100% {opacity:0; display:none;}
}
</style>
</head>
<body>
<div class="container">
    <h2>Admin Login</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
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
