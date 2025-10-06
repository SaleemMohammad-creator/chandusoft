<?php
session_start();

// Include config file (DB connection and session)
require_once __DIR__ . '/../app/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get user info
$user = $_SESSION['user'];
$role = htmlspecialchars(ucfirst($user['role'] ?? 'Editor'));
$username = htmlspecialchars($user['username'] ?? $user['name'] ?? 'User');

// Database connection (using $pdo from config.php)
$search = trim($_GET['search'] ?? '');

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE name LIKE :search OR email LIKE :search ORDER BY id ASC");
    $stmt->execute(['search' => "%$search%"]);
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
<title>Leads - Admin Panel</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin:0;
    padding:0;
}

/* Navbar */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #2c3e50;
    color: #fff;
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.navbar .logo {
    font-weight: bold;
    font-size: 18px;
}
.navbar .links {
    display: flex;
    align-items: center;
    gap: 15px;
}
.navbar .links a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
}
.navbar .links a:hover {
    text-decoration: underline;
}

/* Container */
.container {
    max-width: 1100px;
    margin: 20px auto;
    background: #fff;
    padding: 30px;
    border-radius:8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

/* Top bar (search) */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.top-bar form {
    display: flex;
    align-items: center;
}
.top-bar input[type="text"] {
    padding:7px;
    width:250px;
    border:1px solid #ccc;
    border-radius:4px;
    margin-right:6px;
}
.top-bar button {
    padding:7px 12px;
    border:none;
    border-radius:4px;
    background:#3498db;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
}
.top-bar button:hover {
    background:#2980b9;
}

/* Table */
table {
    border-collapse: collapse;
    width:100%;
}
th, td {
    border:1px solid #ddd;
    padding:12px;
    text-align:left;
}
th {
    background:#3498db;
    color:#fff;
}
tr:nth-child(even) { background:#f9f9f9; }
tr:hover { background:#eef7ff; }
.total-leads { font-size: 1.2em; font-weight: bold; margin-bottom: 15px; }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo">Chandusoft Admin</div>
    <div class="links">
        Welcome <?= $role ?>, <?= $username ?>!
        <a href="dashboard.php">Dashboard</a>
        <a href="pages.php">Pages</a>
        <a href="admin-leads.php">Leads</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- Main container -->
<div class="container">
    <div class="total-leads">Total Leads: <?= $totalLeads ?></div>

    <!-- Search Form -->
    <div class="top-bar">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Leads Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Submitted At</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($leads)): ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><?= htmlspecialchars($lead['name']) ?></td>
                        <td><?= htmlspecialchars($lead['email']) ?></td>
                        <td><?= htmlspecialchars($lead['message']) ?></td>
                        <td><?= htmlspecialchars($lead['created_at']) ?></td>
                        <td><?= htmlspecialchars($lead['ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No leads found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
