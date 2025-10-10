<?php
require_once __DIR__ . '/../app/config.php';

// --- Define site home page ---
define('SITE_HOME', '/admin/index.php');

// Fetch 5 most recent published pages
$navStmt = $pdo->prepare("
    SELECT title, slug 
    FROM pages 
    WHERE status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$navStmt->execute();
$recentPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine current active page
$currentPage = $_GET['page'] ?? '';

// Determine base path for images and links
$basePath = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : 'admin/';

// Check if CMS Services page exists
$stmt = $pdo->prepare("SELECT slug FROM pages WHERE slug='services' AND status='published'");
$stmt->execute();
$cmsService = $stmt->fetch();
$servicesLink = $cmsService ? $basePath . "index.php?page=services" : $basePath . "services.php";
?>

<header>
    <div class="logo">
        <a href="<?= SITE_HOME ?>">
            <img src="<?= $basePath ?>images/logo.jpg" alt="Chandusoft Technologies" width="400" height="70">
        </a>
    </div>

    <nav>
        <!-- Static buttons -->
        <a href="<?= SITE_HOME ?>" class="<?= empty($currentPage) ? 'active' : '' ?>">Home</a>
        <a href="<?= $basePath ?>about.php" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="<?= $servicesLink ?>" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic pages from database -->
        <?php foreach ($recentPages as $page): ?>
            <a href="<?= $basePath ?>index.php?page=<?= htmlspecialchars($page['slug']) ?>"
               class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
                <?= htmlspecialchars($page['title']) ?>
            </a>
        <?php endforeach; ?>

        <!-- Static contact button -->
        <a href="<?= $basePath ?>contact.php" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
