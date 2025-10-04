<?php
session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get error message if set
$error_msg = '';
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Login</h2>

    <?php if ($error_msg): ?>
        <p class="error"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="authenticate.php" method="post">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br><br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br><br>

        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <button type="submit">Login</button>
    </form>
</body>
</html>
