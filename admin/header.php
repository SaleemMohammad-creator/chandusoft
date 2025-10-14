<?php
// Avoid redeclaring logMessage
if (!function_exists('logMessage')) {
    function logMessage($msg) {
        $date = date('Y-m-d H:i:s');
        file_put_contents(__DIR__ . '/../storage/logs/app.log', "[$date] $msg" . PHP_EOL, FILE_APPEND);
    }
}

// Current page slug
$currentPage = $pageSlug ?? 'index';

// Ensure $recentPages exists (fallback to empty array)
$recentPages = $recentPages ?? [];

// Ensure $servicesLink exists (fallback to static services.php)
$servicesLink = $servicesLink ?? "/admin/services.php";
?>

<header>
    <div class="logo">
        <a href="/admin/index">
            <img src="/admin/images/logo.jpg" alt="Chandusoft Technologies" width="400" height="70">
        </a>
    </div>

    <nav>
        <!-- Static links -->
        <a href="/admin/index" class="<?= ($currentPage === 'index') ? 'active' : '' ?>">Home</a>
        <a href="/admin/about" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="<?= $servicesLink ?>" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic CMS pages -->
        <?php if (!empty($recentPages)): ?>
            <?php foreach ($recentPages as $page): ?>
                <a href="/admin/<?= htmlspecialchars($page['slug']) ?>"
                   class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
                   <?= htmlspecialchars($page['title']) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Static contact -->
        <a href="/admin/contact" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
