<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Admin Action Logging


// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// ----------------------------
// Helper: generate URL-friendly slug
// ----------------------------
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    return trim($slug, '-');
}

// ----------------------------
// Helper: Upload original + WebP (WebP resized smaller than original)
// ----------------------------
function uploadOriginalAndWebP($file, $max_size_bytes = 2*1024*1024, $webp_max_width = 1600, $webp_reduce_ratio = 0.8) {
    $allowed_types = ['image/jpeg','image/png','image/webp'];

    // Check PHP upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception("File is too large. Maximum allowed size is 2MB.");
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
            throw new Exception("File was not uploaded correctly. Please try again.");
        case UPLOAD_ERR_OK:
            break;
        default:
            throw new Exception("An unknown error occurred during file upload.");
    }

    // Original file size check
    if ($file['size'] > $max_size_bytes) {
        throw new Exception("File is too large. Maximum allowed size is 2MB.");
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, PNG, or WebP allowed.");
    }

    // Create uploads/YYYY/MM/
    $year  = date('Y');
    $month = date('m');
    $uploadDir = UPLOAD_DIR . "$year/$month/";
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $baseName = uniqid('img_');
    $originalFile = $uploadDir . $baseName . '.' . $ext;
    $webpFile     = $uploadDir . $baseName . '.webp';

    // Move original file as-is
    if (!move_uploaded_file($file['tmp_name'], $originalFile)) {
        throw new Exception("Failed to move uploaded file.");
    }

    // Load original image into GD
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = @imagecreatefromjpeg($originalFile);
            break;
        case 'png':
            $img = @imagecreatefrompng($originalFile);
            break;
        case 'webp':
            $img = @imagecreatefromwebp($originalFile);
            break;
        default:
            throw new Exception("Unsupported image type.");
    }
    if (!$img) throw new Exception("Failed to read image file.");

    $orig_width  = imagesx($img);
    $orig_height = imagesy($img);

    // Calculate WebP width (< original width)
    $new_width = min($webp_max_width, intval($orig_width * $webp_reduce_ratio));
    $new_height = intval($orig_height * $new_width / $orig_width);

    // Create resized WebP image
    $resizedImg = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($resizedImg, false);
    imagesavealpha($resizedImg, true);
    imagecopyresampled($resizedImg, $img, 0,0,0,0, $new_width, $new_height, $orig_width, $orig_height);
    imagedestroy($img);

    // Save WebP version
    if (function_exists('imagewebp')) {
        imagewebp($resizedImg, $webpFile, 80);
    }
    imagedestroy($resizedImg);

    return [
        'original' => "$year/$month/$baseName.$ext",
        'webp'     => file_exists($webpFile) ? "$year/$month/$baseName.webp" : null
    ];
}

// ----------------------------
// Helper: log errors to storage/logs/catalog.logs
// ----------------------------
function log_catalog_error($message) {
    $logFile = __DIR__ . '/../storage/logs/catalog.logs';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// ----------------------------
// Handle Form Submission
// ----------------------------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF check
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid CSRF token.");
        }

        // Sanitize input
        $title      = trim($_POST['title'] ?? '');
        $short_desc = trim($_POST['short_desc'] ?? '');
        $price      = floatval($_POST['price'] ?? 0);

        if ($title === '') throw new Exception("Title is required.");
        if ($price <= 0)  throw new Exception("Price must be greater than zero.");

        // Generate slug
        $slug = generateSlug($title);

        // Handle image upload
        $uploaded = null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadOriginalAndWebP($_FILES['image']);
        }

        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO catalog 
            (title, slug, short_desc, price, image, status, created_at, updated_at) 
            VALUES (:title, :slug, :short_desc, :price, :image, 'published', NOW(), NOW())");

      $stmt->execute([
    'title'      => $title,
    'slug'       => $slug,
    'short_desc' => $short_desc,
    'price'      => $price,
    'image'      => $uploaded['original'] ?? null
]);

 // ✅ Store logs
        mailLog("Catalog Item Added: $title",
            "Slug: $slug | Price: $price | Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'),
            'catalog'
        );

        // ✅ Admin action log
    log_action($_SESSION['user_id'] ?? 0, 'Catalog Item Added', "Title: {$title} | Slug: {$slug} | Price: {$price}");


        $_SESSION['success_message'] = "Catalog item added successfully.";
        redirect('/admin/catalog.php');

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        log_catalog_error($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New Catalog Item</title>
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
}

.navbar a.active {
    background: #2563eb !important;
    color: #fff;
}

/* ===========================
   Container Card
=========================== */
.container {
    max-width: 850px;
    margin: 90px auto 40px;
    padding: 0 15px;
}

.card {
    background: #fff;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

h2 {
    text-align: center;
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 25px;
}

/* ===========================
   Error / Success Messages
=========================== */
.errors {
    background: #fee2e2;
    color: #b91c1c;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 600;
}

.success {
    background: #dcfce7;
    color: #14532d;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 600;
}

/* ===========================
   Form Layout
=========================== */
.form-row {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 18px;
}

.form-row label {
    width: 200px;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    padding-top: 8px;
}

.form-row input,
.form-row textarea {
    flex: 1;
    padding: 12px 14px;
    font-size: 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    outline: none;
    background: #fff;
    transition: 0.25s;
}

.form-row input:focus,
.form-row textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
}

textarea {
    height: 90px;
    resize: vertical;
}

/* ===========================
   File Input Preview
=========================== */
.preview img {
    max-width: 200px;
    border-radius: 8px;
    margin-top: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

/* ===========================
   Submit Button
=========================== */
.button-row {
    margin-left: 200px;
}

.button-row button {
    padding: 12px 20px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s;
}

.button-row button:hover {
    background: #1e4fd4;
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
           style="<?= ($currentPage === 'catalog.php' || $currentPage === 'catalog-new.php') 
                     ? 'background:#1E90FF; padding:6px 12px; border-radius:4px;' 
                     : '' ?>">
            Admin Catalog
        </a>
        <?php endif; ?>

        <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>


<div class="container">
<h2>Add New Catalog Item</h2>

<?php if(!empty($errors)): ?>
<div class="errors">
    <ul>
    <?php foreach($errors as $err): ?>
        <li><?= sanitize($err) ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_input() ?>

    <div class="form-row">
        <label>Title:</label>
        <input type="text" name="title" value="<?= sanitize($_POST['title'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <label>Short Description:</label>
        <textarea name="short_desc"><?= sanitize($_POST['short_desc'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <label>Price:</label>
        <input type="number" name="price" step="0.01" value="<?= sanitize($_POST['price'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <label>Upload Image (Max 2MB):</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <?php if(!empty($uploaded['webp'])): ?>
    <div class="form-row">
        <label>WebP Preview:</label>
        <img src="/uploads/<?= $uploaded['webp'] ?>" alt="WebP Image" style="max-width:200px;">
    </div>
    <?php endif; ?>

    <div class="button-row">
        <button type="submit">Add Item</button>
    </div>
</form>

</div>

</body>
</html>