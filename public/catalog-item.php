<?php
// ===============================================
// 🛒 Catalog Item Page (Add to Cart + Enquiry)
// Updated: Hover Zoom (NO modal popup) + Gemini AI Description
// ===============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/helpers.php';   // <--- Load AI function FIRST
require_once __DIR__ . '/../app/config.php';    // <--- Load config AFTER
require_once __DIR__ . '/../app/mail-logger.php';

// -------------------------
// Safe user info
// -------------------------
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// Turnstile keys
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
// Logging (for this page)
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



// ⭐ NEW — Generate AI Description Using Gemini (once)
try {
    if (function_exists('aiGenerateDescription')) {

        if (!isset($item['short_desc']) || trim($item['short_desc']) === "") {

            $ai_desc = aiGenerateDescription($item['title']);

            if (!empty($ai_desc)) {
                $upd = $pdo->prepare("UPDATE catalog SET short_desc = :d WHERE id = :id");
                $upd->execute([
                    ':d' => $ai_desc,
                    ':id' => $item['id']
                ]);

                $item['short_desc'] = $ai_desc;

                logMessage("AI desc generated for '{$item['title']}' (#{$item['id']})");
            }
        }
    }
} catch (Throwable $e) {
    logMessage("Gemini AI error: " . $e->getMessage());
}


// -------------------------
// Handle POST
// -------------------------
$enquirySuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $actionType = $_POST['action_type'] ?? '';

    // Quantity for cart
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    // Add to cart
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

    // BUY NOW → add to cart (single item) → go to checkout
    if ($actionType === 'buy_now') {

        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        $_SESSION['cart'] = [
            $item['id'] => [
                'id' => $item['id'],
                'title' => $item['title'],
                'price' => $item['price'],
                'image' => $item['image'],
                'quantity' => $quantity
            ]
        ];

        header("Location: /public/checkout.php");
        exit;
    }


    // Enquiry form processing
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

        // -------------------------
        // Turnstile verification
        // -------------------------
        if (!empty($turnstileToken)) {

            $response = file_get_contents(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                false,
                stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'content' => http_build_query([
                            'secret'   => $secretKey,
                            'response' => $turnstileToken,
                            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
                        ])
                    ]
                ])
            );

            $json = json_decode($response, true);

            if (empty($json['success'])) {
                $errors[] = 'CAPTCHA verification failed.';
            }

        } else {
            $errors[] = 'CAPTCHA validation is required.';
        }

        if (!$name) $errors[] = "Name is required.";
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        if (!$message) $errors[] = "Message is required.";

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
// JSON-LD SEO (uses final short_desc — AI or manual)
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

<!-- Optional: SEO meta description using AI text -->
<?php if (!empty($item['short_desc'])): ?>
<meta name="description" content="<?= htmlspecialchars($item['short_desc']) ?>">
<?php endif; ?>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>

<style>
/* =========================================================
   GLOBAL VARIABLES / BASE UI
========================================================= */
:root{
    --primary:#007BFF;
    --primary-dark:#0056b3;
    --accent:#28a745;
    --accent-dark:#1f8b39;
    --bg:#f2f4f8;
    --card-bg:#ffffff;
    --muted:#6b7280;
    --border:#d8dce6;
}

*{
    box-sizing:border-box;
}

body{
    margin:0;
    padding:0;
    background:var(--bg);
    font-family:Inter, "Segoe UI", Arial, sans-serif;
    color:#0f172a;
}

.container{
    max-width:1100px;
    margin:50px auto;
    padding:25px;
}

/* =========================================================
   PRODUCT CARD (LEFT image + RIGHT info)
========================================================= */
.product-card{
    background:var(--card-bg);
    padding:28px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:36px;
    border-radius:14px;
    box-shadow:0 8px 30px rgba(15,23,42,0.06);
}

/* =========================================================
   IMAGE: HOVER-ZOOM INSIDE BOX (NO MODAL)
========================================================= */
.image-thumb{
    width:100%;
    max-width:520px;
    border-radius:12px;
    overflow:hidden;
    cursor:zoom-in;
    background:#fff;
    box-shadow:0 6px 18px rgba(15,23,42,0.08);
    position:relative;
}

.image-thumb img{
    width:100%;
    height:auto;
    transition:transform 0.25s ease;
    transform-origin:center center;
}

