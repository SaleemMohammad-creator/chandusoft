<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';


// Handle search
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE name LIKE :s1 OR email LIKE :s2 ORDER BY id DESC");
    $stmt->execute([
        's1' => "%$search%",
        's2' => "%$search%"
    ]);
    $leads = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY id DESC");
    $leads = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leads - Admin</title>
<style>
body { font-family: Arial; margin:0; background:#f7f7f7; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar a:hover { text-decoration:none; color:#ddd; } /* No underline on hover, slightly lighter color */
.container { max-width:1100px; margin:30px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { margin-bottom:20px; }
form.search-form { margin-bottom:20px; display:flex; align-items:center; }
form.search-form input[type="text"] { padding:10px; width:450px; border:1px solid #ccc; border-radius:4px; font-size:16px; }
form.search-form button { padding:10px 20px; margin-left:10px; background:#1E90FF; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:16px; transition:background 0.3s; }
form.search-form button:hover { background:#1C86EE; }
table { border-collapse:collapse; width:100%; margin-top:20px; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background-color:#1E90FF; color:white; }
tr:nth-child(even) { background:#f2f2f2; }
tr:hover { background:#e6f7ff; }
</style>
</head>
<body>
 <div class="navbar">
    <div class="navbar-left">Chandusoft <?= htmlspecialchars($user_role) ?></div>
    <div>
        Welcome <?= htmlspecialchars($user_role) ?>!
        <a href="/admin/dashboard.php">Dashboard</a>
        <!-- Dynamic catalog link based on user role -->
    <?php if ($user_role === 'admin'): ?>
    <a href="/admin/catalog.php">Admin Catalog</a>
    <a href="/admin/orders.php">Orders</a>
    <?php endif; ?>
    <a href="/public/catalog.php">Public Catalog</a>
    
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
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