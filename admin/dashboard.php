<?php
require_once __DIR__ . '/../app/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch last 5 leads from DB
$stmt = $pdo->prepare("SELECT * FROM leads ORDER BY id DESC LIMIT 5");
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total leads
$totalLeadsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads");
$totalLeadsStmt->execute();
$totalLeads = $totalLeadsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Example pages stats
$pagesPublished = 1;
$pagesDraft = 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chandusoft Admin Dashboard</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f7f8fc;
}

/* Navbar */
.navbar {
    width: 100%;
    background: #34495e;
    color: #fff;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    z-index: 1000;
}
.navbar-left, .navbar-right {
    display: flex;
    align-items: center;
}
.brand {
    font-weight: bold;
    font-size: 22px;
}
.navbar-right a {
    color: #fff;
    text-decoration: none;
    margin: 0 12px;
    font-size: 17px;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background 0.3s;
}
.navbar-right a:hover {
    background: #1E90FF;
}
.welcome-btn {
    background: #5d6d7e;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 7px 15px;
    font-weight: bold;
    margin-right: 10px;
    cursor: pointer;
}

/* Container */
.container {
    max-width: 1000px;
    margin: 100px auto 40px auto; /* offset for fixed navbar */
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px #0001;
    padding: 30px 28px;
}

/* Dashboard Content */
.dashboard-box h1 {
    font-size: 2em;
    margin-bottom: 7px;
}
.dashboard-box ul {
    margin-top:0;
    list-style:none;
    padding:0;
}
.dashboard-box ul li {
    margin-bottom:5px;
    font-size:1.1em;
}
.dashboard-box h2 {
    margin-top:30px;
    margin-bottom:12px;
}
.role-badge {
    display:inline-block;
    background:#2980b9;
    color:white;
    padding:5px 12px;
    border-radius:20px;
    font-size:0.9em;
    margin-bottom:20px;
}

/* Table Styling */
.leads-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.leads-table th, .leads-table td {
    border: 1px solid #ccc;
    padding: 12px 10px;
    text-align: left;
}
.leads-table th {
    background-color: #2980b9;
    color: #fff;
    font-weight: bold;
}
.leads-table tr:nth-child(even) {
    background-color: #f2f2f2;
}
.leads-table tr:hover {
    background-color: #e6f7ff;
}

/* Logout button */
.logout-btn {
    margin-top:20px;
    padding:10px 20px;
    background:#d9534f;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    transition:background 0.3s;
}
.logout-btn:hover {
    background:#c9302c;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-left">
        <span class="brand">Chandusoft Admin</span>
    </div>
    <div class="navbar-right">
        <button class="welcome-btn">Welcome <?php echo htmlspecialchars($_SESSION['user_role']); ?>!</button>
        <a href="dashboard.php">Dashboard</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/pages.php">Pages</a>
        <form style="display:inline;" action="logout.php" method="post">
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
</nav>

<!-- Main Container -->
<div class="container">
    <div class="dashboard-box">
        <h1>Dashboard</h1>
        <div class="role-badge">Role: <?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
        <ul>
            <li>Total leads: <?php echo $totalLeads; ?></li>
            <li>Pages published: <?php echo $pagesPublished; ?></li>
            <li>Pages draft: <?php echo $pagesDraft; ?></li>
        </ul>

        <h2>Last 5 Leads</h2>
        <table class="leads-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Created</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                <tr>
                    <td><?php echo htmlspecialchars($lead['name']); ?></td>
                    <td><?php echo htmlspecialchars($lead['email']); ?></td>
                    <td><?php echo htmlspecialchars($lead['message']); ?></td>
                    <td><?php echo htmlspecialchars($lead['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($lead['ip']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
