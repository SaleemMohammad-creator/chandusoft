<?php
session_start();

// Admin login
define('ADMIN_PASSWORD', 'cstl1234');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Incorrect password.";
    }
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <html><head><title>Admin Login</title></head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </body></html>
    <?php exit;
}

// DB connection
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'chandusoft';
$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

// Fetch odd ID leads
$resultOdd = $conn->query("SELECT * FROM leads WHERE id % 2 = 1 ORDER BY id DESC");

// Fetch even ID leads
$resultEven = $conn->query("SELECT * FROM leads WHERE id % 2 = 0 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Leads - Admin Panel</title>
<style>
body { font-family: Arial; padding: 15px; background:#f7f7f7; }
h2 { margin-top: 40px; }
table { border-collapse: collapse; width: 70%; margin-bottom: 30px; }
th, td { border:1px solid #ccc; padding:15px; text-align:left; }
th { background-color:#4CAF50; color:white; }
tr:nth-child(even){background:#f2f2f2;}
tr:nth-child(odd){background:#ffffff;}
tr:hover{background:#e6f7ff;}
.logout { margin-bottom: 20px; }
.logout a { color:#d9534f; font-weight:bold; text-decoration:none; }
.logout a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="logout"><a href="logout.php">Logout</a></div>

<h2>Odd ID Leads</h2>
<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Submitted At</th></tr>
<?php while($row = $resultOdd->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['message']) ?></td>
<td><?= htmlspecialchars($row['created_at']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Even ID Leads</h2>
<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Submitted At</th></tr>
<?php while($row = $resultEven->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['message']) ?></td>
<td><?= htmlspecialchars($row['created_at']) ?></td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
