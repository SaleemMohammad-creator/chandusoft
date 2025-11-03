<?php
// admin/order_view.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// ✅ Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Admin check
if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    strtolower($_SESSION['user_role']) !== 'admin'
) {
    http_response_code(403);
    exit('Access denied');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid order ID');

// ✅ Fetch order (including gateway)
$stmt = $pdo->prepare("SELECT id, order_ref, customer_name, customer_email, total, payment_gateway, payment_status, created_at 
                       FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) die('Order not found');

// ✅ Fetch order items
$items = $pdo->prepare("SELECT product_name, quantity, unit_price, total_price FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// ✅ CSRF fallback
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }
}
$csrf = csrf_token();
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice #<?= htmlspecialchars($order['order_ref']) ?></title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f3f4f6;
  margin: 0;
  padding: 40px;
  color: #111827;
}
.invoice-container {
  max-width: 900px;
  background: #fff;
  margin: auto;
  border-radius: 10px;
  box-shadow: 0 3px 12px rgba(0,0,0,0.1);
  padding: 40px 50px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1e3a8a;
  padding-bottom: 10px;
  margin-bottom: 25px;
}
.header h1 {
  color: #1e3a8a;
  font-size: 1.8em;
  margin: 0;
}
.header small {
  color: #6b7280;
  font-size: 0.9em;
}
.section {
  margin-bottom: 25px;
}
.section h3 {
  color: #1e3a8a;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 6px;
  margin-bottom: 10px;
}
.details p {
  margin: 6px 0;
  font-size: 1.02em;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
thead {
  background: #1e3a8a;
  color: white;
}
th, td {
  padding: 12px;
  text-align: left;
}
tr:nth-child(even) {
  background: #f9fafb;
}
.total-box {
  text-align: right;
  margin-top: 20px;
}
.total-box strong {
  font-size: 1.2em;
  color: #111827;
}
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: 600;
  text-transform: capitalize;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-paid { background: #dcfce7; color: #166534; }
.status-failed { background: #fee2e2; color: #991b1b; }
.status-refunded { background: #e0f2fe; color: #075985; }
.status-cancelled { background: #f3f4f6; color: #374151; }
.gateway {
  text-transform: capitalize;
  font-weight: 600;
  color: #1e3a8a;
}
.footer {
  text-align: center;
  margin-top: 40px;
  color: #6b7280;
  font-size: 0.9em;
}
.action-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 25px;
}
.back-link, .print-btn {
  display: inline-block;
  color: #2563eb;
  text-decoration: none;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: none;
  font-size: 1em;
}
.back-link:hover, .print-btn:hover { text-decoration: underline; }

/* 🖨️ Print Styles */
@media print {
  body {
    background: #fff;
    padding: 0;
  }
  .invoice-container {
    box-shadow: none;
    border: none;
    padding: 20px;
  }
  .action-buttons {
    display: none;
  }
}
</style>
</head>
<body>

<div class="invoice-container">
  <div class="header">
    <div style="display: flex; align-items: center; gap: 15px;">
      <div>
        <h1>Chandusoft Pvt. Ltd.</h1>
        
      </div>
    </div>
    <small>Date: <?= date('d M Y', strtotime($order['created_at'])) ?></small>
  </div>

  <div class="section">
    <h3>Order Summary</h3>
    <div class="details">
      <p><strong>Order Ref:</strong> <?= htmlspecialchars($order['order_ref']) ?></p>
      <p><strong>Payment Gateway:</strong> <span class="gateway"><?= htmlspecialchars($order['payment_gateway']) ?></span></p>
      <p><strong>Payment Status:</strong> 
        <span class="status-badge status-<?= htmlspecialchars(strtolower($order['payment_status'])) ?>">
          <?= htmlspecialchars($order['payment_status']) ?>
        </span>
      </p>
      <p><strong>Total Amount:</strong> $<?= number_format($order['total'], 2) ?></p>
    </div>
  </div>

  <div class="section">
    <h3>Customer Details</h3>
    <div class="details">
      <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
    </div>
  </div>

  <div class="section">
    <h3>Items</h3>
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Qty</th>
          <th>Unit Price</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td>$<?= number_format($it['unit_price'], 2) ?></td>
            <td>$<?= number_format($it['total_price'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="total-box">
      <p><strong>Grand Total: $<?= number_format($order['total'], 2) ?></strong></p>
    </div>
  </div>

  <div class="action-buttons">
    <button onclick="window.print()" class="print-btn">🖨️ Print Invoice</button>
    <a href="orders.php" class="back-link">← Back to Orders</a>
  </div>

  <div class="footer">
    <p>Thank you for your shopping!</p>
  </div>
</div>

</body>
</html>
