<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Handle restore action
if (isset($_GET['restore_id'])) {
    $restore_id = intval($_GET['restore_id']);
    $stmt = $pdo->prepare("UPDATE catalog SET status='published' WHERE id=?");
    $stmt->execute([$restore_id]);
    header("Location: catalog-delete.php");
    exit;
}

// Pagination
$limit = 10;
$page_no = isset($_GET['page_no']) ? intval($_GET['page_no']) : 1;
$offset = ($page_no - 1) * $limit;

// Search
$search = trim($_GET['search'] ?? '');
$where = "WHERE status='archived'";
$params = [];
if($search){
    $where .= " AND (title LIKE :search OR short_desc LIKE :search)";
    $params[':search'] = "%$search%";
}

// Count total archived items
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog $where");
foreach($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$total_records = $countStmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch archived items
$stmt = $pdo->prepare("SELECT * FROM catalog $where ORDER BY created_at DESC LIMIT :offset, :limit");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archived Catalog Items</title>
<style>
body {font-family:Arial,sans-serif; background:#f9f9f9; margin:0; padding:20px; color:#333;}
h2{text-align:center; color:#007BFF; margin-bottom:20px;}
table{border-collapse:collapse; width:100%; max-width:1200px; margin:0 auto; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
th, td{border:1px solid #ccc; padding:10px; text-align:left;}
th{background:#007BFF; color:#fff;}
tr:nth-child(even){background:#f5f5f5;}
tr:hover{background:#e3f0ff;}
img{max-width:80px;}
a{color:#007BFF; text-decoration:none; margin-right:8px;}
a:hover{text-decoration:underline;}
.search-container{display:flex; justify-content:center; margin-bottom:15px; gap:5px; flex-wrap:wrap;}
.search-container input[type="text"]{padding:8px; border-radius:5px; border:1px solid #ccc; width:250px;}
.search-container button{padding:8px 16px; border-radius:5px; border:none; background:#007BFF; color:#fff; font-weight:bold; cursor:pointer;}
.search-container button:hover{background:#0056b3;}
.pagination{text-align:center; margin-top:15px;}
.pagination a{padding:6px 12px; border:1px solid #007BFF; margin:0 3px; border-radius:5px; text-decoration:none; color:#007BFF;}
.pagination a.active{background:#007BFF; color:#fff;}
.status-archived{color:red; font-weight:bold;}
</style>
</head>
<body>

<h2>Archived Catalog Items</h2>

<!-- Search -->
<form method="get" class="search-container">
    <input type="text" name="search" placeholder="Search archived items..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<!-- Table -->
<table>
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Slug</th>
    <th>Image</th>
    <th>Price</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php if($items): ?>
    <?php foreach($items as $item): ?>
    <tr>
        <td><?= $item['id'] ?></td>
        <td><?= htmlspecialchars($item['title']) ?></td>
        <td><?= htmlspecialchars($item['slug']) ?></td>
        <td><?php if($item['image']): ?><img src="/uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"><?php endif; ?></td>
        <td><?= $item['price'] ?></td>
        <td class="status-archived"><?= $item['status'] ?></td>
        <td>
            <a href="?restore_id=<?= $item['id'] ?>" onclick="return confirm('Restore this item to catalog?')">Restore</a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="7" style="text-align:center;">No archived items found.</td></tr>
<?php endif; ?>
</table>

<!-- Pagination -->
<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++): ?>
    <a href="?page_no=<?= $i ?>&search=<?= htmlspecialchars($search) ?>" class="<?= $page_no==$i?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</body>
</html>