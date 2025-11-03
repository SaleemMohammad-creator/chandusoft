<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ added logging include

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_id = $_SESSION['user_id'] ?? 'Unknown';

// -------------------------
// Define BASE_URL fallback
// -------------------------
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://chandusoft.test');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', BASE_URL . '/uploads/');
}

// -------------------------
// Handle Archive action
// -------------------------
if (isset($_GET['archive_id'])) {
    $archive_id = (int)$_GET['archive_id'];
    $stmt = $pdo->prepare("UPDATE catalog SET status='archived' WHERE id=:id");
    $stmt->execute(['id' => $archive_id]);

    // ✅ Log archive action
    mailLog("Catalog Item Archived", "Item ID: {$archive_id} | Admin ID: {$user_id}", "catalog");

    $_SESSION['success_message'] = "Item archived successfully.";
    header("Location: catalog.php");
    exit;
}

// -------------------------
// Pagination
// -------------------------
$limit = 10;
$page_no = isset($_GET['page_no']) ? intval($_GET['page_no']) : 1;
$offset = ($page_no - 1) * $limit;

// ✅ Log pagination action
if ($page_no > 1) {
    mailLog("Catalog Page Viewed", "Page: {$page_no} | Admin ID: {$user_id}", "catalog");
}

// -------------------------
// Search
// -------------------------
$search = trim($_GET['search'] ?? '');
$params = [];
$where = "WHERE status != 'archived'";

if ($search !== '') {
    $search_escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
    $where .= " AND (title LIKE ? ESCAPE '\\\\' OR short_desc LIKE ? ESCAPE '\\\\')";
    $params[] = "%$search_escaped%";
    $params[] = "%$search_escaped%";

    // ✅ Log search action
    mailLog("Catalog Search Performed", "Keyword: {$search} | Admin ID: {$user_id}", "catalog");
}

// -------------------------
// Count total items
// -------------------------
$countSql = "SELECT COUNT(*) FROM catalog $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_records = $countStmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $limit));

// -------------------------
// Fetch items
// -------------------------
$sql = "SELECT * FROM catalog $where ORDER BY created_at DESC LIMIT $offset, $limit";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Log default list view
mailLog("Catalog List Viewed", "Admin ID: {$user_id}", "catalog");
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Catalog List - Admin</title>
<style>
body { 
    font-family: Arial; 
    margin:0; 
    background:#f7f8fc; 
}

/* ✅ Navbar fixed + safe improvements */
.navbar {
    background:#2c3e50;
    color:#fff;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position: fixed;
    top: 0;
    left: 0;
    width:100%;
    z-index:1000;
    box-sizing: border-box;
}

.navbar a { 
    color:#fff; 
    text-decoration:none; 
    margin-left:15px; 
    font-weight:bold; 
}
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }

.navbar a.nav-btn {
    color:#fff; 
    text-decoration:none; 
    margin-left:5px; 
    font-weight:bold; 
    padding:6px 12px; 
    border-radius:4px; 
    transition:background 0.3s; 
}
.navbar a.nav-btn:hover { background:#1C86EE; }

/* ✅ Prevent content overlap with fixed header */
.container {
    max-width:1000px;
    margin:100px auto 40px auto;
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px #0001;
    padding:30px 28px;
}

h2 { text-align:center; color:#007BFF; margin-bottom:20px; }

.search-container {
    display:flex; 
    justify-content:center; 
    gap:5px; 
    margin-bottom:20px; 
    flex-wrap: wrap;
}
.search-container input[type="text"] { 
    padding:8px; 
    border-radius:5px; 
    border:1px solid #ccc; 
    width:250px; 
}
.search-container button { 
    padding:8px 16px; 
    border-radius:5px; 
    border:none; 
    background:#007BFF; 
    color:#fff; 
    font-weight:bold; 
    cursor:pointer; 
    transition:0.3s;
}
.search-container button:hover { background:#0056b3;}

table { 
    border-collapse:collapse; 
    width:100%; 
    max-width:1200px; 
    margin:0 auto; 
    background:#fff; 
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
th, td { border:1px solid #ccc; padding:10px 12px; text-align:left;}
th { background:#007BFF; color:#fff; font-weight:bold;}
tr:nth-child(even) { background:#f5f5f5;}
tr:hover { background:#e3f0ff;}

img { max-width:80px; display:block; border-radius:5px;}

a { color:#007BFF; text-decoration:none; font-weight:bold; margin-right:8px;}
a:hover { text-decoration:none;}

.pagination { text-align:center; margin-top:15px;}
.pagination a { 
    padding:6px 12px; 
    border:1px solid #007BFF; 
    margin:0 3px; 
    border-radius:5px; 
    text-decoration:none; 
    color:#007BFF; 
}
.pagination a.active { background:#007BFF; color:white; }

.message { text-align:center; margin-bottom:15px; color:green; font-weight:bold; }

/* ✅ New button styles added for actions */
.actions button { margin-right:5px; padding:6px 14px; border:none; border-radius:4px; font-weight:bold; font-size:14px; cursor:pointer; }
.edit-btn { background:#23b07d; color:#fff; }
.archive-btn { background:#f39c12; color:#fff; }

.delete-btn { 
    background: #c0392b; 
    color: #fff; 
    cursor: not-allowed !important;
    pointer-events: none;
    opacity: 0.6;
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
}
.delete-btn:hover { background: #c0392b; }

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
    <a href="/admin/orders.php">Orders</a>
    <?php endif; ?>
    <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>


<h2>Catalog List (Admin)</h2>

<?php
if(!empty($_SESSION['success_message'])) {
    echo '<div class="message">'.htmlspecialchars($_SESSION['success_message']).'</div>';
    unset($_SESSION['success_message']);
}
?>

<!-- Search & Add New -->
<form method="get" class="search-container">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <button type="button" onclick="window.location.href='catalog-new.php'">Add New</button>
    <button type="button" onclick="window.location.href='catalog-delete.php'">View Archived</button>
</form>

<!-- Catalog Table -->
<table>
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Image</th>
    <th>Price</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
<?php if($items): ?>
<?php foreach($items as $item): ?>
<tr>
    <td><?= $item['id'] ?></td>
    <td><?= htmlspecialchars($item['title']) ?></td>
    <td>
        <?php if(!empty($item['image'])): ?>
            <img src="<?= UPLOADS_URL . htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
        <?php endif; ?>
    </td>
    <td><?= number_format($item['price'], 2) ?></td>
    <td><?= htmlspecialchars($item['status']) ?></td>

    <td class="actions">
    <button class="btn edit-btn" onclick="window.location.href='catalog-edit.php?id=<?= $item['id'] ?>'">
        Edit
    </button>

    <button class="btn archive-btn"
        onclick="if(confirm('Archive this item?')) window.location.href='?archive_id=<?= $item['id'] ?>'">
        Archive
    </button>

    <button class="btn delete-btn"
        onclick="if(confirm('Delete this item?')) window.location.href='?delete_id=<?= $item['id'] ?>'">
        Delete
    </button>
</td>


</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6" style="text-align:center;">No items found.</td></tr>
<?php endif; ?>
</table>

<!-- Pagination -->
<div class="pagination">
<?php for($i=1; $i<=$total_pages; $i++): ?>
    <a href="?page_no=<?= $i ?>&search=<?= htmlspecialchars($search) ?>" class="<?= $page_no==$i?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</body>
</html>