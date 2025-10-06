<?php
session_start();

// Include config for DB connection and CSRF
require_once __DIR__ . '/../app/config.php';

// Only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get user info
$user = $_SESSION['user'];
$role = htmlspecialchars($user['role'] ?? 'Editor');
$username = htmlspecialchars($user['name'] ?? 'User');

// Handle search
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE name LIKE :search_name OR email LIKE :search_email ORDER BY id ASC");
    $stmt->execute([
        ':search_name' => "%$search%",
        ':search_email' => "%$search%"
    ]);
    $leads = $stmt->fetchAll();
    $totalLeads = count($leads);
} else {
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY id ASC");
    $leads = $stmt->fetchAll();

    $stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM leads");
    $totalLeads = $stmtTotal->fetch()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Leads</title>
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f7f7f7; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar a:hover { text-decoration:underline; }
.container { max-width:1100px; margin:30px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { margin-bottom:10px; }
.total-leads { font-size:1.2em; font-weight:bold; margin-bottom:20px; }
form.search-form { margin-bottom:20px; display:flex; align-items:center; }
form.search-form input[type="text"] { padding:7px; width:250px; border:1px solid #ccc; border-radius:4px; }
form.search-form button { padding:7px 12px; margin-left:5px; background:#3498db; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }
table { border-collapse:collapse; width:100%; margin-bottom:30px; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background-color:#4CAF50; color:white; }
tr:nth-child(even) { background:#f2f2f2; }
tr:hover { background:#e6f7ff; }
.logout a { color:#d9534f; font-weight:bold; text-decoration:none; }
.logout a:hover { text-decoration:underline; }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div><strong>Chandusoft Admin</strong></div>
    <div>
        Welcome <?= $role ?>, <?= $username ?>!
        <a href="pages.php">Pages</a>
        <a href="admin-leads.php">Leads</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Leads</h1>
    <div class="total-leads">Total Leads: <?= $totalLeads ?></div>

    <!-- Search form -->
    <form class="search-form" method="GET" action="">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Message</th>
            <th>Submitted At</th>
            <th>IP</th>
        </tr>
        <?php if (!empty($leads)): ?>
            <?php foreach ($leads as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['ip']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No leads found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
