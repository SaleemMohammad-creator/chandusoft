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

// Fetch dynamic CMS pages if $recentPages is empty
if (empty($recentPages)) {
    try {
        $stmt = $pdo->query("SELECT title, slug FROM pages WHERE status='published' ORDER BY title ASC");
        $recentPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $recentPages = [];
    }
}

// Define static pages for navigation
$staticPages = [
    'index'    => 'Home',
    'about'    => 'About',
    'services' => 'Services',
    'contact'  => 'Contact'
];
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
        <!-- Static pages BEFORE CMS -->
        <?php foreach (['index', 'about', 'services'] as $slug): ?>
            <a href="/<?= $slug ?>" class="<?= ($currentPage === $slug) ? 'active' : '' ?>">
                <?= htmlspecialchars($staticPages[$slug]) ?>
            </a>
        <?php endforeach; ?>

        <!-- Dynamic CMS pages -->
        <?php foreach ($recentPages as $page): ?>
            <?php
                $slug = htmlspecialchars($page['slug']);
                $title = htmlspecialchars($page['title']);
            ?>
            <?php if (!in_array($slug, ['index', 'about', 'services', 'contact'])): ?>
                <a href="/<?= $slug ?>" class="<?= ($currentPage === $slug) ? 'active' : '' ?>">
                    <?= $title ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Static page AFTER CMS -->
        <a href="/contact" class="<?= ($currentPage === 'contact') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
