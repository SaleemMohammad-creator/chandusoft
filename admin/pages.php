<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // Mailpit Logger included
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Added logging helper

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 👉 ADD THIS LINE HERE
$currentPage = basename($_SERVER['PHP_SELF']);

// Safe user info
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_name = $_SESSION['user_name'] ?? 'User';
$user_id   = $_SESSION['user_id'] ?? null;

// ------------------------------------------------------
// HANDLE SEARCH INPUT
// ------------------------------------------------------
$search = trim($_GET['search'] ?? '');

// ❌ Block special characters (only allow letters, digits, space)
$invalidSearch = false;
if ($search !== '' && !preg_match('/^[a-zA-Z0-9 ]+$/', $search)) {
    $invalidSearch = true;
    $pages = []; // Return empty result
}

// Handle archive action
if (isset($_GET['archive_id']) && $user_role === 'admin') {
    $archive_id = (int)$_GET['archive_id'];
    $stmt = $pdo->prepare("UPDATE pages SET status='archived' WHERE id=:id");
    $stmt->execute(['id' => $archive_id]);

    // Log to Mailpit Inbox
    $subject = "Page Archived by Admin";
    $message = "User: {$user_name} ({$user_role}) has archived the page with ID: {$archive_id}.";
    mailLog($subject, $message);
    log_action($user_id, 'Page Archived', "Page ID: {$archive_id} by {$user_name}");

    header("Location: pages.php");
    exit;
}

// Handle unarchive action
if (isset($_GET['unarchive_id']) && $user_role === 'admin') {
    $unarchive_id = (int)$_GET['unarchive_id'];
    $stmt = $pdo->prepare("UPDATE pages SET status='draft' WHERE id=:id");
    $stmt->execute(['id' => $unarchive_id]);

    $subject = "Page Unarchived by Admin";
    $message = "User: {$user_name} ({$user_role}) has unarchived the page with ID: {$unarchive_id}.";
    mailLog($subject, $message);
    log_action($user_id, 'Page Unarchived', "Page ID: {$unarchive_id} by {$user_name}");

    header("Location: pages.php");
    exit;
}

// Added counts section
$totalCounts = [
    'all'       => $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn(),
    'draft'     => $pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft'")->fetchColumn(),
    'archived'  => $pdo->query("SELECT COUNT(*) FROM pages WHERE status='archived'")->fetchColumn(),
];

// Filter pages
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM pages";
$params = [];

if ($filter === 'published') {
    $query .= " WHERE status='published'";
} elseif ($filter === 'draft') {
    $query .= " WHERE status='draft'";
} elseif ($filter === 'archived') {
    $query .= " WHERE status='archived'";
}

// ------------------------------------------------------
// ONLY RUN SEARCH IF VALID (no special chars)
// ------------------------------------------------------
if ($search !== '' && !$invalidSearch) {

    $searchQuery = "title LIKE :s1 OR slug LIKE :s2";

    if (strpos($query, 'WHERE') !== false) {
        $query .= " AND ($searchQuery)";
    } else {
        $query .= " WHERE $searchQuery";
    }

    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";

    // Log search action
    $subject = "Search Performed in Pages";
    $message = "User: {$user_name} ({$user_role}) searched for: '{$search}'.";
    mailLog($subject, $message);
    log_action($user_id, 'Page Search', "Search term: {$search}");
}

$query .= " ORDER BY id DESC";

