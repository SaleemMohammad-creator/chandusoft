<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';
// Include admin header
include __DIR__ . '/../admin/header.php';

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
body { 
  font-family: Arial; 
  margin: 0; 
  background: #f7f8fc; 
}

/* Header Styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #007BFF;
    padding: 5px 10px;
}

.logo img {
    width: 400px;
    height: 70px;
}

nav {
    display: flex;
    justify-content: center;
    gap: 15px;
    background-color: #007BFF;
    padding: 1px 0;
}

nav a, nav button {
    padding: 10px 18px;
    margin: 5px;
    background-color: #007BFF;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    border: 1px solid #007BFF;
    transition: all 0.3s ease;
    cursor: pointer;
}

nav a.active, nav button.active {
    background-color: #fff; /* White background */
    color: #007BFF;        /* Blue text */
    border-color: #fff;    /* Optional */
}


nav a:hover, nav button:hover {
    background-color: rgb(239, 245, 245);
    color: #007BFF;
}

.container { 
  max-width: 1000px; 
  margin: 100px auto 40px auto; 
  background: #fff; 
  border-radius: 10px; 
  box-shadow: 0 4px 12px #0001; 
  padding: 30px 28px; 
}

.success { 
  background: #d4edda; 
  padding: 10px; 
  margin-bottom: 10px; 
  color: #155724; 
  border-radius: 6px; 
}

.error { 
  background: #f8d7da; 
  padding: 10px; 
  margin-bottom: 10px; 
  color: #721c24; 
  border-radius: 6px; 
}

input, 
textarea { 
  width: 100%; 
  padding: 10px; 
  margin-bottom: 10px; 
  border-radius: 5px; 
  border: 1px solid #ccc; 
}

/* --- Buttons Layout --- */
.form-actions {
  display: flex;
  justify-content: space-between; /* ✅ Send Enquiry (left), Back to Catalog (right) */
  align-items: center;
  margin-top: 10px;
}

button {
  background: #007BFF;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  transition: background 0.3s ease;
}

button:hover {
  background: #0056b3;
  cursor: pointer;
}

.back-btn {
  background: #007BFF;
  color: #fff;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 6px;
  transition: background 0.3s ease;
}

.back-btn:hover {
  background: #0056b3;
}

</style>

</head>
<body>



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
        <div class="success">✅ Enquiry Submitted Successfully</div>
    <?php elseif ($errors): ?>
        <div class="error"><?= implode("<br>", $errors) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
        <input type="text" name="name" placeholder="Your Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <input type="email" name="email" placeholder="Your Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <textarea name="message" rows="5" placeholder="Your Message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        <div class="cf-turnstile" data-sitekey="<?= $siteKey ?>"></div>
        <div class="form-actions">
              <button type="submit">Send Enquiry</button>
            <a href="/public/catalog.php" class="back-btn">← Back To Catalog</a>
        </div>
    </form>
</div>

</body>
</html>
