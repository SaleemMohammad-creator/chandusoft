<?php
// Ensure $page exists
if (!isset($page) || empty($page)) {
    header("HTTP/1.0 404 Not Found");
    include __DIR__ . '/404.php';
    exit;
}
 
ob_start();
?>
 
<h1><?= htmlspecialchars($page['title'] ?? 'Untitled Page') ?></h1>
 
<div>
    <?= $page['content_html'] ?? '<p>No content available.</p>' ?>
</div>
 
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>