<?php
// ====================
// Secure Session Setup
// ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => false, // Set true if using HTTPS
        'cookie_samesite' => 'Strict'
    ]);
}

// ====================
// CSRF Token
// ====================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====================
// Base URL Setup
// ====================
// ⚠️ Change this if your domain or folder differs
define('BASE_URL', 'http://chandusoft.test');
define('UPLOADS_URL', BASE_URL . '/uploads/');

// ====================
// Database Configuration
// ====================
$DB_HOST = '127.0.0.1';
$DB_NAME = 'chandusoft';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ====================
// Site Settings Defaults
// ====================
$site_name = "Chandusoft";

// ====================
// Upload Settings
// ====================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];

// ====================
// Cloudflare Turnstile Keys
// ====================
putenv('TURNSTILE_SITE=YOUR_SITE_KEY_HERE');     // Replace with your actual Turnstile site key
putenv('TURNSTILE_SECRET=YOUR_SECRET_KEY_HERE'); // Replace with your actual Turnstile secret key

// ====================
// Logging Setup
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
// Helper Functions
// ====================
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ====================
// Site Settings Database Functions
// ====================
function get_setting($key, $default = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function update_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    return $stmt->execute([$key, $value]);
}

// ====================
// Image Upload Handler
// ====================
function uploadImage($file) {
    global $allowed_mime_types;

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;
    if (!in_array(mime_content_type($file['tmp_name']), $allowed_mime_types)) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_') . '.' . $ext;
    $target = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) return false;

    // Convert to WebP if JPG/PNG
    if (in_array($ext, ['jpg','jpeg','png'])) {
        $webpFile = UPLOAD_DIR . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        $img = ($ext === 'png') ? imagecreatefrompng($target) : imagecreatefromjpeg($target);
        imagepalettetotruecolor($img);
        imagewebp($img, $webpFile, 80);
        imagedestroy($img);
        unlink($target);
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
    }

    return $filename;
}
