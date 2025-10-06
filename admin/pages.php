<?php
require_once __DIR__ . '/../app/config.php';

// Check login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$role = htmlspecialchars($user['role']);
$username = htmlspecialchars($user['name']);

$search = $_GET['search'] ?? '';

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE title LIKE :search OR slug LIKE :search ORDER BY id DESC");
    $stmt->execute(['search' => "%$search%"]);
    $pages = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM pages ORDER BY id DESC");
    $pages = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pages - Admin</title>
<style>
body { font-family: Arial; background:#f4f4f4; margin:0; padding:0; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar a:hover { text-decoration:underline; }
.container { max-width:1100px; margin:30px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.top-bar input[type="text"] { padding:7px; width:200px; border:1px solid #ccc; border-radius:4px; margin-right:6px; }
.top-bar button { padding:7px 12px; border:none; border-radius:4px; background:#3498db; color:#fff; font-weight:bold; cursor:pointer; }
.create-btn { background:#27ae60; color:#fff; padding:7px 16px; border-radius:4px; font-weight:bold; text-decoration:none; }
table { border-collapse:collapse; width:100%; }
th, td { border:1px solid #ddd; padding:12px; text-align:left; }
th { background:#3498db; color:#fff; }
tr:nth-child(even){background:#f9f9f9;}
tr:hover{background:#eef7ff;}
.actions button { margin-right:5px; padding:5px 10px; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }
.edit-btn { background:#23b07d; color:#fff; }
.delete-btn { background:#c0392b; color:#fff; }
</style>
</head>
<body>

<div class="navbar">
    <div><strong>Chandusoft Admin</strong></div>
    <div>
        Welcome <?= $role ?>, <?= $username ?>!
        <a href="pages.php">Pages</a>
        <a href="admin-leads.php">Leads</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="top-bar">
        <form method="get">
            <input type="text" name="search" placeholder="Search title or slug" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
        <a class="create-btn" href="create.php">+ Create New Page</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($pages)): ?>
            <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= htmlspecialchars($page['title']) ?></td>
                <td><?= htmlspecialchars($page['slug']) ?></td>
                <td><?= htmlspecialchars($page['status']) ?></td>
                <td class="actions">
                    <button class="edit-btn" onclick="window.location.href='edit.php?id=<?= $page['id'] ?>'">Edit</button>
                    <?php if ($role==='admin'): ?>
                    <button class="delete-btn" onclick="if(confirm('Delete this page?')) window.location.href='delete.php?id=<?= $page['id'] ?>'">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No pages found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
