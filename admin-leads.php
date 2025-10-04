<?php
session_start();

// Change this to your desired password
define('ADMIN_PASSWORD', 'cstl1234');

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Incorrect password.";
    }
}

// ✅ Stop here if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
    </head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit; // ❗ Prevent rest of page from loading
}

// ✅ Database connection
$host = 'localhost';
$dbUser = 'root'; // Laragon default
$dbPass = '';     // Laragon default
$dbName = 'chandusoft';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Fetch leads
$result = $conn->query("SELECT * FROM leads ORDER BY id desc limit 5;");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leads - Admin Panel</title>
</head>
<body>
    <h2>Leads Table</h2>
    <p><a href="logout.php">Logout</a></p>
    <table border="1" cellpadding="6">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Message</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>


