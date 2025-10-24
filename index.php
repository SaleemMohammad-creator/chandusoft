<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/helpers.php';

// Get slug from URL, default to 'index'
$slug = $_GET['slug'] ?? 'index';

// Fetch page from CMS table safely
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=:slug AND status='published'");
$stmt->execute(['slug' => $slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

// Only trigger 404 if page not found AND it's not the homepage
if (!$page && $slug !== 'index') {
    http_response_code(404);
    echo "<h1>404 - Page not found</h1>";
    exit;
}

// Fetch recent CMS pages for navigation (optional)
$navStmt = $pdo->prepare("SELECT title, slug FROM pages WHERE status='published' ORDER BY created_at DESC LIMIT 5");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// Page content (safe)
$title = isset($page['title']) ? htmlspecialchars($page['title']) : '';
$content = isset($page['content']) ? $page['content'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft - <?= $title ?></title>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/admin/header.php'; ?>

<main>
<?php
// Keep homepage hero and testimonials intact
if ($slug === 'index'): ?>
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="/services.php" class="btn-hero"><b>Explore Services</b></a>
        </div>
    </section>

    <section class="testimonials">
        <h2>What Our Clients Say</h2>
        <div class="testimonial-container">
            <div class="testimonial">
                <p>"Chandusoft helped us streamline our processes..."</p>
                <h4>John Smith</h4>
                <span>Operations Manager, GlobalTech</span>
            </div>
            <div class="testimonial">
                <p>"Our e-commerce platform scaled smoothly..."</p>
                <h4>Priya Verma</h4>
                <span>Founder, TrendyMart</span>
            </div>
        </div>
    </section>
<?php
else:
    // Display CMS content safely for other pages
    echo $content;
endif;
?>
</main>

<?php include __DIR__ . '/admin/footer.php'; ?>
</body>
</html>
