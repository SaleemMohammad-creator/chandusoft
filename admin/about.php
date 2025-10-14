<?php
require_once __DIR__ . '/../app/config.php';

// Fetch recent CMS pages for header
$navStmt = $pdo->prepare("
    SELECT title, slug
    FROM pages
    WHERE status='published'
    ORDER BY created_at DESC
    LIMIT 5
");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// Current page slug
$pageSlug = 'about';
$servicesLink = "/admin/services"; // Pretty URL
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us - Chandusoft</title>
<link rel="stylesheet" href="/admin/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="page-wrapper">

    <!-- Header -->
    

    <!-- Main Content -->
    <main>
        <h4><span class="section-title">About Us</span></h4>
        <p>
            <span class="highlight">Chandusoft</span> is a well-established company with over
            <span class="highlight">15 years of experience</span> in delivering IT and BPO solutions. We have a team of more than 
            <span class="highlight">200 skilled professionals</span> operating from multiple locations. One of our key strengths is 
            <span class="highlight">24/7 operations</span>, which allows us to support clients across different time zones. We place a strong emphasis on <span class="highlight">data integrity and security</span>, which has helped us earn long-term trust from our partners. Our core service areas include
            <span class="highlight">Software Development</span>,
            <span class="highlight">Medical Process Services</span>, and
            <span class="highlight">E-Commerce Solutions</span>, all backed by a commitment to
            <span class="highlight">quality and process excellence.</span>
        </p>
    </main>

    <!-- Footer -->
    <?php include("footer.php"); ?>

</div>
</body>
</html>
