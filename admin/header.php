<?php
require_once __DIR__ . '/../app/config.php';

// --- Define site home page ---
// Since your actual home page is admin/index.php, set it here
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
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    // Inside admin folder
    $basePath = ''; // images are in admin/images relative to admin/index.php
} else {
    // Inside root folder
    $basePath = 'admin/'; // images in admin/images
}
?>

<header>
    <div class="logo">
        <a href="<?php echo SITE_HOME; ?>">
            <img src="<?php echo $basePath; ?>images/logo.jpg" alt="Chandusoft Technologies" width="400" height="70">
        </a>
    </div>

    <nav>
        <!-- Static buttons -->
        <a href="<?php echo SITE_HOME; ?>" class="<?= empty($currentPage) ? 'active' : '' ?>">Home</a>
        <a href="<?php echo $basePath; ?>about.php" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="<?php echo $basePath; ?>services.php" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic pages from database -->
        <?php foreach ($recentPages as $page): ?>
            <a href="<?php echo $basePath; ?>index.php?page=<?= htmlspecialchars($page['slug']) ?>"
               class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
                <?= htmlspecialchars($page['title']) ?>
            </a>
        <?php endforeach; ?>

        <!-- Static contact button -->
        <a href="<?php echo $basePath; ?>contact.php" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>


