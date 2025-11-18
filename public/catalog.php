<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Include admin header (⚠ must not output ANYTHING before DOCTYPE)
include __DIR__ . '/../admin/header.php';

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

// ✅ Helper for clean URLs (kept original logic)
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = ltrim($path, '/');
    return $protocol . $host . '/public/' . $path;
}

// Pagination
$limit = 15;
$page_no = max(1, intval($_GET['page_no'] ?? 1));
$offset = ($page_no - 1) * $limit;

// ✅ Null-safe Search input
$search = trim((string)($_GET['search'] ?? ''));
$params = [];
$where = "WHERE status='published'";

if ($search !== '') {
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

logCatalogAction("Catalog listing viewed. Search: '$search', Page: $page_no");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<title>Catalog</title>

<style>
/* =========================================================
   BASIC PAGE UI
   ========================================================= */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f7f8fc;
}

/* =========================================================
   PAGE CONTAINER
   ========================================================= */
.container {
    max-width: 1000px;
    margin: 100px auto 40px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px #0001;
    padding: 30px 28px;
}

h2 {
    text-align: center;
    color: #007BFF;
    margin-bottom: 20px;
}

/* =========================================================
   SEARCH BAR
   ========================================================= */
.search-bar {
    text-align: center;
    margin-bottom: 20px;
    margin-top: 10px; /* ← add this */
}

.search-bar input {
    padding: 8px 12px;
    width: 250px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

.search-bar button {
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    background: #007BFF;
    color: #fff;
    cursor: pointer;
}

/* =========================================================
   CATALOG GRID
   ========================================================= */
.catalog {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    max-width: 1200px;
    margin: 0 auto;
}

/* =========================================================
   PRODUCT CARD (Modern, Rounded, No Border Breaking)
   ========================================================= */
.card {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    padding: 14px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden; /* FIX: no border breaking */
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}

/* =========================================================
   CARD IMAGE
   ========================================================= */
/* Image wrapper fixes */
.card picture,
.card img {
    width: 100%;
    height: 140px;
    object-fit: contain;       /* keeps image inside box */
    background: #ffffff;       /* clean background */
    padding: 14px 10px;        /* 🔥 FIX: more padding top/bottom */
    border-radius: 8px;
    display: block;
    border: 1px solid #eee;    /* optional inner border */
    box-sizing: border-box;    /* ensures padding stays inside */
}


/* =========================================================
   CARD TITLE
   ========================================================= */
.card h3 {
    font-size: 15px;
    color: #0056d6;
    margin: 10px 0 4px 0;
    font-weight: 600;
    height: 34px;
    overflow: hidden;
}

/* =========================================================
   CARD PRICE
   ========================================================= */
.card p {
    font-size: 14px;
    color: #333;
    font-weight: bold;
    margin: 0 0 8px 0;
}

/* =========================================================
   QUANTITY SELECTOR (Modern)
   ========================================================= */
.qty-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: auto;
    margin-bottom: 8px;
}

.qty-selector button {
    width: 28px;
    height: 28px;
    background: #007bff;
    border: none;
    color: #fff;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
}

.qty-selector input {
    width: 45px;
    border-radius: 6px;
    border: 1px solid #ccc;
    text-align: center;
    font-size: 14px;
    padding: 5px 0;
}

/* =========================================================
   BUTTONS (Add to Cart / Buy Now)
   ========================================================= */
.card-buttons {
    display: flex;
    gap: 8px;
    margin-top: 5px;
}

.card-buttons a {
    flex: 1;
    text-align: center;
    padding: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.card-buttons .add {
    background: #007bff;
    color: #fff;
}

.card-buttons .buy {
    background: #28a745;
    color: #fff;
}

.card-buttons .add:hover {
    background: #005ec4;
}

.card-buttons .buy:hover {
    background: #1f8c39;
}

/* =========================================================
   PAGINATION
   ========================================================= */
.pagination {
    text-align: center;
    margin-top: 20px;
}

.pagination a {
    padding: 6px 12px;
    border: 1px solid #007BFF;
    margin: 0 3px;
    border-radius: 4px;
    text-decoration: none;
    color: #007BFF;
}

.pagination a.active {
    background: #007BFF;
    color: #fff;
}

/* =========================================================
   MOBILE FIXES
   ========================================================= */
@media (max-width:480px) {
    .card-buttons { flex-direction: column; }
}


</style>


</head>
<body>

<h2>Catalog</h2>

<div class="search-bar">
<form method="get">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search ?? '') ?>">
    <button type="submit">Search</button>
</form>
</div>

<div class="catalog">
<?php if ($items): ?>
    <?php foreach ($items as $item):

        $originalImage = '/uploads/' . htmlspecialchars($item['image'] ?? '');
        $webpImage = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalImage);
        $webpExists = file_exists(__DIR__ . '/../' . ltrim($webpImage, '/'));
    ?>
    <div class="card">
        <a href="<?= base_url('catalog-item/' . urlencode($item['slug'] ?? '')) ?>" style="display:block;">
            <picture>
                <?php if ($webpExists): ?>
                    <source srcset="<?= $webpImage ?>" type="image/webp">
                <?php endif; ?>
                <img src="<?= $originalImage ?>" alt="<?= htmlspecialchars($item['title'] ?? '') ?>" loading="lazy">
            </picture>
        </a>

        <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
        <p>Price: $<?= number_format($item['price'], 2) ?></p>

        <!-- ✅ Quantity Selector (added id + name only change applied) -->
        <div class="qty-selector">
            <button type="button" class="decrease">−</button>
            <input type="number"
                   id="qty_<?= htmlspecialchars($item['slug'] ?? '') ?>"
                   name="quantity"
                   value="1"
                   min="1"
                   class="qty-input"
                   data-slug="<?= htmlspecialchars($item['slug'] ?? '') ?>">
            <button type="button" class="increase">+</button>
        </div>

        <div class="card-buttons">
            <a href="#" class="add" data-slug="<?= htmlspecialchars($item['slug'] ?? '') ?>">Add to Cart</a>
            <a href="#" class="buy" data-slug="<?= htmlspecialchars($item['slug'] ?? '') ?>">Buy Now</a>
        </div>
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

<script>
// Handle + / - buttons
document.querySelectorAll('.card').forEach(card => {
    const input = card.querySelector('.qty-input');
    card.querySelector('.increase').addEventListener('click', () => {
        input.value = parseInt(input.value) + 1;
    });
    card.querySelector('.decrease').addEventListener('click', () => {
        if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
    });
});

// Handle Add to Cart with quantity
document.querySelectorAll('.add').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        const slug = btn.dataset.slug;
        const qty = btn.closest('.card').querySelector('.qty-input').value;
        window.location.href = `<?= base_url('cart.php?action=add') ?>&slug=${encodeURIComponent(slug)}&qty=${qty}`;
    });
});

// Handle Buy Now with quantity
document.querySelectorAll('.buy').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        const slug = btn.dataset.slug;
        const qty = btn.closest('.card').querySelector('.qty-input').value;
        window.location.href = `<?= base_url('checkout.php?action=buy') ?>&slug=${encodeURIComponent(slug)}&qty=${qty}`;
    });
});
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>

<button id="back-to-top" title="Back to Top">↑</button>
    <script src="/include.js"></script>

</body>
</html>
