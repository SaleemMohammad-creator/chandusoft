<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/helpers.php';

// Get slug from URL, default to 'index'
$slug = $_GET['slug'] ?? 'index';

// Fetch CMS page
try {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=:slug AND status='published'");
    $stmt->execute(['slug' => $slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $page = null;
}

// Only 404 if page not found AND it's not the homepage
if (!$page && $slug !== 'index') {
    http_response_code(404);
    echo "<h1>404 - Page not found</h1>";
    exit;
}

// Navigation recent pages
$navStmt = $pdo->prepare("SELECT title, slug FROM pages WHERE status='published' ORDER BY created_at DESC LIMIT 5");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// Page content
$title = $page['title'] ?? '';
$content = $page['content_html'] ?? '';

// Remove the first <h1> from content if exists
$content = preg_replace('/<h1.*?>.*?<\/h1>/i', '', $content, 1);

$currentPage = $slug;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft - <?= htmlspecialchars($title) ?></title>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/admin/header.php'; ?>

<main>
<?php if ($slug === 'index'): ?>
    <!-- Homepage hero & testimonials -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="/services.php" class="btn-hero"><b>Explore Services</b></a>
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
</section>
<?php else: ?>
    <!-- CMS page content -->
    <section class="cms-page">
        <div class="cms-content">
            <?= $content ?: '<p>Content not available.</p>' ?>
        </div>
    </section>
<?php endif; ?>
</main>

<?php include __DIR__ . '/admin/footer.php'; ?>
<button id="back-to-top" title="Back to Top">↑</button>
    <script src="include.js"></script>
</body>
</html>
