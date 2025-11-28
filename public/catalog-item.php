<?php
// ===============================================
// 🛒 Catalog Item Page (Add to Cart + Enquiry)
// Updated: Amazon-style UI + Extra Sections
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

// ⭐ Generate AI Description ONLY IF short_desc is empty (first time)
try {
    if (function_exists('aiGenerateDescription')) {

        if (!isset($item['short_desc']) || trim($item['short_desc']) === "") {

            // Generate description for the FIRST time only
            $ai_desc = aiGenerateDescription($item['title']);

            if (!empty($ai_desc)) {

                // Save permanently inside catalog table
                $upd = $pdo->prepare("UPDATE catalog SET short_desc = :d WHERE id = :id");
                $upd->execute([
                    ':d' => $ai_desc,
                    ':id' => $item['id']
                ]);

                // Update current page variable
                $item['short_desc'] = $ai_desc;

                logMessage("AI Description created FIRST TIME for '{$item['title']}'");
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

<?php if (!empty($item['short_desc'])): ?>
<meta name="description" content="<?= htmlspecialchars($item['short_desc']) ?>">
<?php endif; ?>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>

<style>
/* ============================================================
   PREMIUM OPTIMIZED STYLE — AMAZON THEME + DARK MODE SUPPORT
   Light + Dark Mode | Cleaner | Faster | No Unused Code
============================================================ */

:root {
    --primary:#131921;
    --accent:#ffa41c;
    --accent-hover:#ff8f00;
    --buy:#ffd814;
    --buy-hover:#f7ca00;
    --bg:#f3f4f6;
    --card-bg:#ffffff;
    --text:#111827;
    --muted:#6b7280;
    --border:#d1d5db;
    --success-bg:#ecfdf3;
    --success-border:#bbf7d0;
    --success-text:#15803d;
    --danger-bg:#fef2f2;
    --danger-border:#fecaca;
    --danger-text:#b91c1c;
}

/* -------- DARK MODE -------- */
@media (prefers-color-scheme: dark) {
    :root {
        --bg:#0f172a;
        --card-bg:#1e293b;
        --text:#f1f5f9;
        --muted:#94a3b8;
        --border:#334155;
        --accent:#ffb347;
        --accent-hover:#ffa41c;
        --buy:#ffe766;
        --buy-hover:#ffd814;
    }
}

* { box-sizing:border-box; }

body {
    margin:0;
    padding:0;
    font-family:Inter, "Segoe UI", Arial, sans-serif;
    background:var(--bg);
    color:var(--text);
    line-height:1.55;
}

a { text-decoration:none; color:#007185; }

/* ---------------- Container ---------------- */
.container {
    max-width:1150px;
    margin:20px auto 40px;
    padding:0 14px;
}

/* ---------------- Breadcrumb ---------------- */
.breadcrumb {
    font-size:13px;
    color:var(--muted);
    display:flex;
    align-items:center;
    gap:6px;
}
.breadcrumb span.sep { color:var(--muted); }

/* ---------------- Product Card ---------------- */
.product-card {
    background:var(--card-bg);
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:26px;
    border-radius:8px;
    border:1px solid var(--border);
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
    padding:22px;
}

@media(max-width:900px){
    .product-card {
        grid-template-columns:1fr;
        padding:16px;
        gap:18px;
    }
}

/* ---------------- Product Image ---------------- */
.image-thumb {
    width:100%;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    cursor:zoom-in;
}
.image-thumb img {
    width:100%;
    transition:transform .25s ease;
}

/* ---------------- Product Info ---------------- */
.product-info h1 {
    font-size:26px;
    font-weight:700;
    margin:0 0 8px;
}

.meta-row {
    display:flex;
    align-items:center;
    gap:12px;
    font-size:14px;
}

.rating { color:#f59e0b; }

.stock-badge {
    font-size:12px;
    padding:3px 9px;
    border-radius:999px;
    border:1px solid #22c55e;
    background:#dcfce7;
    color:#166534;
}

.price-main {
    font-size:26px;
    color:#b12704;
    font-weight:700;
}

.price-note {
    color:var(--muted);
    font-size:13px;
    margin-bottom:10px;
}

.badge-row {
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-bottom:14px;
}
.badge-pill {
    font-size:12px;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid var(--border);
}

/* ---------------- Description ---------------- */
.product-description {
    margin-bottom:18px;
}
.product-description p {
    white-space:pre-line;
    font-size:15px;
    color:var(--text);
}

/* ---------------- Quantity ---------------- */
.quantity-row {
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:12px;
}

.quantity-controls {
    display:flex;
    border:1px solid var(--border);
    border-radius:6px;
    overflow:hidden;
}
.quantity-controls button {
    width:38px;
    height:38px;
    background:#e5e7eb;
    border:none;
    cursor:pointer;
    font-size:18px;
}
.quantity-controls input {
    width:60px;
    border:none;
    text-align:center;
    font-size:16px;
    background:var(--card-bg);
}

/* ---------------- Buttons ---------------- */
.button-row {
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.action-btn {
    padding:11px 22px;
    border:none;
    border-radius:20px;
    font-weight:600;
    cursor:pointer;
    transition:background .2s, transform .1s;
}

.cart-btn {
    background:var(--accent);
}
.cart-btn:hover { background:var(--accent-hover); }

.buy-btn {
    background:var(--buy);
}
.buy-btn:hover { background:var(--buy-hover); }

.action-btn:active { transform:scale(.97); }

/* ---------------- Enquiry Box ---------------- */
.enquiry-box {
    margin-top:26px;
    background:var(--card-bg);
    padding:20px;
    border-radius:8px;
    border:1px solid var(--border);
    box-shadow:0 4px 16px rgba(0,0,0,0.06);
}

.enquiry-box h2 {
    margin:0 0 12px;
    font-size:18px;
    font-weight:600;
}

.enquiry-box input,
.enquiry-box textarea {
    width:100%;
    padding:11px;
    border-radius:6px;
    border:1px solid var(--border);
    background:#f9fafb;
    font-size:14px;
    margin-bottom:10px;
}

/* FIX TEXTAREA OVERFLOW */
.enquiry-box textarea {
    resize: vertical;     
    max-width: 100%;      
    box-sizing: border-box;
}

.enquiry-box input:focus,
.enquiry-box textarea:focus {
    border-color:#007185;
    background:#fff;
    outline:none;
}


.send-btn {
    background:#007185;
    color:#fff;
    padding:10px 22px;
    border-radius:20px;
    border:none;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}

/* ---------------- Alerts ---------------- */
.success {
    background:var(--success-bg);
    color:var(--success-text);
    border:1px solid var(--success-border);
    padding:10px;
    border-radius:6px;
    margin-bottom:10px;
}

.error {
    background:var(--danger-bg);
    color:var(--danger-text);
    border:1px solid var(--danger-border);
    padding:10px;
    border-radius:6px;
    margin-bottom:10px;
}

/* ---------------- Responsive Tweaks ---------------- */
@media(max-width:640px){
    .product-info h1 { font-size:22px; }
    .price-main { font-size:22px; }
    .button-row { flex-direction:column; }
}

/* ---------------- Enquiry Footer: Back Button Right ---------------- */
.enquiry-footer {
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.enquiry-footer .back-btn {
    margin-left: auto;   /* pushes Back to Catalog to the RIGHT */
    font-size: 13px;
    font-weight: 500;
    color: #007185;
}

</style>
</head>

<body>

<div class="container">

    <!-- Breadcrumb -->
    <div class="page-header">
        <div class="breadcrumb">
            <a href="/public/catalog.php">All Products</a>
            <span class="sep">›</span>
            <span><?= htmlspecialchars($item['title']) ?></span>
        </div>
    </div>

    <!-- MAIN PRODUCT CARD -->
    <div class="product-card">

        <!-- LEFT: IMAGE -->
        <div class="product-image">
            <div class="image-thumb" id="zoomBox">
                <img src="/uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
            </div>
        </div>

        <!-- RIGHT: INFO -->
        <div class="product-info">

            <h1><?= htmlspecialchars($item['title']) ?></h1>

            <div class="meta-row">
                <div class="stock-badge">In stock</div>
            </div>

            <div class="price-block">
                <div class="price-main">$<?= htmlspecialchars($item['price']) ?></div>
                <div class="price-note">Inclusive of all taxes</div>
            </div>

            <div class="badge-row">
                <span class="badge-pill">Best quality</span>
                <span class="badge-pill">Fast delivery</span>
                <span class="badge-pill">Secure checkout</span>
            </div>

            <!-- DESCRIPTION -->
            <?php if (!empty($item['short_desc'])): ?>
                <div class="product-description">
                    <h2 class="desc-title">Description</h2>
                    <p><?= nl2br(htmlspecialchars((string)($item['short_desc'] ?? ""))) ?></p>
                </div>
            <?php endif; ?>

            <!-- ACTIONS -->
            <form method="post" class="product-action-form">

                <div class="quantity-row">
                    <span class="quantity-label">Quantity:</span>
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

        <h2>Enquire About This Product</h2>

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

            <div style="height:8px"></div>

            <input type="email" name="email" placeholder="Your Email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <div style="height:8px"></div>

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
                const hidden = document.querySelector("input[name='cf-turnstile-response']");
                if (hidden) hidden.value = token;
            }
            </script>

            <div class="enquiry-footer">
                <button type="submit" class="send-btn">Send Enquiry</button>
                <a href="/public/catalog.php" class="back-btn">← Back to Catalog</a>
            </div>

        </form>
    </div>

</div>

<script>
// Quantity
const qty = document.getElementById("quantity");
const plusBtn = document.getElementById("plus");
const minusBtn = document.getElementById("minus");

if (qty && plusBtn && minusBtn) {
    plusBtn.onclick = () => qty.value = (+qty.value || 1) + 1;
    minusBtn.onclick = () => qty.value = Math.max(1, (+qty.value || 1) - 1);
}

// HOVER ZOOM
const zoomBox = document.getElementById("zoomBox");
if (zoomBox) {
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
}

// Auto-hide success
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
