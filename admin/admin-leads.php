<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ log helper

// Secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 👉 ADD THIS LINE HERE
$currentPage = basename($_SERVER['PHP_SELF']); 

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// User info
$user_id   = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// Handle search
$search = trim($_GET['search'] ?? '');

// ===============================================
// ❌ BLOCK WILDCARD-ONLY SEARCHES LIKE %, %% , _
// ===============================================
$wildcard_only = preg_match('/^[%_]+$/', $search);

if ($search !== '' && !$wildcard_only) {

    // Safe search
    $stmt = $pdo->prepare("
        SELECT * FROM leads 
        WHERE name LIKE :s1 OR email LIKE :s2 
        ORDER BY id DESC
    ");

    $stmt->execute([
        's1' => "%$search%",
        's2' => "%$search%"
    ]);

    $leads = $stmt->fetchAll();

    // Log the valid search
    $details = sprintf("%s (%s) searched leads for: '%s'", $user_name, ucfirst($user_role), $search);
    log_action($user_id, 'Lead Search', $details);

} else {
    // If empty search OR wildcard-only → show nothing
    if ($wildcard_only) {
        $leads = []; // return empty results
    } else {
        // Load all leads normally when no search is done
        $stmt = $pdo->query("SELECT * FROM leads ORDER BY id DESC");
        $leads = $stmt->fetchAll();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leads - Admin</title>
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
   Container
=========================== */
.container {
    max-width: 1150px;
    margin: 90px auto 40px;
}

/* Card for content */
.card {
    background: #fff;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    margin-top: 20px;
}

h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 22px;
}

/* ===========================
   Search Form
=========================== */
.search-box {
    max-width: 380px;
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.search-box input {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    background: #fff;
}

.search-box button {
    padding: 12px 18px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.search-box button:hover {
    background: #1e4fd4;
}

/* ===========================
   Leads Table
=========================== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    border-radius: 10px;
    overflow: hidden;
}

thead {
    background: #2563eb;
    color: #fff;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

tr:nth-child(even) {
    background: #f9fafb;
}

tr:hover {
    background: #eef7ff;
}

/* Sub text (IP / Message) */
td small {
    color: #6b7280;
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
    <div class="card">

        <h1>Leads</h1>

        <form class="search-box" method="get">
            <input type="text" name="search" placeholder="Search name/email">
            <button type="submit">Search</button>
        </form>
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
      <?php foreach ($leads as $row): ?>
    <tr>
        <td><?= (int)($row['id'] ?? 0) ?></td>
        <td><?= htmlspecialchars((string)($row['name'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['email'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['message'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['ip'] ?? '')) ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" style="text-align:center;">No leads found.</td></tr>
<?php endif; ?>
</tbody>
    </table>
</div>

</body>
</html>