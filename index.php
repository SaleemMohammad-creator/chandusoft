<?php
require __DIR__ . '/app/config.php'; // DB connection
 
// -----------------------
// Fetch pages for navbar
// -----------------------
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published'");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);
 
// -----------------------
// Determine which page to show
// -----------------------
$pageSlug = $_GET['page'] ?? 'home';
 
if ($pageSlug === 'home') {
    $page = null; // Home page doesn't need DB content
    include __DIR__ . '/views/home.php';
} else {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$pageSlug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($page) {
        include __DIR__ . '/views/page.php';
    } else {
        include __DIR__ . '/views/404.php';
    }
}