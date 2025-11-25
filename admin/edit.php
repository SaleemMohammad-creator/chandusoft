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

    /* Fixed Navbar */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-sizing: border-box;
}

.navbar-left {
    font-weight: bold;
    font-size: 22px;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 10px;
    font-weight: bold;
}

.navbar a {
    color: #fff;
    text-decoration: none;
    margin-left: 15px;
    font-weight: bold;
}

.nav-btn {
    color: #fff;
    margin-left: 5px;
    padding: 6px 12px;
    font-weight: bold;
    text-decoration: none;
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
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
    box-sizing: border-box;
}

/* ===========================
   Form Buttons
=========================== */
.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

button {
    padding: 10px 20px;
    border: none;
    background: #3498db;
    color: #fff;
    cursor: pointer;
    border-radius: 4px;
    font-weight: bold;
    transition: background 0.3s ease;
}

button:hover {
    background: #1d6fa5;
}

/* ===========================
   Messages
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

/* =========================================
   Left Label - Right Input Form Layout
========================================= */
.form-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 18px;
}

.form-row label {
    width: 200px;
    font-weight: bold;
    color: #333;
    padding-top: 10px;
}

.form-row input,
.form-row textarea,
.form-row select {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.form-row textarea {
    height: 120px;
}

.button-row {
    display: flex;
    justify-content: space-between;   /* Left & Right */
    align-items: center;
    margin-left: 200px;               /* Line up with inputs */
    margin-top: 20px;
    width: calc(100% - 200px);        /* Full width minus label space */
}

.button-row button {
    padding: 10px 20px;
    background: #3498db;
    border: none;
    border-radius: 6px;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.button-row button:hover {
    background: #1d6fa5;
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

        <a href="/admin/pages.php"
           style="<?= (
                        $currentPage === 'pages.php' ||
                        $currentPage === 'create.php' ||
                        $currentPage === 'edit.php' ||
                        $currentPage === 'page-create.php' ||
                        $currentPage === 'page-edit.php'
                    )
                    ? 'background:#1E90FF; padding:6px 12px; border-radius:4px;'
                    : '' ?>">
            Pages
        </a>

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

    <div class="form-row">
        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <label>Slug:</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <label>Content (HTML allowed):</label>
        <textarea name="content_html"><?= $page['content_html'] ?? '' ?></textarea>
    </div>

    <div class="form-row">
        <label>Status:</label>
        <select name="status">
            <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($page['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
    </div>

    <div class="button-row">
        <button type="submit">Update Page</button>

        <a href="pages.php">
            <button type="button">← Back to Pages</button>
        </a>
    </div>

</form>

</div>
</body>
</html>