/* =========================================================
   PRODUCT INFO TEXT
========================================================= */
.product-info h1{
    margin:0 0 8px 0;
    font-size:28px;
    font-weight:700;
}

.product-price{
    font-size:22px;
    color:var(--primary);
    font-weight:700;
    margin-bottom:12px;
}

.short-desc{
    font-size:15px;
    line-height:1.6;
    color:#374151;
    margin-bottom:18px;
}

/* =========================================================
   QUANTITY BUTTONS
========================================================= */
.quantity-controls{
    display:inline-flex;
    border:1px solid var(--border);
    border-radius:10px;
    overflow:hidden;
    background:#fff;
    align-items:center;
}

.quantity-controls button{
    background:var(--primary);
    color:#fff;
    width:42px;
    height:42px;
    font-size:20px;
    border:none;
    cursor:pointer;
    transition:background .2s;
}

.quantity-controls button:hover{
    background:var(--primary-dark);
}

.quantity-controls input{
    width:70px;
    text-align:center;
    font-size:16px;
    border:none;
    font-weight:600;
    background:#fff;
}

/* =========================================================
   BUTTONS (Add to Cart + Buy Now)
========================================================= */
.action-btn{
    padding:12px 22px;
    border:none;
    border-radius:10px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    color:#fff;
    transition:background .2s ease, transform .1s ease;
    box-shadow:0 4px 12px rgba(0,0,0,0.10);
}

.action-btn:active{
    transform:scale(.97);
}

.cart-btn{
    background:var(--primary);
}
.cart-btn:hover{
    background:var(--primary-dark);
}

.buy-btn{
    background:var(--accent);
}
.buy-btn:hover{
    background:var(--accent-dark);
}

/* =========================================================
   BUTTON + QUANTITY LAYOUT
========================================================= */
.product-action-form{
    margin-top:10px;
}

.quantity-row{
    display:flex;
    justify-content:flex-start;
    margin-bottom:14px;
}

.button-row{
    display:flex;
    gap:12px;
}

/* =========================================================
   ENQUIRY BOX (CARD STYLE)
========================================================= */
.enquiry-box{
    padding:25px;
    background:#fff;
    border-radius:14px;
    margin-top:30px;
    box-shadow:0 8px 30px rgba(15,23,42,0.05);
    border:1px solid #e8ebf2;
}

.enquiry-box h2{
    margin:0 0 18px 0;
    font-size:22px;
    font-weight:700;
    color:#0f172a;
}

.enquiry-box input,
.enquiry-box textarea{
    width:100%;
    padding:14px 16px;
    border-radius:10px;
    border:1px solid var(--border);
    font-size:15px;
    background:#fafbff;
    transition:all .2s;
}

.enquiry-box input:focus,
.enquiry-box textarea:focus{
    border-color:var(--primary);
    background:#fff;
    box-shadow:0 0 0 3px rgba(0,123,255,0.15);
}

textarea{
    min-height:120px;
    resize:vertical;
}

.enquiry-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:18px;
}

/* Send Button */
.send-btn{
    background:var(--accent);
    color:#fff;
    padding:12px 22px;
    border-radius:10px;
    border:none;
    font-weight:700;
    cursor:pointer;
    transition:.2s;
}

.send-btn:hover{
    background:var(--accent-dark);
}

/* Back Button */
.back-btn{
    font-size:15px;
    font-weight:600;
    color:var(--primary);
    text-decoration:none;
    transition:color .2s;
}

.back-btn:hover{
    color:var(--primary-dark);
}

/* =========================================================
   ALERT MESSAGES
========================================================= */
.success{
    background:#e6f9ee;
    border:1px solid #27ae60;
    color:#1b7f47;
    padding:14px;
    border-radius:10px;
    font-weight:600;
    margin-bottom:15px;
}

.error{
    background:#ffe6e9;
    border:1px solid #ff8a8a;
    color:#b71c1c;
    padding:14px;
    border-radius:10px;
    font-weight:600;
    margin-bottom:15px;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 980px){
    .product-card{
        grid-template-columns:1fr;
        gap:20px;
        padding:20px;
    }
    .container{
        margin:20px auto;
        padding:16px;
    }
}

/* =========================================================
   IMPROVED SHORT DESCRIPTION LAYOUT (AI TEXT SUPPORT)
   — No design changes, only enhancements
========================================================= */

