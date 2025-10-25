<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

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

        $_SESSION['success_message'] = "Catalog item added successfully.";
        redirect('catalog.php');

    } catch (Exception $e) {
        $errors[] = $e->getMessage(); // Display friendly error
        log_catalog_error($e->getMessage()); // Log error to catalog.logs
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New Catalog Item</title>
<style>
body { font-family: Arial, sans-serif; background:#f9f9f9; padding:20px; color:#333; }
.container { max-width:600px; margin:0 auto; background:#fff; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.1); border-radius:8px; }
h2 { text-align:center; color:#007BFF; margin-bottom:20px; }
form input, form textarea { width:100%; padding:8px; margin-bottom:12px; border:1px solid #ccc; border-radius:5px; }
form button { padding:10px 18px; border:none; border-radius:5px; background:#007BFF; color:#fff; font-weight:bold; cursor:pointer; transition:0.3s; }
form button:hover { background:#0056b3; }
.errors { background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px; }
.success { background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px; }
.preview { margin-top:10px; }
.preview img { max-width:200px; border:1px solid #ccc; padding:5px; border-radius:5px; }
</style>
</head>
<body>

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
    <label>Title:</label>
    <input type="text" name="title" value="<?= sanitize($_POST['title'] ?? '') ?>" required>

    <label>Short Description:</label>
    <textarea name="short_desc"><?= sanitize($_POST['short_desc'] ?? '') ?></textarea>

    <label>Price:</label>
    <input type="number" name="price" step="0.01" value="<?= sanitize($_POST['price'] ?? '') ?>" required>

    <label>Upload Image:(Max Size 2MB)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <?php if(!empty($uploaded['webp'])): ?>
    <div class="preview">
        <strong>WebP Preview:</strong><br>
        <img src="/uploads/<?= $uploaded['webp'] ?>" alt="WebP Image">
    </div>
    <?php endif; ?>

    <button type="submit">Add Item</button>
</form>
</div>

</body>
</html>
