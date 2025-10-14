<?php
// ------------------------
// Logging Setup (only once)
// ------------------------
define('LOG_FILE', __DIR__ . '/../storage/logs/app.log');

if (!function_exists('logMessage')) {
    function logMessage($message) {
        $date = date('Y-m-d H:i:s');
        file_put_contents(LOG_FILE, "[$date] $message" . PHP_EOL, FILE_APPEND);
    }
}

// Handle PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logMessage("Error [$errno] in $errfile at line $errline: $errstr");
});

// Handle uncaught exceptions
set_exception_handler(function($exception) {
    logMessage("Uncaught Exception: " . $exception->getMessage() .
               " in " . $exception->getFile() .
               " at line " . $exception->getLine());
});

// ------------------------
// App Setup
// ------------------------
require_once __DIR__ . '/../app/config.php';

// --- Define site home page ---
define('SITE_HOME', '/admin/index');

// Fetch 5 most recent published pages for header nav
$navStmt = $pdo->prepare("
    SELECT title, slug
    FROM pages
    WHERE status = 'published'
    ORDER BY created_at DESC
    LIMIT 5
");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------
// Determine current page slug (pretty URL support)
// ------------------------
$pageSlug = $_GET['page'] ?? null;

if (!$pageSlug) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $segments = explode('/', $uri);

    if ($segments[0] === 'admin') {
        $pageSlug = $segments[1] ?? 'index';
    } else {
        $pageSlug = 'index';
    }
}

// Base path for images/links
$basePath = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : 'admin/';

// Check if CMS Services page exists
$stmt = $pdo->prepare("SELECT slug FROM pages WHERE slug='services' AND status='published'");
$stmt->execute();
$cmsService = $stmt->fetch();
$servicesLink = $cmsService ? "/admin/services" : "/admin/services.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft Admin</title>
<link rel="stylesheet" href="<?= $basePath ?>styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include_once("header.php"); ?>

<main>
<?php if ($pageSlug === 'index'): ?>

    <!-- Home Page Content -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="<?= $servicesLink ?>" class="btn-hero"><b>Explore Services</b></a>
        </div>
    </section>

    <section class="testimonials">
        <h2 style="color: rgb(42, 105, 240);">What Our Clients Say</h2>
        <div class="testimonial-container">
            <div class="testimonial">
                <p>"Chandusoft helped us streamline our processes. Their 24/7 support means we never miss a client query."</p>
                <h4>John Smith</h4>
                <span>Operations Manager, GlobalTech</span>
            </div>
            <div class="testimonial">
                <p>"Our e-commerce platform scaled smoothly after migrating with Chandusoft. Sales grew by 40% in just 6 months!"</p>
                <h4>Priya Verma</h4>
                <span>Founder, TrendyMart</span>
            </div>
            <div class="testimonial">
                <p>"The QA team at Chandusoft made our product launch seamless. Bug-free delivery on time!"</p>
                <h4>Ahmed Khan</h4>
                <span>Product Lead, Medisoft</span>
            </div>
        </div>
    </section>

<?php else: ?>

    <!-- CMS or Static Page -->
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug AND status = 'published'");
        $stmt->execute(['slug' => $pageSlug]);
        $page = $stmt->fetch();

        if ($page) {
            echo "<section class='page-content'>";
            echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
            echo "<div>" . $page['content_html'] . "</div>";
            echo "</section>";
        } else {
            $staticFile = $pageSlug . ".php";
            if (file_exists($staticFile)) {
                include $staticFile;
            } else {
                http_response_code(404);
                echo "<section><h2>404 - Page Not Found</h2></section>";
                logMessage("Page not found: $pageSlug");
            }
        }
    } catch (Exception $e) {
        logMessage("Database/Query Error for page '$pageSlug': " . $e->getMessage());
        echo "<section><h2>Something went wrong. Please try again later.</h2></section>";
    }
    ?>

<?php endif; ?>
</main>

<button id="back-to-top" title="Back to Top">↑</button>
<script src="<?= $basePath ?>include.js"></script>

<?php include_once("footer.php"); ?>
</body>
</html>
