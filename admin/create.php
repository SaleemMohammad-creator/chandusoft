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

$error = '';
$success = '';
$csrf_token = $_SESSION['csrf_token'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $csrf   = $_POST['csrf_token'] ?? '';

    $errors = [];

    if (!verify_csrf($csrf)) $errors[] = "Invalid CSRF token.";
    if ($title === '') $errors[] = "Title is required.";
    if ($slug === '') $slug = strtolower(str_replace(' ', '-', $title)); // auto-generate slug

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, status) VALUES (:title, :slug, :status)");
        $stmt->execute([
            ':title'  => $title,
            ':slug'   => $slug,
            ':status' => $status
        ]);
        $success = "Page created successfully!";
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Page - Admin</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar a:hover { text-decoration:underline; }
.container { max-width: 800px; margin:30px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h2 { margin-top:0; }
form label { display:block; margin-bottom:8px; font-weight:bold; }
form input[type="text"], form select { width:100%; padding:10px; margin-bottom:20px; border:1px solid #ccc; border-radius:4px; }
form button { background-color:#3498db; color:#fff; padding:10px 20px; border:none; border-radius:4px; font-weight:bold; cursor:pointer; }
form button:hover { background-color:#2980b9; }
.message { margin-bottom:15px; padding:10px; border-radius:5px; }
.error { background-color:#f8d7da; color:#721c24; }
.success { background-color:#d4edda; color:#155724; }
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
    <h2>Create New Page</h2>

    <?php if ($error): ?>
        <div class="message error"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <label for="title">Page Title *</label>
        <input type="text" name="title" id="title" required>

        <label for="slug">Slug (optional)</label>
        <input type="text" name="slug" id="slug" placeholder="auto-generated if empty">

        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="published">Published</option>
            <option value="draft" selected>Draft</option>
            <option value="archived">Archived</option>
        </select>

        <button type="submit">Create Page</button>
    </form>
</div>

</body>
</html>
