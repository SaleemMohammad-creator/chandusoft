<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Services</title>
    <!-- Global Styles -->
    <link rel="stylesheet" href="/styles.css">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
/* ============================
   GLOBAL
============================ */
body {
    font-family: Arial, Helvetica, sans-serif;
    color: #030303;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
}

/* Center main content */
main {
    padding: 40px 20px;
}

/* ============================
   SERVICES SECTION
============================ */
#Services {
    text-align: center;
    padding: 20px;
}

#Services h2 {
    color: #2d5be3;
    font-size: 32px;
    margin-bottom: 40px;
}

/* Grid layout */
.services-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    max-width: 1100px;
    margin: auto;
}

/* Service card styling */
.service-card {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    text-align: left;
    transition: 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

.service-card i {
    font-size: 32px;
    margin-bottom: 15px;
    display: block;
}

/* Icon Colors */
.icon-blue   { color: #63b3cc; }
.icon-green  { color: #575a91; }
.icon-black  { color: #333; }
.icon-yellow { color: #fbc02d; }
.icon-purple { color: #27b092; }
.icon-red    { color: #eb1b0c; }

.service-card h3 {
    font-size: 18px;
    font-weight: bold;
    margin: 0 0 10px;
}

.service-card p {
    font-size: 14px;
    color: #555;
    margin: 0;
}

/* Highlight text */
.highlight {
    font-weight: bold;
    color: black;
    background-color: white;
    padding: 4px 4px;
    border-radius: 4px;
}

/* Back to Top Button */
#back-to-top {
    position: fixed;
    bottom: 25px;
    right: 25px;
    padding: 10px 14px;
    background: #2d5be3;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: none;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.3);
}

#back-to-top:hover {
    background: #1b42a8;
}
</style>

</head>
<body>

<?php include __DIR__ . '/admin/header.php'; ?>

<main>
<section id="Services">

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

 <button id="back-to-top" title="Back to Top">↑</button>
    <script src="include.js"></script>

</body>
</html>
