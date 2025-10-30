<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
// Include admin header
include __DIR__ . '/../admin/header.php';

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// Helper for clean URLs (fixed to include /public/)
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = ltrim($path, '/');
    return $protocol . $host . '/public/' . $path; // <-- Added /public/ to match folder
}

// Pagination
$limit = 12;
$page_no = max(1, intval($_GET['page_no'] ?? 1));
$offset = ($page_no - 1) * $limit;

// Search
$search = trim($_GET['search'] ?? '');
$params = [];
$where = "WHERE status='published'";

if ($search !== '') {
    // Escape special characters for LIKE query
    $search_escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
    $where .= " AND (title LIKE ? ESCAPE '\\\\' OR short_desc LIKE ? ESCAPE '\\\\')";
    $params[] = "%$search_escaped%";
    $params[] = "%$search_escaped%";
}

// Count total items
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog $where");
$countStmt->execute($params);
$total_records = $countStmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $limit));

// Fetch items
$sql = "SELECT * FROM catalog $where ORDER BY created_at DESC LIMIT $offset, $limit";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Log view action
logCatalogAction("Catalog listing viewed. Search: '$search', Page: $page_no");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catalog</title>
<style>
body { font-family: Arial; margin:0; background:#f7f8fc; }

/* Header Styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #007BFF;
    padding: 5px 10px;
}

.logo img {
    width: 400px;
    height: 70px;
}

nav {
    display: flex;
    justify-content: center;
    gap: 15px;
    background-color: #007BFF;
    padding: 1px 0;
}

nav a, nav button {
    padding: 10px 18px;
    margin: 5px;
    background-color: #007BFF;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    border: 1px solid #007BFF;
    transition: all 0.3s ease;
    cursor: pointer;
}

nav a.active, nav button.active {
    background-color: #fff; /* White background */
    color: #007BFF;        /* Blue text */
    border-color: #fff;    /* Optional */
}


nav a:hover, nav button:hover {
    background-color: rgb(239, 245, 245);
    color: #007BFF;
}

/* ✅ Prevent overlap by pushing content down */
.container {
    max-width:1000px;
    margin:100px auto 40px auto; /* Keep your original spacing */
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px #0001;
    padding:30px 28px;
}
h2 { text-align: center; color: #007BFF; margin-bottom: 20px; }
.search-bar { text-align: center; margin-bottom: 20px; }
.search-bar input { padding: 8px 12px; width: 250px; border-radius: 5px; border: 1px solid #ccc; }
.search-bar button { padding: 8px 14px; border: none; border-radius: 5px; background: #007BFF; color: #fff; cursor: pointer; }
.catalog { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
.card { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; }
.card picture, .card img { width: 100%; height: 200px; object-fit: cover; border-radius: 6px; display: block; margin-bottom: 10px; }
.card h3 { margin: 5px 0; color: #007BFF; }
.card p { font-size: 14px; color: #333; margin-bottom: 10px; }
.card a { text-decoration: none; color: #fff; background: #007BFF; padding: 6px 12px; border-radius: 4px; display: inline-block; transition: 0.3s; text-align: center; }
.card a:hover { background: #0056b3; }
.pagination { text-align: center; margin-top: 20px; }
.pagination a { padding: 6px 12px; border: 1px solid #007BFF; margin: 0 3px; border-radius: 5px; text-decoration: none; color: #007BFF; }
.pagination a.active { background: #007BFF; color: #fff; }

</style>
</head>
<body>

   


<h2>Catalog</h2>

<div class="search-bar">
<form method="get">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>
</div>

<div class="catalog">
<?php if ($items): ?>
    <?php foreach ($items as $item):
        $originalImage = '/uploads/' . htmlspecialchars($item['image']);
        $webpImage = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalImage);
        $webpExists = file_exists(__DIR__ . '/../' . ltrim($webpImage,'/'));
    ?>
    <div class="card">
        <?php if ($item['image']): ?>
        <picture>
            <?php if ($webpExists): ?>
            <source srcset="<?= $webpImage ?>" type="image/webp">
            <?php endif; ?>
            <img src="<?= $originalImage ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
        </picture>
        <?php endif; ?>
        <h3><?= htmlspecialchars($item['title']) ?></h3>
        <p>Price: $<?= number_format($item['price'], 2) ?></p>
        <a href="<?= base_url('catalog-item/' . urlencode($item['slug'])) ?>">View Details</a>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center; grid-column: 1 / -1;">No items found.</p>
<?php endif; ?>
</div>

<div class="pagination">
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="<?= base_url('catalog.php?page_no=' . $i . '&search=' . urlencode($search)) ?>" class="<?= $page_no == $i ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>

  
</div>

</body>
</html>
