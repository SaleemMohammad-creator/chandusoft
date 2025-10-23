<?php
require_once __DIR__ . '/app/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Services</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/admin/header.php'; ?>

<main>
    <section class="services-section">
        <h2>Our Services</h2>
        <div class="services-container">
            <div class="service-card">
                <i class="fas fa-building icon-blue"></i>
                <h3>Enterprise Application Solution</h3>
                <p>Robust enterprise apps for seamless business operations.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-mobile-alt icon-green"></i>
                <h3>Mobile Application Solution</h3>
                <p>Cross-platform mobile apps with modern UI/UX.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-laptop icon-black"></i>
                <h3>Web Portal Design & Solution</h3>
                <p>Custom web portals for business and customer engagement.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-tools icon-yellow"></i>
                <h3>Web Portal Maintenance & Content Management</h3>
                <p>Continuous support, updates, and content handling.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-vial icon-purple"></i>
                <h3>QA & Testing</h3>
                <p>Quality assurance and testing for bug-free releases.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-phone icon-red"></i>
                <h3>Business Process Outsourcing</h3>
                <p>End-to-end BPO services with 24/7 operations.</p>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/admin/footer.php'; ?>
</body>
</html>
