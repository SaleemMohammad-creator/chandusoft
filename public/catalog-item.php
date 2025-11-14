<?php
// ===============================================
// 🛒 Catalog Item Page (Add to Cart + Enquiry, Buy Now Removed)
// ===============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php';

// -------------------------
// Safe user info
// -------------------------
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// ✅ Use keys loaded from config.php
$siteKey  = TURNSTILE_SITE;
$secretKey = TURNSTILE_SECRET;

// -------------------------
// CSRF Token
// -------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


// -------------------------
// Logging
// -------------------------
$logFile = __DIR__ . '/../storage/logs/catalog.logs';
function logMessage($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg" . PHP_EOL, FILE_APPEND);
}

// -------------------------
// Get catalog item
// -------------------------
$slug = $_GET['slug'] ?? '';
if (!$slug) die("No catalog item specified.");

$stmt = $pdo->prepare("SELECT * FROM catalog WHERE slug=:slug AND status='published'");
$stmt->execute(['slug' => $slug]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) die("Item not found or not published.");

// -------------------------
// Handle POST (Add to Cart, Enquiry)
// -------------------------
$enquirySuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $actionType = $_POST['action_type'] ?? '';

    // Quantity only for add_to_cart
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    // ✅ Add to Cart
    if ($actionType === 'add_to_cart') {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$item['id']] = [
            'id' => $item['id'],
            'title' => $item['title'],
            'price' => $item['price'],
            'image' => $item['image'],
            'quantity' => $quantity
        ];
        header("Location: /public/cart.php");
        exit;
    }

    // ✅ Enquiry
    if ($actionType === 'enquiry') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $csrf = $_POST['_csrf'] ?? '';
        $turnstileToken = $_POST['cf-turnstile-response'] ?? '';

        logMessage("ATTEMPT: Enquiry for '{$item['title']}' | Name: {$name} | Email: {$email}");

        if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {

            $errors[] = "Invalid CSRF token.";
        }

        if ($turnstileToken) {
            $verify = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
            curl_setopt_array($verify, [
           CURLOPT_POST => true,
           CURLOPT_POSTFIELDS => http_build_query([
        'secret' => $secretKey,
        'response' => $turnstileToken,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false    // ✅ FIX: allow local HTTPS without valid certificate
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

        if (!$name) $errors[] = "Name is required.";
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        if (!$message) $errors[] = "Message is required.";

        if (!empty($errors)) {
            logMessage("ERROR: Enquiry failed | ERRORS: " . implode(", ", $errors));
        }

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

                // ✅ CLEAR FORM FIELDS AFTER SUCCESS
                $_POST['name'] = '';
                $_POST['email'] = '';
                $_POST['message'] = '';
                logMessage("SUCCESS: Enquiry Saved | Item: {$item['title']} (#{$item['id']}) | {$email}");
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
}

// -------------------------
// JSON-LD SEO
// -------------------------
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
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($item['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ✅ Turnstile Script (unchanged) -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>

<style>
/* (CSS unchanged exactly as you provided) */
body{font-family:Arial;margin:0;background:#f7f8fc;}
.container{max-width:1000px;margin:50px auto;padding:30px;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h1{margin-top:0;}
img{max-width:400px;border-radius:8px;display:block;margin:20px auto;}
.quantity-controls{display:inline-flex;align-items:center;border:1px solid #ccc;border-radius:6px;overflow:hidden;margin:10px 0;}
.quantity-controls button{background:#007BFF;color:#fff;border:none;width:35px;height:35px;font-size:18px;cursor:pointer;}
.quantity-controls input{width:50px;text-align:center;border:none;font-size:16px;}
.action-btns{display:flex;gap:15px;margin:20px 0;}
.action-btns button{padding:12px 20px;background:#007BFF;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer;transition:0.3s;}
.action-btns button:hover{background:#0056b3;}
button:disabled{background:#ccc;cursor:not-allowed;}
form{display:flex;flex-direction:column;gap:20px;}
input,textarea{padding:12px;font-size:16px;border:1px solid:#ddd;border-radius:8px;outline:none;transition:0.3s;}
input:focus,textarea:focus{border-color:#007BFF;}
textarea{resize:vertical;min-height:120px;}
.success{padding:15px;margin-bottom:20px;border-radius:6px;text-align:center;font-size:16px;background:#e0f9e0;color:#28a745;border:1px solid #28a745;}
.error{padding:15px;margin-bottom:20px;border-radius:6px;text-align:center;font-size:16px;background:#f8d7da;color:#dc3545;border:1px solid #dc3545;}
.enquiry-buttons{display:flex;justify-content:space-between;align-items:center;margin-top:15px;}
.send-btn{padding:12px 20px;background:#28a745;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer;transition:0.3s;}
.send-btn:hover{background:#218838;}
.back-btn{font-size:16px;color:#007BFF;text-decoration:none;}
.back-btn:hover{color:#0056b3;}
@media(max-width:768px){.container{padding:20px;margin:20px;}input,textarea{font-size:14px;}button{font-size:14px;padding:10px 15px;}}
</style>
</head>
<body>

<div class="container">
<h1><?= htmlspecialchars($item['title']) ?></h1>
<p><strong>Price:</strong> $<?= htmlspecialchars($item['price']) ?></p>
<p><?= nl2br(htmlspecialchars($item['short_desc'])) ?></p>
<?php if(!empty($item['image'])): ?>
<img src="/uploads/<?= htmlspecialchars($item['image']) ?>">
<?php endif; ?>

<!-- Add to Cart Form -->
<form method="post">
    <div class="quantity-controls">
        <button type="button" id="minus">−</button>
        <input type="text" id="quantity" name="quantity" value="1">
        <button type="button" id="plus">+</button>
    </div>
    <div class="action-btns">
        <button type="submit" name="action_type" value="add_to_cart">🛒 Add to Cart</button>
    </div>
</form>

<!-- Enquiry Form -->
<h2>Enquire Now</h2>
<?php if($enquirySuccess): ?>
<div class="success" id="successMsg">✅ Enquiry Submitted Successfully</div>
<?php elseif($errors): ?>
<div class="error"><?= implode("<br>", $errors) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
    <input type="hidden" name="action_type" value="enquiry">
    <input type="text" name="name" placeholder="Your Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    <input type="email" name="email" placeholder="Your Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    <textarea name="message" rows="5" placeholder="Your Message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

    <!-- ✅ Updated ONLY this line -->
    <div class="cf-turnstile"
        data-sitekey="<?= $siteKey ?>"
        data-callback="turnstileCallback">
    </div>


    <input type="hidden" name="cf-turnstile-response" id="turnstile-token">

    <div class="enquiry-buttons">
        <button type="submit" class="send-btn">Send Enquiry</button>
        <a href="/public/catalog.php" class="back-btn">← Back To Catalog</a>
    </div>
</form>
</div>

<script>
const qtyInput = document.getElementById('quantity');
document.getElementById('plus').addEventListener('click',()=>qtyInput.value=parseInt(qtyInput.value)+1);
document.getElementById('minus').addEventListener('click',()=>{if(parseInt(qtyInput.value)>1)qtyInput.value=parseInt(qtyInput.value)-1;});

// ✅ Only update: automatic Turnstile token capture
function turnstileCallback(token) {
    document.getElementById("turnstile-token").value = token;
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const successMsg = document.getElementById("successMsg");
    if (successMsg) {
        setTimeout(() => {
            successMsg.style.display = "none";
        }, 3000); // 3 seconds
    }
});
</script>

</body>
</html>
