<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'cookie_samesite' => 'Lax',
        'cookie_path' => '/'
    ]);
}

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle form submission
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');

    if ($site_name !== '') {
        update_setting('site_name', $site_name);
    }

    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $file = $_FILES['site_logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
            $filename = 'logo_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $oldLogo = get_setting('site_logo');
                if ($oldLogo && file_exists($uploadDir . $oldLogo)) unlink($uploadDir . $oldLogo);
                update_setting('site_logo', $filename);
            }
        }
    }

    // Show success message and clear inputs
    $successMessage = "✅ Settings updated successfully!";
    $_POST = []; // clear form
}

// Always blank inputs
$current_site_name = '';
$current_logo = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f2f5;
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.admin-header {
    background: #1E90FF;
    color: #fff;
    padding: 15px 0;
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Center content vertically and horizontally */
.wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Perfectly centered card */
.admin-content {
    background: #fff;
    width: 100%;
    max-width: 420px;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    box-sizing: border-box;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

input[type="text"], 
input[type="file"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 18px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background: #1E90FF;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s ease;
}

button:hover {
    background: #187bcd;
}

.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
}
</style>
</head>
<body>

<header class="admin-header">
    Settings
</header>

<div class="wrapper">
    <main class="admin-content">
        <?php if ($successMessage): ?>
            <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>Site Name</label>
            <input type="text" name="site_name" value="<?= htmlspecialchars($current_site_name) ?>" required>

            <label>Site Logo</label>
            <input type="file" name="site_logo" accept=".jpg,.jpeg,.png,.gif,.webp">

            <button type="submit">Save</button>
        </form>
    </main>
</div>

</body>
</html>
