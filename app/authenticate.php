<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'])) {
        die("❌ Invalid CSRF token");
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "❌ Invalid email or password!";
        header("Location: login.php");
        exit();
    }
}
