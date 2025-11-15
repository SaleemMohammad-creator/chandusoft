<?php
// =====================================
// Admin Orders Management (with Pagination + Search)
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

 // 👉 ADD THIS LINE HERE
$currentPage = basename($_SERVER['PHP_SELF']);

if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    strtolower($_SESSION['user_role']) !== 'admin'
) {
    header("Location: /admin/login.php");
    exit();
}

// ✅ FIX: Define user role for navbar usage
$user_role = strtolower($_SESSION['user_role'] ?? '');

// ====================================
// Pagination Variables
// ====================================
$limit = 10; // number of orders per page
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ====================================
// Search Logic + Count
// ====================================
$search = trim($_GET['search'] ?? '');

$orders = [];
$total_orders = 0;

// If only symbols → empty result
if ($search !== '' && !preg_match('/[A-Za-z0-9]/', $search)) {
    $total_pages = 1;
} else {
    if ($search !== '') {

        // Escape LIKE wildcards
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
        $like = "%$escaped%";

        $countStmt = $pdo->prepare('
            SELECT COUNT(*) FROM orders 
            WHERE customer_email LIKE :email ESCAPE "\\\\" 
               OR order_ref LIKE :ref ESCAPE "\\\\"
        ');
        $countStmt->execute([':email' => $like, ':ref' => $like]);
        $total_orders = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare('
            SELECT * FROM orders 
            WHERE customer_email LIKE :email ESCAPE "\\\\" 
               OR order_ref LIKE :ref ESCAPE "\\\\"
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':email', $like);
        $stmt->bindValue(':ref', $like);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

    } else {
        $total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
    }

    $orders = $stmt->fetchAll();
}

$total_pages = max(1, ceil($total_orders / $limit));
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Orders</title>

<style>
 /* ===========================
   Global Styles
=========================== */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f7f8fc;
}

/* ===========================
   Navbar
=========================== */
.navbar {
    background: #2c3e50;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar a.active {
    background: #4da6ff;
    padding: 6px 12px;
    border-radius: 4px;
}

.navbar-left {
    font-size: 22px;
    font-weight: bold;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 12px;
    font-weight: bold;
}

.navbar a {
    color: #fff;
    text-decoration: none;
    margin-left: 12px;
    font-weight: bold;
}

.nav-btn {
    padding: 6px 12px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.nav-btn:hover {
    background: #1C86EE;
}

/* ===============================
   ORIGINAL STYLES
=============================== */
h1 {
    text-align: center;
    color: #1e3a8a;
    margin-bottom: 25px;
}

/* -------------------------------
   Search Box
------------------------------- */
.search-box {
    text-align: center;
    margin-bottom: 20px;
}

.search-box input[type="text"] {
    width: 280px;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}

.search-box button {
    padding: 10px 16px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 6px;
}

/* -------------------------------
   Table
------------------------------- */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

thead {
    background: #1e3a8a;
    color: #fff;
}

th,
td {
    padding: 12px 16px;
    text-align: left;
}

tr:nth-child(even) {
    background: #f1f5f9;
}

.empty {
    padding: 20px;
    text-align: center;
    color: #64748b;
}

/* -------------------------------
   Pagination
------------------------------- */
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
    font-weight: 500;
}

.pagination a.active {
    background: #2563eb;
    color: #fff;
}

/* -------------------------------
   Status Badges
------------------------------- */
.status {
    padding: 6px 10px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

.status.paid {
    background: #dcfce7;
    color: #166534;
}

.status.pending {
    background: #fef9c3;
    color: #92400e;
}

.status.failed,
.status.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.status.refunded {
    background: #e0f2fe;
    color: #075985;
}

/* -------------------------------
   View Button
------------------------------- */
.view-btn {
    display: inline-block;
    padding: 6px 12px;
    background: #2563eb;
    color: #fff !important;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.view-btn:hover {
    background: #1e40af;
}

</style>

</head>
<body>

<!-- ===============================
     NAVBAR HTML
     =============================== -->
<div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
        <a href="/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

  <?php if ($user_role === 'admin'): ?>
      <a href="/admin/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Admin Catalog</a>
      <a href="/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
   <?php endif; ?>

     <a href="/public/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Public Catalog</a>

    <a href="/admin/pages.php" class="<?= $currentPage === 'pages.php' ? 'active' : '' ?>">Pages</a>

    <a href="/admin/admin-leads.php" class="<?= $currentPage === 'admin-leads.php' ? 'active' : '' ?>">Leads</a>

    <a href="/admin/logout.php">Logout</a>

 </div>
</div>


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
  <th>Transaction ID</th>
  <th>Date</th>
  <th>View</th>
</tr>
</thead>

<tbody>
<?php if (empty($orders)): ?>
    <tr><td colspan="10" class="empty">No orders found</td></tr>
<?php else: ?>
    <?php foreach ($orders as $o): ?>
        <?php $status = strtolower(trim($o['payment_status'] ?? '-')); ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['order_ref']) ?></td>
          <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($o['customer_email'] ?? '-') ?></td>
          <td>$<?= number_format($o['total'], 2) ?></td>
          <td><?= htmlspecialchars($o['payment_gateway'] ?? '-') ?></td>
          <td><span class="status <?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status ?: '-') ?></span></td>
          <td><?= htmlspecialchars($o['transaction_id'] ?? '-') ?></td>
          <td><?= htmlspecialchars($o['created_at'] ?? '-') ?></td>
          <td><a class="view-btn" href="order_view.php?id=<?= $o['id'] ?>">View</a></td>
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
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next »</a>
  <?php endif; ?>

</div>
<?php endif; ?>

</body>
</html>
