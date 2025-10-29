<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';


// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// -------------------------
// Cloudflare Turnstile keys
// -------------------------
$siteKey = '0x4AAAAAAB7ii-4RV0QMh131';
$secretKey = '0x4AAAAAAB7ii73wAJ7ecUp7fBr4RTvr5N8';

// -------------------------
// CSRF Token
// -------------------------
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['_csrf'];

// -------------------------
// Logging function
// -------------------------
$logFile = __DIR__ . '/../storage/logs/catalog.logs';
function logMessage($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg" . PHP_EOL, FILE_APPEND);
}

// -------------------------
// Base URL helper
// -------------------------
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . '/' . ltrim($path, '/');
}

// -------------------------
// Get catalog item by slug
// -------------------------
$slug = $_GET['slug'] ?? '';
if (!$slug) die("No catalog item specified.");

$stmt = $pdo->prepare("SELECT * FROM catalog WHERE slug=:slug AND status='published'");
$stmt->execute(['slug' => $slug]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) die("Item not found or not published.");

// -------------------------
// Handle enquiry form
// -------------------------
$enquirySuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $csrf = $_POST['_csrf'] ?? '';
    $turnstileToken = $_POST['cf-turnstile-response'] ?? '';

    // ✅ NEW: Log enquiry attempt
    logMessage("ATTEMPT: Enquiry initiated for item #{$item['id']} | Name: {$name} | Email: {$email} | Message: {$message}");

    // CSRF check
    if (!$csrf || !hash_equals($_SESSION['_csrf'], $csrf)) {
        $errors[] = "Invalid CSRF token.";
    }

    // Turnstile verification
    if ($turnstileToken) {
        $verify = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
        curl_setopt_array($verify, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secretKey,
                'response' => $turnstileToken,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]),
            CURLOPT_RETURNTRANSFER => true
        ]);
        $resp = curl_exec($verify);
        curl_close($verify);
        $result = json_decode($resp, true);
        if (empty($result['success'])) {
            $errors[] = "CAPTCHA verification failed.";
        }
    } else {
        $errors[] = "Please complete CAPTCHA.";
    }

    // Form validation
    if (!$name) $errors[] = "Name is required.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (!$message) $errors[] = "Message is required.";

    // Log errors if any
    if (!empty($errors)) {
        // ✅ Upgraded: Log details for better traceability
        logMessage("ERROR: Enquiry Failed | Item: {$item['id']} | Name: {$name} | Email: {$email} | Message: {$message} | ERRORS: " . implode(" | ", $errors));
    }

    // Insert enquiry
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO enquiries (catalog_id, name, email, message, created_at) VALUES (:catalog_id, :name, :email, :message, NOW())");
            $stmt->execute([
                ':catalog_id' => $item['id'],
                ':name' => $name,
                ':email' => $email,
                ':message' => $message
            ]);
            $enquirySuccess = true;

            // ✅ Upgraded: Log full enquiry successfully stored
            logMessage("SUCCESS: Enquiry Stored | Item: {$item['id']} | Name: {$name} | Email: {$email} | Message: {$message}");

        } catch (Exception $e) {
            $errors[] = "Failed to submit enquiry. Please try again.";
            logMessage("ERROR: Database Error for Item: {$item['id']} | {$email} | " . $e->getMessage());
        }
    }
}

// -------------------------
// JSON-LD for SEO
// -------------------------
$jsonLd = [
    "@context" => "https://schema.org/",
    "@type" => "Product",
    "name" => $item['title'],
    "image" => ["/uploads/" . $item['image']],
    "description" => $item['short_desc'],
    "offers" => [
        "@type" => "Offer",
        "price" => $item['price'],
        "priceCurrency" => "USD",
        "availability" => "https://schema.org/InStock"
    ]
];

// Determine WebP path
$originalImage = '/uploads/' . htmlspecialchars($item['image']);
$webpImage = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalImage);
$webpExists = file_exists(__DIR__ . '/../' . ltrim($webpImage, '/'));
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($item['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($item['short_desc']) ?>">
<script type="application/ld+json">
<?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
    body { font-family: Arial; margin:0; background:#f7f8fc; }

.navbar {
    background:#2c3e50;
    color:#fff;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;

    /* ✅ new lines */
    position: fixed;
    top: 0;
    left: 0;
    width:100%;
    z-index:1000;
    box-sizing: border-box;
}

.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }
.navbar a.nav-btn { color:#fff; text-decoration:none; margin-left:5px; font-weight:bold; padding:6px 12px; border-radius:4px; transition:background 0.3s; }
.navbar a.nav-btn:hover { background:#1C86EE; }

/* ✅ Prevent overlap by pushing content down */
.container {
    max-width:1000px;
    margin:100px auto 40px auto; /* Keep your original spacing */
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px #0001;
    padding:30px 28px;
}

h1 { color:#007BFF; margin-bottom:15px; }
.price { font-size:20px; font-weight:bold; margin-bottom:10px; }
.description { margin-bottom:20px; }
img { max-width:100%; border-radius:6px; display:block; margin-bottom:20px; }
.success { background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px; }
.error { background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px; }
.form-container input, .form-container textarea { width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:5px; }
.form-buttons { display:flex; justify-content:space-between; align-items:center; }
.form-buttons a, .form-buttons button { padding:10px 20px; border-radius:5px; font-weight:bold; text-decoration:none; }
.form-buttons a { background:#6C7BFF; color:#fff; }
.form-buttons button { background:#007BFF; color:#fff; border:none; cursor:pointer; }
</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">Chandusoft Admin</div>
    <div class="navbar-right">
        <span>Welcome <?= htmlspecialchars($user_role)?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>
        <!-- Dynamic catalog link based on user role -->
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
    <h1><?= htmlspecialchars($item['title']) ?></h1>
    <div class="price">Price: $<?= htmlspecialchars($item['price']) ?></div>
    <div class="description"><?= nl2br(htmlspecialchars($item['short_desc'])) ?></div>

    <?php if ($item['image']): ?>
    <picture>
        <?php if ($webpExists): ?>
        <source srcset="<?= $webpImage ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?= $originalImage ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
    </picture>
    <?php endif; ?>

    <div class="form-container">
        <h2>Enquire about this product</h2>
        <?php if ($enquirySuccess): ?>
            <div class="success">✅ Thank you! Your enquiry has been submitted.</div>
        <?php elseif ($errors): ?>
            <div class="error"><?= implode("<br>", $errors) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
            <input type="text" name="name" placeholder="Your Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            <input type="email" name="email" placeholder="Your Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <textarea name="message" rows="5" placeholder="Your Message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

            <div class="cf-turnstile" data-sitekey="<?= $siteKey ?>" data-action="submit"></div>

            <div class="form-buttons">
                <button type="submit">Submit Enquiry</button>
                <a href="<?= base_url('public/catalog') ?>">← Back to Catalog</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
