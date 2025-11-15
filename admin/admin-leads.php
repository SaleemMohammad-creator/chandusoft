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

h1 {
    margin-bottom: 20px;
}

/* ===========================
   Search Form
=========================== */
form.search-form {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

form.search-form input[type="text"] {
    padding: 10px;
    width: 450px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

form.search-form button {
    padding: 10px 20px;
    margin-left: 10px;
    background: #1E90FF;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

form.search-form button:hover {
    background: #1C86EE;
}

/* ===========================
   Table
=========================== */
table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
}

th,
td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}

th {
    background: #1E90FF;
    color: #fff;
}

tr:nth-child(even) {
    background: #f2f2f2;
}

tr:hover {
    background: #e6f7ff;
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
    <h1>Leads</h1>
     <!-- Search form -->
    <form class="search-form" method="GET" action="">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name/email">
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