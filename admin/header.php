<?php
// Ensure variables are defined
$recentPages = $recentPages ?? [];
$currentPage = $currentPage ?? 'index';

// Include config & helpers
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Site settings
$site_name = get_setting('site_name') ?? 'Chandusoft Technologies';
$site_logo = get_setting('site_logo') ?? 'default-logo.png';
?>

<header>
    <div class="logo">
        <a href="/index" class="<?= ($currentPage === 'index') ? 'active' : '' ?>">
            <img src="/uploads/<?= htmlspecialchars($site_logo) ?>" 
                 alt="<?= htmlspecialchars($site_name) ?>" 
                 width="400" height="70">
        </a>
    </div>

    <nav>
        <!-- Static pages -->
        <a href="/index" class="<?= ($currentPage === 'index') ? 'active' : '' ?>">Home</a>
        <a href="/about" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="/services" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic CMS pages -->
        <?php foreach ($recentPages as $page): ?>
            <a href="/<?= htmlspecialchars($page['slug']) ?>" 
               class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
               <?= htmlspecialchars($page['title']) ?>
            </a>
        <?php endforeach; ?>

        <a href="/contact" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