// If search is invalid, skip DB query
if ($invalidSearch) {
    $pages = [];
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Log viewing action (ONLY when not searching or doing actions)
if (!isset($_GET['archive_id']) && !isset($_GET['unarchive_id']) && $search === '') {
    $subject = "Pages Viewed";
    $message = "User: {$user_name} ({$user_role}) viewed the pages list (Filter: {$filter}).";
    mailLog($subject, $message);
    log_action($user_id, 'View Pages List', "Filter: {$filter}");
}
?>



<!-- Your existing HTML design and rendering remain unchanged -->

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pages - Admin</title>
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
   Container Card
=========================== */
.container {
    max-width: 1150px;
    margin: 90px auto 40px;
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

/* ===========================
   Top Bar / Filters / Buttons
=========================== */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.filters {
    display: flex;
    gap: 12px;
}

.filters a {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    color: #2563eb;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    transition: 0.2s;
}

.filters a:hover {
    background: #dbe4ff;
    border-color: #93b4ff;
}

.filters a.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

/* ===========================
   Search / Create Button
=========================== */
.right-side {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Search Form */
.search-form {
    display: flex;
    align-items: center;
    gap: 6px;
}

.search-form input[type="text"] {
    padding: 10px 12px;
    width: 260px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.search-form button {
    padding: 10px 16px;
    border-radius: 6px;
    border: none;
    background: #2563eb;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s ease;
}

.search-form button:hover {
    background: #1e4fd4;
}

/* Create Button */
.create-btn {
    background: #16a34a;
    color: #fff;
    padding: 9px 14px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: 0.25s ease;
}

.create-btn:hover {
    background: #11803b;
}

/* ===========================
   Pages Table
=========================== */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
    border-radius: 10px;
    overflow: hidden;
}

thead {
    background: #2563eb;
    color: #fff;
}

th, td {
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
}

tr:nth-child(even) {
    background: #f9fafb;
}

tr:hover {
    background: #eef7ff;
}

/* ===========================
   Action Buttons
=========================== */
.actions button {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s;
    min-width: 105px;
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

/* Unarchive */
.unarchive-btn {
    background: #8b5cf6;
    color: #fff;
}
.unarchive-btn:hover {
    background: #6d3ecf;
}

/* Delete (disabled) */
.delete-btn {
    background: #dc2626;
    color: #fff;
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none;
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

    <!-- Messages -->
    <?php
    if (!empty($_SESSION['success_message'])) {
        echo '<div class="message success">'.htmlspecialchars($_SESSION['success_message']).'</div>';
        unset($_SESSION['success_message']);
    }
    if (!empty($_SESSION['error_message'])) {
        echo '<div class="message error">'.htmlspecialchars($_SESSION['error_message']).'</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="top-bar">
        <div class="filters">
            <a href="pages.php?filter=all" class="<?= $filter==='all'?'active':'' ?>">All (<?= $totalCounts['all'] ?>)</a>
            <a href="pages.php?filter=published" class="<?= $filter==='published'?'active':'' ?>">Published (<?= $totalCounts['published'] ?>)</a>
            <a href="pages.php?filter=draft" class="<?= $filter==='draft'?'active':'' ?>">Draft (<?= $totalCounts['draft'] ?>)</a>
            <a href="pages.php?filter=archived" class="<?= $filter==='archived'?'active':'' ?>">Archived (<?= $totalCounts['archived'] ?>)</a>
        </div>

        <div class="right-side">
            <form class="search-form" method="get">
                <input type="text" name="search" placeholder="Search title or slug" value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
            <a class="create-btn" href="create.php">+ Create New Page</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pages</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($pages)): ?>
            <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= htmlspecialchars($page['title'] ?? '') ?></td>
                <td><?= htmlspecialchars($page['slug'] ?? '') ?></td>
                <td><?= htmlspecialchars($page['status'] ?? '') ?></td>
                <td><?= htmlspecialchars($page['created_at'] ?? '') ?></td>
                <td class="actions">
                    <button class="edit-btn" onclick="window.location.href='edit.php?id=<?= $page['id'] ?>'">Edit</button>

                    <?php if($user_role === 'admin'): ?>
                        <?php if($page['status'] === 'archived'): ?>
                            <button class="unarchive-btn" onclick="if(confirm('Unarchive this page?')) window.location.href='pages.php?unarchive_id=<?= $page['id'] ?>'">Unarchive</button>
                        <?php else: ?>
                            <button class="archive-btn" onclick="if(confirm('Archive this page?')) window.location.href='pages.php?archive_id=<?= $page['id'] ?>'">Archive</button>
                        <?php endif; ?>

                        <button class="delete-btn" onclick="if(confirm('Delete this page?')) window.location.href='delete.php?delete_id=<?= $page['id'] ?>'">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No Pages Found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
