<?php
require_once __DIR__ . '/../app/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Safe access
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_name = $_SESSION['user_name'] ?? 'User';

// Fetch last 5 leads
$stmt = $pdo->query("SELECT * FROM leads ORDER BY id DESC LIMIT 5");
$leads = $stmt->fetchAll();

// Total leads
$totalLeadsStmt = $pdo->query("SELECT COUNT(*) AS total FROM leads");
$totalLeads = $totalLeadsStmt->fetch()['total'] ?? 0;

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
body { font-family: Arial, sans-serif; margin:0; background:#f7f8fc; }

/* Navbar */
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar a:hover { text-decoration:none; }

.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }

.navbar a.nav-btn {
    color:#fff; text-decoration:none; margin-left:5px; /* Reduced space */
    font-weight:bold; padding:6px 12px; border-radius:4px;
    transition:background 0.3s;
}


/* Container */
.container {
    max-width:1000px; margin:100px auto 40px auto; 
    background:#fff; border-radius:10px; 
    box-shadow:0 4px 12px #0001; padding:30px 28px;
}

/* Dashboard */
.dashboard-box h1 { font-size:2em; margin-bottom:15px; }
.dashboard-box ul { list-style: disc inside; padding-left:0; margin-bottom:20px; }
.dashboard-box ul li { margin-bottom:6px; font-size:1.1em; }

/* Role Badge */
.role-badge {
    display:inline-block; background:#2980b9; color:white; 
    padding:5px 12px; border-radius:20px; font-size:0.9em;
    margin-bottom:20px;
}

/* Table */
.leads-table { width:100%; border-collapse:collapse; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.leads-table th, .leads-table td { border:1px solid #ccc; padding:12px 10px; text-align:left; }
.leads-table th { background-color:#2980b9; color:#fff; font-weight:bold; }
.leads-table tr:nth-child(even) { background-color:#f2f2f2; }
.leads-table tr:hover { background-color:#e6f7ff; }
</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">Chandusoft Admin</div>
    <div class="navbar-right">
        <span>Welcome <?= htmlspecialchars($user_role) ?>!</span>
        <a href="dashboard.php" class="nav-btn">Dashboard</a>
        <a href="pages.php" class="nav-btn">Pages</a>
        <a href="admin-leads.php" class="nav-btn">Leads</a>
        <a href="logout.php" class="nav-btn logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="dashboard-box">
        <h1>Dashboard</h1>
    
        <ul>
            <li>Total leads: <?= $totalLeads ?></li>
            <li>Pages published: <?= $pagesPublished ?></li>
            <li>Pages draft: <?= $pagesDraft ?></li>
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
                    <td><?= htmlspecialchars($lead['name']) ?></td>
                    <td><?= htmlspecialchars($lead['email']) ?></td>
                    <td><?= htmlspecialchars($lead['message']) ?></td>
                    <td><?= htmlspecialchars($lead['created_at']) ?></td>
                    <td><?= htmlspecialchars($lead['ip']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
