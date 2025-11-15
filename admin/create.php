<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // PDO connection + verify_csrf()
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Added for Mailpit logging
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Added logging helper

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_id   = $_SESSION['user_id'] ?? null;

// Allow both admin and editor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'])) {
    header("Location: login.php"); // Or dashboard.php
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

        // ✅ Log to Mailpit inbox
        $subject = "New Page Created by {$user_name}";
        $message = "
        A new page has been created by {$user_name} ({$user_role}).
        <br><br>
        <b>Title:</b> {$title}<br>
        <b>Slug:</b> {$slug}<br>
        <b>Status:</b> {$status}
        ";
        mailLog($subject, $message);

        // ✅ Log to Database (admin_logs)
        log_action($user_id, 'Page Created', "Title: {$title}, Slug: {$slug}, Status: {$status}");

        // Reset form
        $title = $slug = $content_html = '';
    } else {
        // ✅ Log failed creation attempt
        $errorText = implode(', ', $errors);
        log_action($user_id, 'Page Create Failed', $errorText);
    }
}
?>


<!-- Your existing HTML form remains unchanged -->


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Page - Admin</title>
<link rel="stylesheet" href="assets/css/styles.css">
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
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-sizing: border-box;
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
   Form Inputs
=========================== */
input[type="text"],
select,
textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
    box-sizing: border-box;
}

/* ===========================
   Buttons
=========================== */
button {
    padding: 10px 20px;
    border: none;
    background: #3498db;
    color: #fff;
    cursor: pointer;
    border-radius: 4px;
    font-weight: bold;
    font-size: 15px;
    transition: background 0.3s ease;
}

button:hover {
    background: #1c7ec9;
}

.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

/* ===========================
   Alerts
=========================== */
.error {
    color: #c0392b;
    margin-bottom: 15px;
    font-weight: bold;
}

.success {
    color: #27ae60;
    margin-bottom: 15px;
    font-weight: bold;
}

</style>
</head>
<body>
  
  <div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
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

        <div class="form-buttons">
            <button type="submit">Create Page</button>
            <a href="pages.php"><button type="button">← Back to Pages</button></a>
        </div>
    </form>
</div>
</body>
</html>
