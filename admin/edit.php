<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // PDO connection

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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
        // Refresh page data
        $stmt->execute(['id' => $pageId]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Page - Admin</title>
<link rel="stylesheet" href="assets/css/styles.css">
<style>
    body { font-family: Arial; padding:20px; background:#f4f4f4; }
    .container { max-width:800px; margin:auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    input[type=text], select, textarea { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:4px; }
    button { padding:10px 20px; border:none; background:#3498db; color:#fff; cursor:pointer; border-radius:4px; font-weight:bold; }
    button:hover { background:#1d6fa5; }
    .error { color:#c0392b; margin-bottom:15px; }
    .success { color:#27ae60; margin-bottom:15px; }
</style>
</head>
<body>
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

        <button type="submit">Update Page</button>
    </form>

    <p><a href="pages.php">&laquo; Back to Pages</a></p>
</div>
</body>
</html>
