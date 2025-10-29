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
// Logging function (Local file log)
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

    // Log attempt
    logMessage("ATTEMPT: Enquiry for '{$item['title']}' | Name: {$name} | Email: {$email}");

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

    // Validation
    if (!$name) $errors[] = "Name is required.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (!$message) $errors[] = "Message is required.";

    if (!empty($errors)) {
        logMessage("ERROR: Enquiry failed | ERRORS: " . implode(", ", $errors));
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

            // ✅ Local File Log
            logMessage("SUCCESS: Enquiry Saved | Item: {$item['title']} (#{$item['id']}) | {$email}");

            // ✅ Also Send Log to Mailpit Inbox
            $mailSubject = "New Enquiry | {$item['title']} (#{$item['id']})";
            $mailBody = "
                Product: {$item['title']} (#{$item['id']})<br>
                Name: {$name}<br>
                Email: {$email}<br>
                Message: {$message}<br>
                Time: " . date('Y-m-d H:i:s') . "
            ";
            mailLog($mailSubject, $mailBody, 'catalog-enquiry');

        } catch (Exception $e) {
            $errors[] = "Failed to submit enquiry.";
            logMessage("DATABASE ERROR: {$e->getMessage()}");
        }
    }
}

// JSON-LD SEO
$jsonLd = [
    "@context" => "https://schema.org/",
    "@type" => "Product",
    "name" => $item['title'],
    "description" => $item['short_desc'],
    "image" => ["/uploads/" . $item['image']],
    "offers" => [
        "@type" => "Offer",
        "price" => $item['price'],
        "priceCurrency" => "USD"
    ]
];

// Image handling
$originalImage = '/uploads/' . htmlspecialchars($item['image']);
$webpImage = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalImage);
$webpExists = file_exists(__DIR__ . '/../' . ltrim($webpImage, '/'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($item['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<style>
   body { font-family: Arial; margin:0; background:#f7f8fc; }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }
.navbar a.nav-btn { color:#fff; text-decoration:none; margin-left:5px; font-weight:bold; padding:6px 12px; border-radius:4px; transition:background 0.3s; }
.navbar a.nav-btn:hover { background:#1C86EE; }
.container { max-width:1000px; margin:100px auto 40px auto; background:#fff; border-radius:10px; box-shadow:0 4px 12px #0001; padding:30px 28px; }
    .success { background:#d4edda; padding:10px; margin-bottom:10px; color:#155724; border-radius:6px; }
    .error { background:#f8d7da; padding:10px; margin-bottom:10px; color:#721c24; border-radius:6px; }
    input, textarea { width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc; }
    button { background:#007BFF; color:#fff; border:none; padding:10px 20px; border-radius:6px; }
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
    <p><strong>Price: </strong>$<?= htmlspecialchars($item['price']) ?></p>
    <p><?= nl2br(htmlspecialchars($item['short_desc'])) ?></p>

    <?php if ($item['image']): ?>
    <picture>
        <?php if ($webpExists): ?>
            <source srcset="<?= $webpImage ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?= $originalImage ?>" style="max-width:100%; border-radius:8px;">
    </picture>
    <?php endif; ?>

    <h2>Enquire Now</h2>

    <?php if ($enquirySuccess): ?>
        <div class="success">✅ Enquiry submitted successfully</div>
    <?php elseif ($errors): ?>
        <div class="error"><?= implode("<br>", $errors) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
        <input type="text" name="name" placeholder="Your Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <input type="email" name="email" placeholder="Your Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <textarea name="message" rows="5" placeholder="Your Message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        <div class="cf-turnstile" data-sitekey="<?= $siteKey ?>"></div>
        <button type="submit">Submit Enquiry</button>
    </form>
</div>

</body>
</html>
