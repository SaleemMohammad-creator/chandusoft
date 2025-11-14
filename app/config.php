<?php
// ============================================================
// 🚀 Secure Config Loader (Chandusoft Store)
// ============================================================

// ------------------------------------------------------------
// 🛡️ Prevent Double Loading (Guard Clause)
// ------------------------------------------------------------
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// ====================
// 1️⃣ Load .env Variables
// ====================
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, "\"' ");
        putenv("$name=$value");
    }
} else {
    die("❌ .env file missing! Please create one in the project root.");
}

// ✅ Expand ${BASE_URL} placeholders dynamically
$base = getenv('BASE_URL') ?: '';
foreach ($_ENV as $key => $val) {
    if (is_string($val) && strpos($val, '${BASE_URL}') !== false) {
        putenv("$key=" . str_replace('${BASE_URL}', $base, $val));
    }
}

// ====================
// 2️⃣ Optimized Secure Session Setup (AUTO HTTPS + NGROK DETECT)
// ====================
$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false);

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (
    str_contains($host, 'localhost') ||
    str_contains($host, '.test') ||
    str_contains($host, '127.0.0.1')
);

$cookieSecure = $isHttps && !$isLocal;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $cookieSecure,
        'cookie_samesite' => $cookieSecure ? 'Strict' : 'Lax'
    ]);
}

// ====================
// 3️⃣ CSRF Token Setup
// ====================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====================
// 4️⃣ Base URL Setup
// ====================
if (!defined('BASE_URL')) define('BASE_URL', getenv('BASE_URL'));
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', BASE_URL . '/uploads/');

// ====================
// 5️⃣ Database Configuration
// ====================
$DB_HOST = getenv('DB_HOST');
$DB_NAME = getenv('DB_NAME');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');
$DB_CHARSET = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ====================
// 6️⃣ Mailpit Logging
// ====================
if (!defined('MAILPIT_LOGGING')) define('MAILPIT_LOGGING', getenv('MAILPIT_LOGGING') === 'true');
if (!defined('MAILPIT_HOST')) define('MAILPIT_HOST', getenv('MAILPIT_HOST'));
if (!defined('MAILPIT_PORT')) define('MAILPIT_PORT', getenv('MAILPIT_PORT'));
if (!defined('MAILPIT_LOG_EMAIL_TO')) define('MAILPIT_LOG_EMAIL_TO', getenv('MAILPIT_LOG_EMAIL_TO'));
if (!defined('MAILPIT_LOG_EMAIL_FROM')) define('MAILPIT_LOG_EMAIL_FROM', getenv('MAILPIT_LOG_EMAIL_FROM'));

// ====================
// 7️⃣ Site Defaults
// ====================
$site_name = getenv('APP_NAME') ?: "Chandusoft Store";

// ====================
// 8️⃣ Upload Settings
// ====================
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/');
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];

// ====================
// ✅ 9️⃣ Cloudflare Turnstile (Auto Local / Test fallback)
// ====================
$TURNSTILE_SITE   = getenv('TURNSTILE_SITE');
$TURNSTILE_SECRET = getenv('TURNSTILE_SECRET');

// 🔄 Auto fallback when in localhost or missing values
if ($isLocal && (!$TURNSTILE_SITE || !$TURNSTILE_SECRET)) {
    $TURNSTILE_SITE   = "1x00000000000000000000AA";  // Test key
    $TURNSTILE_SECRET = "1x0000000000000000000000000000000AA"; // Test secret
}

// final constants used everywhere
if (!defined('TURNSTILE_SITE'))   define('TURNSTILE_SITE', $TURNSTILE_SITE);
if (!defined('TURNSTILE_SECRET')) define('TURNSTILE_SECRET', $TURNSTILE_SECRET);

// ====================
// 🔟 Stripe Configuration
// ====================
if (!defined('STRIPE_PUBLISHABLE_KEY')) define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY'));
if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY'));
if (!defined('STRIPE_WEBHOOK_SECRET')) define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET'));

if (empty(STRIPE_SECRET_KEY)) {
    die('❌ Stripe secret key not found in .env file.');
}

// ====================
// 1️⃣1️⃣ PayPal Configuration
// ====================
if (!defined('PAYPAL_MODE')) define('PAYPAL_MODE', getenv('PAYPAL_MODE'));
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID'));
if (!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', getenv('PAYPAL_SECRET'));
if (!defined('PAYPAL_RETURN_URL')) define('PAYPAL_RETURN_URL', getenv('PAYPAL_RETURN_URL'));
if (!defined('PAYPAL_CANCEL_URL')) define('PAYPAL_CANCEL_URL', getenv('PAYPAL_CANCEL_URL'));
if (!defined('PAYPAL_CURRENCY')) define('PAYPAL_CURRENCY', getenv('PAYPAL_CURRENCY'));
if (!defined('PAYPAL_BASE_URL')) define('PAYPAL_BASE_URL', getenv('PAYPAL_BASE_URL'));
if (!defined('PAYPAL_CHECKOUT_URL')) define('PAYPAL_CHECKOUT_URL', getenv('PAYPAL_CHECKOUT_URL'));

// ====================
// 1️⃣2️⃣ Logging Setup
// ====================
$log_dir = __DIR__ . '/../storage/logs/';
if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);

if (!function_exists('logMessage')) {
    function logMessage($message) {
        global $log_dir;
        $date = date('Y-m-d H:i:s');
        file_put_contents($log_dir . 'app.log', "[$date] $message" . PHP_EOL, FILE_APPEND);
    }
}

// ====================
// 1️⃣3️⃣ Include Helpers & Logger
// ====================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mail-logger.php';

// ====================
// 1️⃣4️⃣ Site Settings (Database Functions)
// ====================
if (!function_exists('get_setting')) {
    function get_setting($key, $default = null) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }
}

if (!function_exists('update_setting')) {
    function update_setting($key, $value) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        return $stmt->execute([$key, $value]);
    }
}

// ====================
// 1️⃣5️⃣ Extra Utility: Order Reference
// ====================
if (!function_exists('generate_order_ref')) {
    function generate_order_ref(): string {
        try {
            return strtoupper(bin2hex(random_bytes(6)));
        } catch (Exception $e) {
            return 'ORD' . time();
        }
    }
}
