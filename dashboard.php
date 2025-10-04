<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$success_msg = '';
if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}

$user = isset($_SESSION['user']) ? $_SESSION['user'] : 'Guest';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <?php if ($success_msg): ?>
        <p class="success"><?php echo htmlspecialchars($success_msg); ?></p>
    <?php endif; ?>

    <h1>Welcome, <?php echo htmlspecialchars($user); ?>!</h1>
    <a href="logout.php">Logout</a>
</body>
</html>
