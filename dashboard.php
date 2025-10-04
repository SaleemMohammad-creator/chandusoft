<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<style>
body { font-family: Arial; background:#f7f7f7; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); text-align:center; }
button { padding:10px 20px; background:#d9534f; color:white; border:none; border-radius:4px; cursor:pointer; }
button:hover { background:#c9302c; }
</style>
</head>
<body>
<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
    <p>You are logged in.</p>
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</div>
</body>
</html>
