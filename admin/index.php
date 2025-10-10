<?php
// ------------------------
// Logging Setup
// ------------------------
define('LOG_FILE', __DIR__ . '/../storage/logs/app.log');


/**
 * Log messages with timestamp
 */
function logMessage($message) {
    $date = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// General PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $msg = "Error [$errno] in $errfile at line $errline: $errstr";
    logMessage($msg);
});

// Uncaught exceptions
set_exception_handler(function($exception) {
    $msg = "Uncaught Exception: " . $exception->getMessage() .
           " in " . $exception->getFile() .
           " at line " . $exception->getLine();
    logMessage($msg);
});

// Mail sending function with logging
function sendEmail($to, $subject, $body) {
    try {
        $success = mail($to, $subject, $body);
        if (!$success) {
            throw new Exception("Mail sending failed to: $to, Subject: $subject");
        }
        logMessage("Mail sent successfully to $to with subject '$subject'");
    } catch (Exception $e) {
        logMessage("Mail Error: " . $e->getMessage());
    }
}

// ------------------------
// App Setup
// ------------------------
require_once __DIR__ . '/../app/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chandusoft</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include("header.php"); ?>

<main>
<?php
$pageSlug = $_GET['page'] ?? 'home';

if ($pageSlug !== 'home') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=:slug AND status='published'");
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
                echo "<section><h2>Page not found</h2></section>";
                logMessage("Page not found: $pageSlug");
            }
        }
    } catch (Exception $e) {
        logMessage("Database/Query Error for page '$pageSlug': " . $e->getMessage());
        echo "<section><h2>Something went wrong. Please try again later.</h2></section>";
    }
} else {
    ?>
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="<?= $cmsService ? "index.php?page=services" : "services.php" ?>" class="btn-hero"><b>Explore Services</b></a>
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
    <?php
}
?>
</main>

<button id="back-to-top" title="Back to Top">↑</button>
<script src="include.js"></script>

<?php include("footer.php"); ?>
</body>
</html>
