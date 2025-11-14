<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // PDO connection
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Added for Mailpit Logging
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Added for admin action logging

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_id   = $_SESSION['user_id'] ?? null; // ✅ Added for logging

// Redirect if not logged in or not admin/editor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'])) {
    header("Location: login.php");
    exit;
}

$pageId = $_GET['id'] ?? null;
if (!$pageId) {
    header("Location: pages.php");
    exit;
}

// Fetch existing page
$stmt = $pdo->prepare("SELECT * FROM pages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $pageId]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header("Location: pages.php");
    exit;
}

$errors = [];
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content_html = $_POST['content_html'] ?? '';
    $status = $_POST['status'] ?? 'draft';

    // Validation
    if ($title === '') $errors[] = "Title is required.";
    if ($slug === '') $errors[] = "Slug is required.";

    // Check slug uniqueness
    $slugCheck = $pdo->prepare("SELECT id FROM pages WHERE slug = :slug AND id != :id");
    $slugCheck->execute(['slug' => $slug, 'id' => $pageId]);
    if ($slugCheck->fetch()) {
        $errors[] = "Slug already exists. Choose a different one.";
    }

    // If no errors, update the page
    if (empty($errors)) {
        $updateStmt = $pdo->prepare("UPDATE pages SET title = :title, slug = :slug, content_html = :content_html, status = :status WHERE id = :id");
        $updateStmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content_html' => $content_html,
            'status' => $status,
            'id' => $pageId
        ]);

        $success = "Page updated successfully.";

        // ✅ Log to Mailpit
        log_info("Page updated successfully by {$user_name} (Role: {$user_role}) — ID: {$pageId}, Title: {$title}");

        // ✅ Log to Database (admin_logs)
        log_action($user_id, 'Page Updated', "Page ID: {$pageId}, Title: {$title}, Slug: {$slug}, Status: {$status}");

        // Refresh page data
        $stmt->execute(['id' => $pageId]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // ✅ Log errors to Mailpit
        log_error("Page update failed by {$user_name} (Role: {$user_role}) — Errors: " . implode(', ', $errors));

        // ✅ Log failed attempt to Database
        $errorText = implode(', ', $errors);
        log_action($user_id, 'Page Update Failed', "Page ID: {$pageId}, Errors: {$errorText}");
    }
}
?>


<!-- Your original HTML form and structure remain untouched -->

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Page - Admin</title>
<link rel="stylesheet" href="assets/css/styles.css">
<style>
    body { font-family: Arial; margin:0; background:#f7f8fc; }

.navbar {
    background:#2c3e50;
    color:#fff;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;

    /* ✅ new lines */
    position: fixed;
    top: 0;
    left: 0;
    width:100%;
    z-index:1000;
    box-sizing: border-box;
}

.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }
.navbar a.nav-btn { color:#fff; text-decoration:none; margin-left:5px; font-weight:bold; padding:6px 12px; border-radius:4px; transition:background 0.3s; }
.navbar a.nav-btn:hover { background:#1C86EE; }

.container {
    max-width:1000px;
    margin:100px auto 40px auto;
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px #0001;
    padding:30px 28px;
}
  
input[type=text], select, textarea {
    width:100%; padding:10px; margin:8px 0;
    border:1px solid #ccc; border-radius:4px;
}

.form-buttons {
    display:flex; justify-content:space-between; margin-top:15px;
}

button {
    padding:10px 20px; border:none;
    background:#3498db; color:#fff; cursor:pointer;
    border-radius:4px; font-weight:bold; transition:background 0.3s;
}
button:hover { background:#1d6fa5; }

.error { color:#c0392b; margin-bottom:15px; }
.success { color:#27ae60; margin-bottom:15px; }
</style>
</head>
<body>
 
<div class="navbar">
    <div class="navbar-left">Chandusoft <?= htmlspecialchars($user_role) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= htmlspecialchars($user_role)?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>
        <?php if ($user_role === 'admin'): ?>
        <a href="/admin/catalog.php">Admin Catalog</a>
        <?php endif; ?>
        <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>
 
<div class="container">
    <h2>Edit Page</h2>

    <?php if ($errors): ?>
        <div class="error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>

        <label>Slug</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required>

        <label>Content (HTML allowed)</label>
        <textarea name="content_html" rows="10"><?= $page['content_html'] ?? '' ?></textarea>

        <label>Status</label>
        <select name="status">
            <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($page['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>

        <div class="form-buttons">
            <button type="submit">Update Page</button>
            <a href="pages.php"><button type="button">← Back to Pages</button></a>
        </div>
    </form>
</div>
</body>
</html>
