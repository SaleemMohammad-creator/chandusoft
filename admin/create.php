<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // PDO connection + verify_csrf()
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php';

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';
$user_id   = $_SESSION['user_id'] ?? null;

// Allow both admin and editor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'])) {
    header("Location: login.php");
    exit;
}

// CSRF token
$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$errors = [];
$success = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $slug         = trim($_POST['slug'] ?? '');
    $content_html = $_POST['content_html'] ?? '';
    $status       = $_POST['status'] ?? 'draft';
    $csrf         = $_POST['csrf_token'] ?? '';

    // CSRF Validation
    if (!verify_csrf($csrf)) $errors[] = "Invalid CSRF token.";

    if ($title === '') $errors[] = "Title is required.";
    if ($slug === '') $slug = strtolower(str_replace(' ', '-', $title));

    // Check duplicate slug
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    if ($stmt->fetch()) $errors[] = "Slug already exists. Choose another.";

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

        // Log Email
        mailLog("Page Created", "Title: $title, Slug: $slug, Status: $status", "pages");

        // DB Log
        log_action($user_id, 'Page Created', "Title: $title, Slug: $slug, Status: $status");

        // Reset
        $title = $slug = $content_html = '';
    } else {
        log_action($user_id, 'Page Create Failed', implode(', ', $errors));
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Page - Admin</title>

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
    height: 150px;
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

        <a href="/admin/dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

        <?php if ($user_role === 'admin'): ?>
        <a href="/admin/catalog.php"
           class="<?= in_array($currentPage, ['catalog.php','catalog-new.php','catalog-edit.php','catalog-delete.php']) ? 'active' : '' ?>">
           Admin Catalog
        </a>
        <?php endif; ?>

        <a href="/public/catalog.php">Public Catalog</a>

        <a href="/admin/pages.php"
           class="<?= in_array($currentPage, ['pages.php','create.php','edit.php','page-create.php','page-edit.php']) ? 'active' : '' ?>">
           Pages
        </a>

        <a href="/admin/admin-leads.php"
           class="<?= $currentPage === 'admin-leads.php' ? 'active' : '' ?>">Leads</a>

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

    <div class="form-row">
        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>
    </div>

    <div class="form-row">
        <label>Slug:</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($slug ?? '') ?>" placeholder="Leave empty to auto-generate">
    </div>

    <div class="form-row">
        <label>Content (HTML allowed):</label>
        <textarea name="content_html"><?= htmlspecialchars($content_html ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <label>Status:</label>
        <select name="status">
            <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($status ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
    </div>

    <div class="button-row">
        <button type="submit">Create Page</button>
        <a href="pages.php"><button type="button">← Back to Pages</button></a>
    </div>

</form>

</div>

</body>
</html>
