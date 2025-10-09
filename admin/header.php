<?php
require_once __DIR__ . '/../app/config.php';

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
?>

<header>
    <div class="logo">
    <a href="/index.php">
        <img src="<?= (basename(__DIR__) === 'admin') ? 'images/logo.jpg' : 'admin/images/logo.jpg' ?>" 
             alt="Chandusoft Technologies" width="400" height="70">
    </a>
</div>


    <nav>
        <!-- Static buttons -->
        <a href="index.php" class="<?= empty($currentPage) ? 'active' : '' ?>">Home</a>
        <a href="about.php" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="services.php" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic slugs -->
        <?php foreach ($recentPages as $page): ?>
            <a href="index.php?page=<?= htmlspecialchars($page['slug']) ?>"
               class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
                <?= htmlspecialchars($page['title']) ?>
            </a>
        <?php endforeach; ?>

        <!-- Static button -->
        <a href="contact.php" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
