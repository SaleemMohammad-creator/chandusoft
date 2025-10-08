<?php
require __DIR__ . '/app/config.php'; // PDO DB connection

// -----------------------
// Fetch all published pages for navbar
// -----------------------
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status='published' ORDER BY created_at ASC");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------
// Determine requested page
// -----------------------
$pageSlug = $_GET['page'] ?? 'home';

if ($pageSlug === 'home') {
    $page = null; // Homepage content
    $viewFile = __DIR__ . '/views/home.php';
} else {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug AND status='published' LIMIT 1");
    $stmt->execute([':slug' => $pageSlug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($page) {
        $viewFile = __DIR__ . '/views/page.php';
    } else {
        http_response_code(404);
        $viewFile = __DIR__ . '/views/404.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($page['title'] ?? 'Chandusoft') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Inline CSS (merged styles) -->
<style>
/* Reset & Global */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, Helvetica, sans-serif; line-height:1.6; background:#f4f4f4; color:#333; }

/* Header */
header { display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#2c3e50; color:#fff; position:sticky; top:0; z-index:1000; }
header .logo img { display:block; max-width:50%; height:auto; }
nav a { text-decoration:none; margin-left:10px; }
nav button { padding:8px 15px; background:#3498db; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; transition:0.3s; }
nav button:hover { background:#1d6fa5; }

/* Hero Section */
.hero { padding:80px 20px; text-align:center; background:#e9f0ff; }
.hero h1 { font-size:2.5rem; margin-bottom:20px; color:#2a69f0; }
.hero p { font-size:1.2rem; margin-bottom:25px; }
.btn-hero { padding:10px 20px; background:#3498db; color:#fff; border:none; border-radius:4px; font-weight:bold; text-decoration:none; transition:0.3s; }
.btn-hero:hover { background:#1d6fa5; }

/* Testimonials */
.testimonials { padding:50px 20px; text-align:center; }
.testimonials h2 { margin-bottom:40px; font-size:2rem; color:#2a69f0; }
.testimonial-container { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; }
.testimonial { background:#fff; padding:20px 30px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); max-width:300px; flex:1; }
.testimonial p { font-style:italic; margin-bottom:15px; }
.testimonial h4 { font-weight:bold; margin-bottom:5px; }
.testimonial span { font-size:0.9rem; color:#777; }

/* Page Content */
.page-content { max-width:900px; margin:40px auto; padding:0 20px; }

/* Footer */
footer { background:#2c3e50; color:#fff; padding:20px 0; text-align:center; margin-top:40px; }
footer .footer-links a { color:#fff; margin:0 5px; text-decoration:none; }
footer .social-icons a { color:#fff; margin:0 5px; text-decoration:none; font-size:1.1rem; }

/* Back to Top */
#back-to-top { display:none; position:fixed; bottom:30px; right:30px; z-index:1000; padding:10px 15px; background:#3498db; color:#fff; border:none; border-radius:50%; cursor:pointer; font-size:18px; }

/* Responsive */
@media(max-width:768px) {
    nav { display:flex; flex-wrap:wrap; gap:5px; }
    .testimonial-container { flex-direction:column; align-items:center; }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Header -->
<header>
    <div class="logo">
        <a href="index.php"><img src="admin/images/logo.jpg" alt="Chandusoft"></a>
    </div>
    <nav>
        <a href="index.php"><button>Home</button></a>
        <?php foreach($navPages as $nav): ?>
            <a href="index.php?page=<?= htmlspecialchars($nav['slug']) ?>"><button><?= htmlspecialchars($nav['title']) ?></button></a>
        <?php endforeach; ?>
        <a href="index.php?page=contact"><button>Contact</button></a>
    </nav>
</header>

<!-- Main Content -->
<main>
    <?php include $viewFile; ?>
</main>

<!-- Footer -->
<footer>
    <p>©2025 Chandusoft. Designed by <b>Chandusoft Pvt Ltd</b></p>
    <div class="footer-links">
        <a href="index.php">Home</a>
        <?php foreach($navPages as $nav): ?>
            <a href="index.php?page=<?= htmlspecialchars($nav['slug']) ?>"><?= htmlspecialchars($nav['title']) ?></a>
        <?php endforeach; ?>
        <a href="index.php?page=contact">Contact</a>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/YourPage" target="_blank"><i class="fab fa-facebook-f"></i></a>
        <a href="https://twitter.com/YourPage" target="_blank"><i class="fab fa-twitter"></i></a>
        <a href="https://www.linkedin.com/company/YourPage" target="_blank"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://www.instagram.com/YourPage" target="_blank"><i class="fab fa-instagram"></i></a>
    </div>
</footer>

<!-- Back to Top -->
<button id="back-to-top" title="Back to Top">↑</button>

<script>
const btn = document.getElementById('back-to-top');
window.onscroll = () => { btn.style.display = window.scrollY > 300 ? 'block' : 'none'; };
btn.onclick = () => window.scrollTo({ top:0, behavior:'smooth' });
</script>
</body>
</html>
