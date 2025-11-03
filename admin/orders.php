<?php
// =====================================
// Admin Orders Management (with Pagination)
// =====================================
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// -------------------------------------
// Secure Session & Role Validation
// -------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => false,
        'cookie_samesite' => 'Strict'
    ]);
}

if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    strtolower($_SESSION['user_role']) !== 'admin'
) {
    header("Location: /admin/login.php");
    exit();
}

// ====================================
// Pagination Variables
// ====================================
$limit = 10; // number of orders per page
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ====================================
// Search Logic + Count
// ====================================
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_email LIKE ? OR order_ref LIKE ?");
    $like = "%$search%";
    $countStmt->execute([$like, $like]);
    $total_orders = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM orders 
                           WHERE customer_email LIKE ? OR order_ref LIKE ?
                           ORDER BY created_at DESC
                           LIMIT $limit OFFSET $offset");
    $stmt->execute([$like, $like]);
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_orders = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM orders 
                           ORDER BY created_at DESC 
                           LIMIT $limit OFFSET $offset");
    $stmt->execute();
}

$orders = $stmt->fetchAll();
$total_pages = ceil($total_orders / $limit);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Orders</title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f9fafb;
  margin: 0;
  padding: 30px;
}
h1 {
  text-align: center;
  color: #1e3a8a;
  margin-bottom: 25px;
}
.search-box {
  text-align: center;
  margin-bottom: 20px;
}
.search-box input[type="text"] {
  width: 280px;
  padding: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 1em;
}
.search-box button {
  padding: 10px 16px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 1em;
  cursor: pointer;
  margin-left: 8px;
}
.search-box button:hover {
  background: #1e40af;
}
table {
  border-collapse: collapse;
  width: 100%;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
thead {
  background: #1e3a8a;
  color: #fff;
}
th, td {
  padding: 12px 16px;
  text-align: left;
}
tr:nth-child(even) { background: #f1f5f9; }
a {
  color: #2563eb;
  text-decoration: none;
  font-weight: 500;
}
a:hover { text-decoration: underline; }
td:last-child a {
  background: #2563eb;
  color: #fff;
  padding: 6px 10px;
  border-radius: 6px;
  text-decoration: none;
}
td:last-child a:hover {
  background: #1e40af;
}
.empty {
  text-align: center;
  color: #64748b;
  padding: 20px;
}
.pagination {
  margin-top: 25px;
  text-align: center;
}
.pagination a {
  display: inline-block;
  padding: 8px 14px;
  margin: 0 4px;
  background: #e2e8f0;
  color: #1e3a8a;
  border-radius: 6px;
  text-decoration: none;
}
.pagination a.active {
  background: #2563eb;
  color: #fff;
  font-weight: bold;
}
.pagination a:hover {
  background: #1e40af;
  color: #fff;
}
.gateway {
  text-transform: capitalize;
  font-weight: 600;
}
</style>
</head>
<body>
<h1>Admin Orders</h1>

<div class="search-box">
  <form method="get" action="">
    <input type="text" name="search" placeholder="Search by email or order ref..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Order Ref</th>
      <th>Customer</th>
      <th>Email</th>
      <th>Total</th>
      <th>Gateway</th>
      <th>Status</th>
      <th>Date</th>
      <th>View</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($orders)): ?>
      <tr><td colspan="9" class="empty">No orders found</td></tr>
    <?php else: ?>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['order_ref']) ?></td>
          <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($o['customer_email'] ?? '-') ?></td>
          <td>$<?= number_format($o['total'], 2) ?></td>
          <td class="gateway"><?= htmlspecialchars($o['payment_gateway'] ?? '-') ?></td>
          <td><?= htmlspecialchars($o['payment_status'] ?? '-') ?></td>
          <td><?= htmlspecialchars($o['created_at'] ?? '-') ?></td>
          <td><a href="order_view.php?id=<?= $o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($total_pages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">« Prev</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next »</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
