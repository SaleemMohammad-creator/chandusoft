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
   Top Bar / Filters
=========================== */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filters { 
    display: flex; 
    gap: 12px; 
}

.filters a { 
    padding: 8px 16px; 
    font-weight: bold; 
    text-decoration: none; 
    color: #3498db; 
    border-bottom: 2px solid transparent; 
    transition: 0.25s ease; 
}

.filters a:hover { 
    color: #1d6fa5; 
    border-bottom: 2px solid #1d6fa5; 
}

.filters a.active { 
    color: #1d6fa5; 
    border-bottom: 2px solid #1d6fa5; 
}

/* ===========================
   Search + Right Side
=========================== */
.right-side {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-left: auto; /* pushes to right */
}

/* Search Box */
.search-form {
    display: flex;
    align-items: center;
    gap: 6px;
    transform: translateX(-20px); /* move slightly left */
}

.search-form input[type="text"] {
    padding: 8px 12px;
    width: 260px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.search-form button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background: #3498db;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
    transition: 0.25s ease;
}

.search-form button:hover { 
    background: #1d6fa5; 
}

/* ===========================
   Create Button (Reduced Size)
=========================== */
.create-btn { 
    background: #27ae60; 
    color: #fff; 
    padding: 6px 14px;      /* reduced from 8px 18px */
    border-radius: 4px; 
    font-weight: bold; 
    font-size: 13px;        /* smaller text */
    text-decoration: none; 
    white-space: nowrap; 
    transition: 0.25s ease;
}

.create-btn:hover {
    background: #1e8c4d;
}

/* ===========================
   Table Styles
=========================== */
table { 
    width: 100%; 
    border-collapse: collapse; 
    font-size: 15px; 
}

th, td { 
    padding: 10px 12px; 
    border: 1px solid #ddd; 
    vertical-align: middle; 
    text-align: left; 
}

th { 
    background: #3498db; 
    color: #fff; 
}

tr:nth-child(even) { 
    background: #f9f9f9; 
}

tr:hover { 
    background: #eef7ff; 
}

/* ===========================
   Action Buttons
=========================== */
.actions button { 
    margin-right: 5px; 
    padding: 6px 14px; 
    border: none; 
    border-radius: 4px; 
    font-weight: bold; 
    cursor: pointer; 
    font-size: 14px; 
    transition: 0.2s ease; 
    min-width: 105px;
    text-align: center;
}

/* Edit */
.edit-btn { 
    background: #23b07d; 
    color: #fff; 
}
.edit-btn:hover {
    background: #1e9167;
}

/* Archive */
.archive-btn { 
    background: #f39c12; 
    color: #fff; 
}
.archive-btn:hover {
    background: #d9830d;
}

/* Unarchive */
.unarchive-btn { 
    background: #8e44ad; 
    color: #fff; 
}
.unarchive-btn:hover { 
    background: #71368a; 
}

/* Delete (Disabled) */
.delete-btn { 
    background: #c0392b; 
    color: #fff; 
    opacity: 0.6;
    cursor: not-allowed !important;
    pointer-events: none;
    border: none; 
    padding: 6px 14px; 
    border-radius: 4px; 
    font-weight: bold; 
    font-size: 14px;
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
