<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ added logging include
require_once __DIR__ . '/../utilities/log_action.php';


// 👉 ADD THIS LINE HERE
$currentPage = $_SERVER['PHP_SELF'];

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

// ✅ Database Log (admin_logs)
if ($search) {
    log_action($user_id, 'Catalog Search', "Keyword: {$search}"); // 🔍 Log search keyword
} elseif ($page_no > 1) {
    log_action($user_id, 'Catalog Page Viewed', "Page: {$page_no}"); // 📄 Log page number
} else {
    log_action($user_id, 'Catalog List Viewed', "Viewed main catalog list"); // 📜 Default first page
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Catalog List - Admin</title>
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
}

.navbar a.active {
    background: #2563eb;
    color: #fff;
}

/* ===========================
   Container / Card
=========================== */
.container {
    max-width: 1150px;
    margin: 90px auto 40px;
    padding: 0 20px;
}

.card {
    background: #fff;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

h2 {
    font-size: 26px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 25px;
    color: #1f2937;
}

/* ===========================
   Search & Action Buttons
=========================== */
.search-container {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.search-container input[type="text"] {
    padding: 12px 14px;
    width: 300px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    background: #fff;
}

.search-container button {
    padding: 12px 18px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.25s;
}

.search-container button[type="submit"] {
    background: #2563eb;
    color: #fff;
}

.search-container button[type="submit"]:hover {
    background: #1e4fd4;
}

.search-container button:nth-child(3) {
    background: #16a34a;
    color: #fff;
}

.search-container button:nth-child(3):hover {
    background: #11803b;
}

.search-container button:nth-child(4) {
    background: #9333ea;
    color: #fff;
}

.search-container button:nth-child(4):hover {
    background: #7a22d1;
}

/* ===========================
   Catalog Table
=========================== */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 15px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

thead {
    background: #2563eb;
    color: #fff;
}

th, td {
    padding: 14px 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    text-align: left;
}

tr:nth-child(even) {
    background: #f9fafb;
}

tr:hover {
    background: #eef7ff;
}

img {
    max-width: 90px;
    border-radius: 8px;
}

/* ===========================
   Action Buttons
=========================== */
.actions button {
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: 0.25s;
    margin-right: 5px;
}

/* Edit */
.edit-btn {
    background: #10b981;
    color: #fff;
}
.edit-btn:hover {
    background: #0d9466;
}

/* Archive */
.archive-btn {
    background: #f59e0b;
    color: #fff;
}
.archive-btn:hover {
    background: #d98304;
}

/* Delete (disabled) */
.delete-btn {
    background: #dc2626;
    color: #fff;
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none;
}

/* ===========================
   Pagination
=========================== */
.pagination {
    text-align: center;
    margin-top: 25px;
}

.pagination a {
    display: inline-block;
    padding: 9px 15px;
    margin: 0 4px;
    background: #e5e7eb;
    color: #1f2937;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.2s;
}

.pagination a:hover {
    background: #d1d5db;
}

.pagination a.active {
    background: #2563eb;
    color: #fff;
}

/* ===========================
   Messages
=========================== */
.message {
    text-align: center;
    background: #dcfce7;
    border: 1px solid #16a34a;
    padding: 12px;
    border-radius: 6px;
    color: #14532d;
    margin: 10px auto 20px auto;
    max-width: 600px;
    font-weight: 600;
}


</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">
        Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?>
    </div>

    <div class="navbar-right">

        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>

        <!-- Dashboard -->
        <a href="/admin/dashboard.php"
           class="<?= $currentPage === '/admin/dashboard.php' ? 'active' : '' ?>">
           Dashboard
        </a>

        <!-- Admin-only menu -->
        <?php if ($user_role === 'admin'): ?>

            <a href="/admin/catalog.php"
               class="<?= $currentPage === '/admin/catalog.php' ? 'active' : '' ?>">
               Admin Catalog
            </a>

            <a href="/admin/orders.php"
               class="<?= $currentPage === '/admin/orders.php' ? 'active' : '' ?>">
               Orders
            </a>

        <?php endif; ?>

        <!-- Public Catalog -->
        <a href="/public/catalog.php"
           class="<?= $currentPage === '/public/catalog.php' ? 'active' : '' ?>">
           Public Catalog
        </a>

        <!-- Pages -->
        <a href="/admin/pages.php"
           class="<?= $currentPage === '/admin/pages.php' ? 'active' : '' ?>">
           Pages
        </a>

        <!-- Leads -->
        <a href="/admin/admin-leads.php"
           class="<?= $currentPage === '/admin/admin-leads.php' ? 'active' : '' ?>">
           Leads
        </a>

        <!-- Logout -->
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
    <td>$<?= number_format($item['price'], 2) ?></td>
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