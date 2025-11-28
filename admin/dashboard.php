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
    font-family: "Inter", Arial, sans-serif;
    margin: 0;
    background: #f3f4f6;
    color: #111827;
}

/* ===========================
   Navbar
=========================== */
.navbar {
    background: #1f2937;
    color: #fff;
    padding: 16px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.navbar-left {
    font-size: 22px;
    font-weight: 700;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 14px;
    font-weight: 600;
}

.navbar a {
    padding: 8px 14px;
    margin-left: 10px;
    border-radius: 6px;
    font-weight: 600;
    color: #e5e7eb;
    text-decoration: none;
    transition: 0.25s ease-in-out;
}

.navbar a:hover {
    background: #374151;
    color: #fff;
}

.navbar a.active {
    background: #2563eb;
    color: #fff;
}

/* ===========================
   Container / Card Layout
=========================== */
.container {
    max-width: 1150px;
    margin: 90px auto 40px;
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 18px;
}

h2 {
    font-size: 20px;
    font-weight: 600;
    margin: 22px 0 12px;
}

/* ===========================
   Status Badges
=========================== */
.status-box {
    display: inline-block;
    padding: 10px 20px;
    margin-right: 12px;
    margin-bottom: 12px;
    border-radius: 8px;
    font-weight: 600;
    color: #fff;
    font-size: 14px;
}

.status-box.published {
    background: #16a34a;
}

.status-box.archived {
    background: #6b7280;
}

.status-box.draft {
    background: #facc15;
    color: #000;
}

/* ===========================
   Table Styling
=========================== */
.leads-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    font-size: 14px;
}

.leads-table thead {
    background: #2563eb;
    color: #fff;
}

.leads-table th,
.leads-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
}

.leads-table tr:nth-child(even) {
    background: #f9fafb;
}

.leads-table tr:hover {
    background: #eef7ff;
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