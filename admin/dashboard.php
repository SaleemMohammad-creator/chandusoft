<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false, // true if HTTPS
        'cookie_samesite' => 'Strict'
    ]);
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// ----------------------
// Fetch total leads
// ----------------------
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM leads");
$totalLeads = $stmt->fetch()['total'] ?? 0;

// ----------------------
// Fetch pages count by status
// ----------------------
$statusStmt = $pdo->query("
    SELECT status, COUNT(*) AS count 
    FROM pages 
    GROUP BY status
");
$pagesStatus = [];
while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
    $pagesStatus[ucfirst(strtolower($row['status']))] = $row['count'];
}

// Ensure all statuses exist
$statuses = ['Published', 'Archived', 'Draft'];
foreach ($statuses as $status) {
    if (!isset($pagesStatus[$status])) {
        $pagesStatus[$status] = 0;
    }
}

// ----------------------
// Fetch last 5 leads
// ----------------------
$leadsStmt = $pdo->query("SELECT * FROM leads ORDER BY id DESC LIMIT 5");
$leads = $leadsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; margin:0; background:#f7f8fc; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }
.navbar a.nav-btn { color:#fff; text-decoration:none; margin-left:5px; font-weight:bold; padding:6px 12px; border-radius:4px; transition:background 0.3s; }
.navbar a.nav-btn:hover { background:#1C86EE; }
.container { max-width:1000px; margin:100px auto 40px auto; background:#fff; border-radius:10px; box-shadow:0 4px 12px #0001; padding:30px 28px; }
.leads-table { width:100%; border-collapse:collapse; }
.leads-table th, .leads-table td { border:1px solid #ccc; padding:12px; text-align:left; }
.leads-table th { background:#2980b9; color:#fff; }
.leads-table tr:nth-child(even) { background:#f2f2f2; }
.leads-table tr:hover { background:#e6f7ff; }
.status-box { display:inline-block; background:#2980b9; color:#fff; padding:10px 20px; margin-right:10px; border-radius:6px; margin-bottom:10px; }
.status-box.published { background: #27ae60; }
.status-box.archived { background: #7f8c8d; }
.status-box.draft { background: #f1c40f; color:#000; }
</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">Chandusoft Admin</div>
    <div class="navbar-right">
        <span>Welcome <?= htmlspecialchars($user_role)?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>
        <!-- Dynamic catalog link based on user role -->
    <?php if ($user_role === 'admin'): ?>
    <a href="/admin/catalog.php">Admin Catalog</a>
    <?php endif; ?>
    <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Dashboard</h1>

    <p>Total Leads: <?= (int)$totalLeads ?></p>

    <h2>Pages by Status</h2>
    <div class="status-box published">Published: <?= (int)$pagesStatus['Published'] ?></div>
    <div class="status-box archived">Archived: <?= (int)$pagesStatus['Archived'] ?></div>
    <div class="status-box draft">Draft: <?= (int)$pagesStatus['Draft'] ?></div>

    <h2>Last 5 Leads</h2>
    <table class="leads-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Message</th><th>Created</th><th>IP</th></tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $lead): ?>
            <tr>
                <td><?= htmlspecialchars($lead['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($lead['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($lead['message'] ?? '') ?></td>
                <td><?= htmlspecialchars($lead['created_at'] ?? '') ?></td>
                <td><?= htmlspecialchars($lead['ip'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>