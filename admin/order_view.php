<?php
// admin/order_view.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// ✅ Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

 $csrf = $_SESSION['csrf_token'];  // ← use config.php token

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

// ======================
//  CANCEL ORDER SETTINGS
// ======================

// Time after which admin can cancel (0 = immediately)
$cancel_wait_minutes = 0;

// Convert created time
$order_time = strtotime($order['created_at']);
$now = time();
$diff_minutes = ($now - $order_time) / 60;

// Allow cancel only if status is pending (no wait time)
$can_cancel = (strtolower($order['payment_status']) === 'pending');

?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice #<?= htmlspecialchars($order['order_ref']) ?></title>
<style>
/* ===========================
   Global Styles
=========================== */
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f3f4f6;
  margin: 0;
  padding: 40px;
  color: #111827;
}

/* ===========================
   Invoice Container
=========================== */
.invoice-container {
  max-width: 900px;
  background: #fff;
  margin: auto;
  border-radius: 10px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
  padding: 40px 50px;
}

/* ===========================
   Header
=========================== */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1e3a8a;
  padding-bottom: 12px;
  margin-bottom: 25px;
}

/* Logo + Company Title */
.logo-title {
  display: flex;
  align-items: center;
  gap: 15px;
}

.logo-title img {
  width: 150px;
  height: auto;
  border-radius: 6px;
}

.logo-title h1 {
  margin: 0;
  font-size: 1.8em;
  color: #1e3a8a;
}

.header small {
  font-size: 0.9em;
  color: #6b7280;
}

/* ===========================
   Sections
=========================== */
.section {
  margin-bottom: 25px;
}

.section h3 {
  margin-bottom: 10px;
  padding-bottom: 6px;
  color: #1e3a8a;
  border-bottom: 2px solid #e5e7eb;
}

.details p {
  margin: 6px 0;
  font-size: 1.02em;
}

/* ===========================
   Table
=========================== */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

thead {
  background: #1e3a8a;
  color: #fff;
}

th,
td {
  padding: 12px;
  text-align: left;
}

tr:nth-child(even) {
  background: #f9fafb;
}

/* ===========================
   Totals
=========================== */
.total-box {
  text-align: right;
  margin-top: 20px;
}

.total-box strong {
  font-size: 1.2em;
  color: #111827;
}

/* ===========================
   Status Badges
=========================== */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-paid {
  background: #dcfce7;
  color: #166534;
}

.status-failed {
  background: #fee2e2;
  color: #991b1b;
}

.status-refunded {
  background: #e0f2fe;
  color: #075985;
}

.status-cancelled {
  background: #f3f4f6;
  color: #374151;
}

.gateway {
  font-weight: 600;
  text-transform: capitalize;
  color: #1e3a8a;
}

/* ===========================
   Footer
=========================== */
.footer {
  text-align: center;
  margin-top: 40px;
  font-size: 0.9em;
  color: #6b7280;
}

/* ===========================
   Buttons / Navigation
=========================== */
.action-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 25px;
}

.back-link,
.print-btn {
  font-size: 1em;
  font-weight: 500;
  color: #2563eb;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
}

.back-link:hover,
.print-btn:hover {
  text-decoration: underline;
}

/* ===========================
   Print Styles
=========================== */
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

/* ===========================
   Cancel Order Button (Centered)
=========================== */

/* Center the cancel button */
.cancel-form {
  margin: 0 auto; /* This pushes form to center */
}

/* Cancel button styling */
.cancel-btn {
  background: #dc2626;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s ease;
}

.cancel-btn:hover {
  background: #b91c1c;
}


</style>
</head>
<body>

<div class="invoice-container">
  <div class="header">
    
    <div class="logo-title">
      <img src="/admin/images/logo.jpg" alt="Chandusoft Logo">
      <h2>Chandusoft Pvt. Ltd.</h2>
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

    <?php if ($can_cancel): ?>
    <form method="post" action="/admin/cancel_order" class="cancel-form">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit" class="cancel-btn">❌ Cancel Order</button>
    </form>
    <?php endif; ?>

    <a href="/admin/orders" class="back-link">← Back to Orders</a>
</div>


  <div class="footer">
    <p>Thank you for your shopping!</p>
  </div>
</div>

</body>
</html>