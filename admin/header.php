<?php
// Ensure $recentPages is defined
$recentPages = $recentPages ?? [];
$currentPage = $currentPage ?? 'index';
?>

<header>
    <div class="logo">
        <a href="/index">
            <img src="/admin/images/logo.jpg" alt="Chandusoft Technologies" width="400" height="70">
        </a>
    </div>

    <nav>
        <!-- Static links -->
        <a href="/index" class="<?= ($currentPage === 'index') ? 'active' : '' ?>">Home</a>
        <a href="/about" class="<?= ($currentPage === 'about') ? 'active' : '' ?>">About</a>
        <a href="/services" class="<?= ($currentPage === 'services') ? 'active' : '' ?>">Services</a>
        <a href="/contact" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>

        <!-- Dynamic CMS pages (if any) -->
        <?php foreach ($recentPages as $page): ?>
            <a href="/<?= htmlspecialchars($page['slug']) ?>"
               class="<?= ($currentPage === $page['slug']) ? 'active' : '' ?>">
               <?= htmlspecialchars($page['title']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
