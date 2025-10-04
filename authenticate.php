<?php
session_start();

// Hardcoded credentials
$valid_email = "cstl@gmail.com";
$valid_user  = "Saleem";
$valid_password = "cstl1234";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === $valid_email && $password === $valid_password) {
        $_SESSION['email'] = $email;
        $_SESSION['user']  = $valid_user;

        $_SESSION['success'] = "Login Successful!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password!";
        header("Location: login.php");
        exit();
    }
}
?>