.short-desc {
    font-size: 16px;
    line-height: 1.7;
    color: #2f3640;
    white-space: pre-line;      /* keeps AI line breaks */
    word-break: break-word;     /* prevents long words breaking layout */
    margin-top: 15px;
}

/* Optional: limit width for cleaner text block */
.product-info .short-desc {
    max-width: 90%;
}

/* Make paragraphs smoother */
.short-desc p {
    margin-bottom: 10px;
}

/* Better spacing on mobile */
@media (max-width: 600px){
    .short-desc{
        font-size: 15px;
        line-height: 1.6;
        max-width: 100%;
    }
}

</style>

</head>

<body>

<div class="container">

    <div class="product-card">

        <!-- IMAGE with Hover Zoom -->
        <div class="product-image">
            <div class="image-thumb" id="zoomBox">
                <img src="/uploads/<?= htmlspecialchars($item['image']) ?>">
            </div>
        </div>

        <!-- PRODUCT INFO -->
        <div class="product-info">

            <h1><?= htmlspecialchars($item['title']) ?></h1>

            <div class="product-meta">
                <div class="product-price">$<?= htmlspecialchars($item['price']) ?></div>
            </div>

            <!-- PRODUCT DESCRIPTION -->
       <?php if (!empty($item['short_desc'])): ?>
                       <div class="product-description">
        <h2 class="desc-title">Description</h2>
        <p><?= nl2br(htmlspecialchars((string)($item['short_desc'] ?? ""))) ?></p>
    </div>
<?php endif; ?>


            <!-- Add to Cart -->
            <form method="post" class="product-action-form">

                <div class="quantity-row">
                    <div class="quantity-controls">
                        <button type="button" id="minus">−</button>
                        <input type="text" id="quantity" name="quantity" value="1">
                        <button type="button" id="plus">+</button>
                    </div>
                </div>

                <div class="button-row">
                    <button type="submit" name="action_type" value="add_to_cart" class="action-btn cart-btn">
                        🛒 Add to Cart
                    </button>

                    <button type="submit" name="action_type" value="buy_now" class="action-btn buy-btn">
                        ⚡ Buy Now
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- ENQUIRY BOX -->
    <div class="enquiry-box">

        <h2>Enquire Now</h2>

        <?php if($enquirySuccess): ?>
            <div class="success">Enquiry Submitted Successfully</div>
        <?php elseif($errors): ?>
            <div class="error"><?= implode("<br>", array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
            <input type="hidden" name="action_type" value="enquiry">

            <input type="text" name="name" placeholder="Your Name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

            <div style="height:12px"></div>

            <input type="email" name="email" placeholder="Your Email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <div style="height:12px"></div>

            <textarea name="message" placeholder="Your Message"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

            <!-- Turnstile -->
            <div class="cf-turnstile"
                 data-sitekey="<?= $siteKey ?>"
                 data-theme="light"
                 data-mode="managed"
                 data-callback="onTurnstileSuccess">
            </div>

            <input type="hidden" name="cf-turnstile-response">

            <script>
            function onTurnstileSuccess(token) {
                document.querySelector("input[name='cf-turnstile-response']").value = token;
            }
            </script>

            <div class="enquiry-footer">
                <button type="submit" class="send-btn">Send Enquiry</button>
                <a href="/public/catalog.php" class="back-btn">← Back To Catalog</a>
            </div>

        </form>
    </div>

</div>

<script>
// Quantity
const qty = document.getElementById("quantity");
document.getElementById("plus").onclick = ()=> qty.value = (+qty.value||1)+1;
document.getElementById("minus").onclick = ()=> qty.value = Math.max(1,(+qty.value||1)-1);

// HOVER ZOOM
const zoomBox = document.getElementById("zoomBox");
const zoomImg = zoomBox.querySelector("img");

zoomBox.addEventListener("mousemove", (e) => {
    const r = zoomBox.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * 100;
    const y = ((e.clientY - r.top) / r.height) * 100;

    zoomImg.style.transform = "scale(2)";
    zoomImg.style.transformOrigin = `${x}% ${y}%`;
});

zoomBox.addEventListener("mouseleave", () => {
    zoomImg.style.transform = "scale(1)";
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const successMsg = document.querySelector(".success");

    if (successMsg) {
        setTimeout(() => {
            successMsg.style.opacity = "0";
            successMsg.style.transition = "opacity 0.5s ease";

            setTimeout(() => successMsg.remove(), 600);

        }, 2500);
    }
});
</script>

</body>
</html>
