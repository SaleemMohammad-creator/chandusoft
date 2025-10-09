<?php
require_once __DIR__ . '/../app/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chandusoft</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("header.php"); ?>

<main>
<?php
$pageSlug = $_GET['page'] ?? '';

if ($pageSlug && $pageSlug !== 'home') {
    // Fetch the clicked page content
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug AND status = 'published'");
    $stmt->execute(['slug' => $pageSlug]);
    $page = $stmt->fetch();

    if ($page) {
        echo "<section class='page-content'>";
        echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
        echo "<div>" . $page['content_html'] . "</div>";
        echo "</section>";
    } else {
        echo "<section><h2>Page not found</h2></section>";
    }
} else {
    // Home page content
    ?>
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="services.php" class="btn-hero"><b>Explore Services</b></a>
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

<!-- The "Back to Top" button -->
    <button id="back-to-top" title="Back to Top">↑</button>

<?php include("footer.php"); ?>
</body>
</html>
