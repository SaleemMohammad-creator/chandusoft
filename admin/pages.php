<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Safe user info
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle search
$search = trim($_GET['search'] ?? '');

// Handle archive action
if (isset($_GET['archive_id']) && $user_role === 'admin') {
    $archive_id = (int)$_GET['archive_id'];
    $stmt = $pdo->prepare("UPDATE pages SET status='archived' WHERE id=:id");
    $stmt->execute(['id' => $archive_id]);
    header("Location: pages.php");
    exit;
}

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

if ($search !== '') {
    $searchQuery = "title LIKE :s OR slug LIKE :s";
    if (strpos($query, 'WHERE') !== false) {
        $query .= " AND ($searchQuery)";
    } else {
        $query .= " WHERE $searchQuery";
    }
    $params['s'] = "%$search%";
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pages - Admin</title>
<style>
body { font-family: Arial; background:#f4f4f4; margin:0; padding:0; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:10px; font-weight:bold; padding:5px 10px; border-radius:4px; }
.navbar a:hover { text-decoration:none; background:#1a2a38; }

.container { max-width:1200px; margin:30px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }

.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.filters { display:flex; gap:10px; }
.filters a { padding:8px 16px; text-decoration:none; color:#3498db; font-weight:bold; border-bottom:2px solid transparent; transition:0.3s; }
.filters a:hover { color:#1d6fa5; border-bottom:2px solid #1d6fa5; }
.filters a.active { color:#1d6fa5; border-bottom:2px solid #1d6fa5; }

.right-side { display:flex; align-items:center; gap:10px; }

.search-form { display:flex; align-items:center; gap:5px; }
.search-form input[type="text"] { padding:8px 12px; width:250px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
.search-form button { padding:8px 16px; border:none; border-radius:4px; background:#3498db; color:#fff; font-weight:bold; cursor:pointer; font-size:14px; transition:0.3s; }
.search-form button:hover { background:#1d6fa5; }

.create-btn { background:#27ae60; color:#fff; padding:8px 18px; border-radius:4px; font-weight:bold; text-decoration:none; white-space:nowrap; }

table { border-collapse:collapse; width:100%; font-size:15px; }
th, td { border:1px solid #ddd; padding:10px 12px; text-align:left; vertical-align:middle; }
th { background:#3498db; color:#fff; }
tr:nth-child(even){background:#f9f9f9;}
tr:hover{background:#eef7ff;}

.actions button { margin-right:5px; padding:6px 14px; border:none; border-radius:4px; font-weight:bold; font-size:14px; cursor:pointer; }
.edit-btn { background:#23b07d; color:#fff; }
.archive-btn { background:#f39c12; color:#fff; }

.delete-btn { 
    background: #c0392b; 
    color: #fff; 
    cursor: not-allowed !important;   /* 🚫 shows blocked cursor */
    pointer-events: none;             /* disables click */
    opacity: 0.6;                     /* visually show disabled */
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
}
.delete-btn:hover { 
    background: #c0392b;              /* keep same color on hover */
}
</style>
</head>
<body>

<div class="navbar">
    <div><strong>Chandusoft Admin</strong></div>
    <div>
        Welcome <?= htmlspecialchars($user_role) ?>!
        <a href="dashboard.php">Dashboard</a>
        <a href="pages.php">Pages</a>
        <a href="admin-leads.php">Leads</a>
        <a href="logout.php">Logout</a>
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
            <a href="pages.php?filter=all" class="<?= $filter==='all'?'active':'' ?>">All (<?= count($pages) ?>)</a>
            <a href="pages.php?filter=published" class="<?= $filter==='published'?'active':'' ?>">Published (<?= count(array_filter($pages, fn($p)=>$p['status']==='published')) ?>)</a>
            <a href="pages.php?filter=draft" class="<?= $filter==='draft'?'active':'' ?>">Draft (<?= count(array_filter($pages, fn($p)=>$p['status']==='draft')) ?>)</a>
            <a href="pages.php?filter=archived" class="<?= $filter==='archived'?'active':'' ?>">Archived (<?= count(array_filter($pages, fn($p)=>$p['status']==='archived')) ?>)</a>
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
                    <?php if($user_role === 'admin' && ($page['status'] ?? '') !== 'archived'): ?>
                        <button class="archive-btn" onclick="if(confirm('Archive this page?')) window.location.href='pages.php?archive_id=<?= $page['id'] ?>'">Archive</button>
                    <?php endif; ?>
                    <?php if($user_role === 'admin'): ?>
                        <button class="delete-btn" 
                            onclick="if(confirm('Delete this page?')) window.location.href='delete.php?delete_id=<?= $page['id'] ?>'">
                            Delete
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No pages found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
