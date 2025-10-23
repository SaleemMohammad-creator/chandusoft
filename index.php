<?php
require_once __DIR__ . '/app/config.php';
$pageSlug = 'index';

// Fetch recent pages for nav
$navStmt = $pdo->prepare("SELECT title, slug FROM pages WHERE status='published' ORDER BY created_at DESC LIMIT 5");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft - Home</title>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/admin/header.php'; ?>

<main>
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Chandusoft</h1>
            <p>Delivering IT & BPO solutions for over 15 years.</p>
            <a href="/services" class="btn-hero"><b>Explore Services</b></a>
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
</main>

<?php include __DIR__ . '/admin/footer.php'; ?>
</body>
</html>
