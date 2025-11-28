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
   Container
=========================== */
.container {
    max-width: 950px;
    margin: 110px auto 40px;
    background: #fff;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

/* ===========================
   Header
=========================== */
h2 {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 25px;
}

/* ===========================
   Alerts
=========================== */
.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 600;
}

.success {
    background: #dcfce7;
    color: #14532d;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 600;
}

/* ===========================
   Form Layout
=========================== */
.form-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 18px;
}

.form-row label {
    width: 200px;
    font-weight: 600;
    font-size: 15px;
    color: #374151;
    padding-top: 10px;
}

.form-row input,
.form-row textarea,
.form-row select {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 15px;
    background: #fff;
    transition: border 0.25s, box-shadow 0.25s;
}

.form-row input:focus,
.form-row textarea:focus,
.form-row select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

textarea {
    height: 160px;
}

/* ===========================
   Buttons
=========================== */
.button-row {
    margin-left: 200px;
    margin-top: 12px;
    display: flex;
    gap: 14px;
}

.button-row button {
    padding: 12px 20px;
    background: #2563eb;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s;
}

.button-row button:hover {
    background: #1e4fd4;
    transform: translateY(-1px);
}

.button-row a button {
    background: #6b7280;
}

.button-row a button:hover {
    background: #4b5563;
    transform: translateY(-1px);
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