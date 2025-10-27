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
        $stmt = $pdo->query("SELECT title, slug FROM pages WHERE status='published' ORDER BY id ASC");
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
        <!-- Static pages -->
        <?php foreach ($staticPages as $slug => $title): ?>
            <a href="/<?= $slug ?>" class="<?= ($currentPage === $slug) ? 'active' : '' ?>">
                <?= htmlspecialchars($title) ?>
            </a>
        <?php endforeach; ?>

        <!-- Dynamic CMS pages (exclude duplicates of static pages) -->
        <?php foreach ($recentPages as $page): ?>
            <?php
                $slug = htmlspecialchars($page['slug']);
                $title = htmlspecialchars($page['title']);
            ?>
            <?php if (!array_key_exists($slug, $staticPages)): ?>
                <a href="/<?= $slug ?>" class="<?= ($currentPage === $slug) ? 'active' : '' ?>">
                    <?= $title ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</header>
