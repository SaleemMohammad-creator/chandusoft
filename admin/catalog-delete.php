<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Enable Mailpit Logs

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// ✅ Log viewing archived list (Mailpit + Storage)
mailLog(
    "Archived Catalog Viewed",
    "Viewed by Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'),
    'catalog'
);

// Handle restore action
if (isset($_GET['restore_id'])) {
    $restore_id = intval($_GET['restore_id']);

    $stmt = $pdo->prepare("UPDATE catalog SET status='published' WHERE id=?");
    $stmt->execute([$restore_id]);

    // ✅ Log restore action (Mailpit + Storage)
    mailLog(
        "Catalog Item Restored",
        "Item ID: $restore_id restored by Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'),
        'catalog'
    );

    header("Location: catalog-delete.php");
    exit;
}

// Pagination
$limit = 10;
$page_no = isset($_GET['page_no']) ? intval($_GET['page_no']) : 1;
$offset = ($page_no - 1) * $limit;

// Search
$search = trim($_GET['search'] ?? '');
$where = "WHERE status='archived'";
$params = [];
if($search){
    $where .= " AND (title LIKE :search OR short_desc LIKE :search)";
    $params[':search'] = "%$search%";
}

// Count total archived items
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog $where");
foreach($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$total_records = $countStmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch archived items
$stmt = $pdo->prepare("SELECT * FROM catalog $where ORDER BY created_at DESC LIMIT :offset, :limit");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archived Catalog Items</title>
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
    background: #2563eb !important;
    color: #fff;
}

/* ===========================
   Page Title
=========================== */
h2 {
    text-align: center;
    font-size: 26px;
    font-weight: 700;
    margin-top: 40px;
    color: #1f2937;
    margin-bottom: 25px;
}

/* ===========================
   Search Bar
=========================== */
.search-container {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.search-container input {
    padding: 12px 14px;
    width: 300px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    transition: 0.25s;
}

.search-container input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.25);
}

.search-container button {
    padding: 12px 18px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s;
}

.search-container button:hover {
    background: #1e4fd4;
}

/* ===========================
   Table
=========================== */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin: 0 auto;
    max-width: 1150px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

thead {
    background: #2563eb;
    color: #fff;
}

th, td {
    padding: 14px 16px;
    font-size: 14px;
    border-bottom: 1px solid #e5e7eb;
}

tr:nth-child(even) {
    background: #f9fafb;
}

tr:hover {
    background: #eef7ff;
}

img {
    max-width: 80px;
    border-radius: 6px;
}

/* ===========================
   Status Labels
=========================== */
.status-archived {
    color: #b91c1c;
    font-weight: 700;
}

/* ===========================
   Restore Button
=========================== */
.restore-btn {
    display: inline-block;
    padding: 8px 14px;
    background: #10b981;
    color: #fff !important;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.25s;
}

.restore-btn:hover {
    background: #0d9466;
}

/* ===========================
   Back Button
=========================== */
.back-wrapper {
    max-width: 1150px;
    margin: 25px auto 0 auto;
    text-align: right;
}

.back-btn {
    padding: 10px 20px;
    background: #6b7280;
    color: #fff !important;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.25s ease;
}

.back-btn:hover {
    background: #4b5563;
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
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: 0.2s;
}

.pagination a:hover {
    background: #d1d5db;
}

.pagination a.active {
    background: #2563eb;
    color: #fff;
}

</style>
</head>
<body>

 <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>

        <?php if ($user_role === 'admin'): ?>
        <a href="/admin/catalog.php"
           style="<?= (
                        $currentPage === 'catalog.php' || 
                        $currentPage === 'catalog-new.php' || 
                        $currentPage === 'catalog-edit.php' || 
                        $currentPage === 'catalog-delete.php'
                    ) 
                    ? 'background:#1E90FF; padding:6px 12px; border-radius:4px;' 
                    : '' ?>">
            Admin Catalog
        </a>
        <?php endif; ?>

        <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>


<h2>Archived Catalog Items</h2>

<!-- Search -->
<form method="get" class="search-container">
    <input type="text" name="search" placeholder="Search archived items..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<!-- Table -->
<table>
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Slug</th>
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
        <td><?= htmlspecialchars($item['slug']) ?></td>
        <td><?php if($item['image']): ?><img src="/uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"><?php endif; ?></td>
        <td><?= $item['price'] ?></td>
        <td class="status-archived"><?= $item['status'] ?></td>
        <td>
       <a href="?restore_id=<?= $item['id'] ?>" 
           onclick="return confirm('Restore this item to catalog?')" 
           class="restore-btn">
           Restore
       </a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="7" style="text-align:center;">No archived items found.</td></tr>
<?php endif; ?>
</table>

<div class="back-wrapper">
    <a href="/admin/catalog.php" class="back-btn">← Back To Catalog</a>
</div>

<!-- Pagination -->
<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++): ?>
    <a href="?page_no=<?= $i ?>&search=<?= htmlspecialchars($search) ?>" class="<?= $page_no==$i?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</body>
</html>