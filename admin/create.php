<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // PDO connection + verify_csrf()

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Safe user info
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_name = $_SESSION['user_name'] ?? 'User';

// CSRF token
$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$errors = [];
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $slug         = trim($_POST['slug'] ?? '');
    $content_html = $_POST['content_html'] ?? '';
    $status       = $_POST['status'] ?? 'draft';
    $csrf         = $_POST['csrf_token'] ?? '';

    // CSRF check
    if (!verify_csrf($csrf)) $errors[] = "Invalid CSRF token.";

    // Validation
    if ($title === '') $errors[] = "Title is required.";
    if ($slug === '') $slug = strtolower(str_replace(' ', '-', $title));

    // Check for duplicate slug
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    if ($stmt->fetch()) $errors[] = "Slug already exists. Choose another.";

    // If no errors, insert page
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO pages (title, slug, status, content_html)
            VALUES (:title, :slug, :status, :content_html)
        ");
        $stmt->execute([
            ':title'        => $title,
            ':slug'         => $slug,
            ':status'       => $status,
            ':content_html' => $content_html
        ]);
        $success = "Page created successfully!";
        // Reset form
        $title = $slug = $content_html = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Page - Admin</title>
<link rel="stylesheet" href="assets/css/styles.css">
<style>
    body { font-family: Arial; padding:20px; background:#f4f4f4; }
    .container { max-width:800px; margin:auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    input[type=text], select, textarea { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:4px; }
    button { padding:10px 20px; border:none; background:#27ae60; color:#fff; cursor:pointer; border-radius:4px; font-weight:bold; }
    button:hover { background:#1d6fa5; }
    .error { color:#c0392b; margin-bottom:15px; }
    .success { color:#27ae60; margin-bottom:15px; }
</style>
</head>
<body>
<div class="container">
    <h2>Create New Page</h2>

    <?php if (!empty($errors)): ?>
        <div class="error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>

        <label>Slug</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($slug ?? '') ?>" placeholder="Leave empty to auto-generate">

        <label>Content (HTML allowed)</label>
        <textarea name="content_html" rows="10"><?= htmlspecialchars($content_html ?? '') ?></textarea>

        <label>Status</label>
        <select name="status">
            <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($status ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>

        <button type="submit">Create Page</button>
    </form>

    <p><a href="pages.php">&laquo; Back to Pages</a></p>
</div>
</body>
</html>
