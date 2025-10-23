<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'cookie_samesite' => 'Lax',
        'cookie_path' => '/'
    ]);
}

// Admin login check
if (empty($_SESSION['user_id'])) redirect('index');

// Upload directory
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update site name
    $site_name = trim($_POST['site_name'] ?? '');
    if ($site_name !== '') {
        update_setting('site_name', $site_name);
    }

    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $file = $_FILES['site_logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
            $filename = 'logo_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $oldLogo = get_setting('site_logo');
                if ($oldLogo && file_exists($uploadDir . $oldLogo)) unlink($uploadDir . $oldLogo);
                update_setting('site_logo', $filename);
            }
        }
    }

    // Redirect to admin index
    redirect('index');
}

// Fetch current settings
$current_site_name = get_setting('site_name');
$current_logo = get_setting('site_logo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings - <?= sanitize($current_site_name) ?></title>
<link rel="stylesheet" href="/admin/styles.css">
<style>
body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; }
.admin-header { background: #1E90FF; color: #fff; padding: 15px; }
.admin-header nav a { color: #fff; margin-right: 15px; text-decoration: none; }
.admin-content { padding: 20px; background: #fff; margin: 20px auto; width: 400px; border-radius: 8px; box-shadow: 0 6px 15px rgba(0,0,0,0.1);}
label { display: block; margin-bottom: 5px; font-weight: bold; }
input[type="text"], input[type="file"] { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ccc; }
button { padding: 10px 20px; background: #1E90FF; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
button:hover { background: #187bcd; }
img.logo-preview { max-width: 200px; display: block; margin-bottom: 15px; }
</style>
</head>
<body>

<header class="admin-header">
    <h1>Settings</h1>
    <nav>
        <a href="index">Dashboard</a>
        <a href="settings">Settings</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="admin-content">
    <form method="post" enctype="multipart/form-data">
        <label>Site Name</label>
        <input type="text" name="site_name" value="<?= sanitize($current_site_name) ?>" required>

        <label>Site Logo</label>
        <?php if ($current_logo && file_exists($uploadDir . $current_logo)): ?>
            <img src="/uploads/<?= sanitize($current_logo) ?>" class="logo-preview" alt="Logo">
        <?php endif; ?>
        <input type="file" name="site_logo" accept=".jpg,.jpeg,.png,.gif,.webp">

        <button type="submit">Save</button>
    </form>
</main>

</body>
</html>
