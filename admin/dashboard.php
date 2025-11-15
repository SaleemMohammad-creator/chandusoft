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

// 👉 ADD THIS LINE HERE
$currentPage = basename($_SERVER['PHP_SELF']);   // e.g. dashboard.php

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
/* ===========================
   Global Styles
=========================== */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f7f8fc;
}

/* ===========================
   Navbar
=========================== */
.navbar {
    background: #2c3e50;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar a.active {
    background: #4da6ff;
    padding: 6px 12px;
    border-radius: 4px;
}

.navbar-left {
    font-size: 22px;
    font-weight: bold;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 12px;
    font-weight: bold;
}

.navbar a {
    color: #fff;
    text-decoration: none;
    margin-left: 12px;
    font-weight: bold;
}

.nav-btn {
    padding: 6px 12px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.nav-btn:hover {
    background: #1C86EE;
}

/* ===========================
   Container
=========================== */
.container {
    max-width: 1000px;
    margin: 100px auto 40px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 30px 28px;
}

/* ===========================
   Table Styling
=========================== */
.leads-table {
    width: 100%;
    border-collapse: collapse;
}

.leads-table th,
.leads-table td {
    border: 1px solid #ccc;
    padding: 12px;
    text-align: left;
}

.leads-table th {
    background: #2980b9;
    color: #fff;
    font-size: 15px;
}

.leads-table tr:nth-child(even) {
    background: #f2f2f2;
}

.leads-table tr:hover {
    background: #e6f7ff;
}

/* ===========================
   Status Badges
=========================== */
.status-box {
    display: inline-block;
    padding: 10px 20px;
    margin-right: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    font-weight: bold;
    color: #fff;
}

.status-box.published {
    background: #27ae60;
}

.status-box.archived {
    background: #7f8c8d;
}

.status-box.draft {
    background: #f1c40f;
    color: #000;
}

</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
        <a href="/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

  <?php if ($user_role === 'admin'): ?>
      <a href="/admin/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Admin Catalog</a>
      <a href="/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
   <?php endif; ?>

     <a href="/public/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Public Catalog</a>

    <a href="/admin/pages.php" class="<?= $currentPage === 'pages.php' ? 'active' : '' ?>">Pages</a>

    <a href="/admin/admin-leads.php" class="<?= $currentPage === 'admin-leads.php' ? 'active' : '' ?>">Leads</a>

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