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
/* =======================================================
   GLOBAL PAGE STYLE
======================================================= */
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #eaf2ff, #f6f9ff);
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

/* =======================================================
   ADMIN HEADER
======================================================= */
.admin-header {
    background: #1E90FF;
    color: #fff;
    padding: 18px 0;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    letter-spacing: 0.5px;
}

/* =======================================================
   CENTER WRAPPER
======================================================= */
.wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px;
}

/* =======================================================
   ADMIN CARD
======================================================= */
.admin-content {
    background: #ffffff;
    width: 100%;
    max-width: 420px;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.10);
    box-sizing: border-box;
    animation: fadeSlideIn 0.5s ease-out;
}

/* Slide-in animation */
@keyframes fadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* =======================================================
   FORM LABELS
======================================================= */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
    font-size: 14px;
}

/* =======================================================
   INPUTS
======================================================= */
input[type="text"], 
input[type="file"] {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 20px;
    border-radius: 6px;
    border: 1px solid #ccd4dd;
    background: #fafbfd;
    font-size: 15px;
    transition: border-color 0.25s, box-shadow 0.25s;
    box-sizing: border-box;
}

input[type="text"]:focus,
input[type="file"]:focus {
    border-color: #1E90FF;
    box-shadow: 0 0 0 3px rgba(30,144,255,0.2);
    outline: none;
}

/* =======================================================
   SUBMIT BUTTON
======================================================= */
button {
    width: 100%;
    padding: 13px;
    background: #1E90FF;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 17px;
    font-weight: bold;
    letter-spacing: 0.3px;
    transition: background 0.3s, transform 0.2s;
}

button:hover {
    background: #187bcd;
    transform: translateY(-2px);
}

/* =======================================================
   SUCCESS MESSAGE
======================================================= */
.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    font-weight: bold;
    margin-bottom: 18px;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
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

<script>
window.addEventListener('DOMContentLoaded', (event) => {
    const message = document.querySelector('.success-message');
    if (message) {
        // Show message for 3 seconds, then fade out
        setTimeout(() => {
            message.style.transition = 'opacity 0.6s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 600); // remove element after fade
        }, 3000); // 3 seconds
    }
});
</script>

</body>
</html>
