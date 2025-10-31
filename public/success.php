<?php
// public/success.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';

$order_ref = $_GET['order_ref'] ?? '';

if (!$order_ref) {
    die('Invalid order reference.');
}

// ✅ Update payment status to 'paid' for demo mode
$stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_ref = :ref");
$stmt->execute(['ref' => $order_ref]);

// ✅ Fetch order and item details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = :ref LIMIT 1");
$stmt->execute(['ref' => $order_ref]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}

// Decode order metadata (stored as JSON)
$items = json_decode($order['metadata'], true) ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Order Receipt - <?= htmlspecialchars($order_ref) ?></title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f8fafc;
  margin: 0;
  padding: 20px;
  color: #333;
}
.container {
  max-width: 700px;
  background: #fff;
  margin: 40px auto;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
h1 {
  color: #16a34a;
  text-align: center;
  margin-bottom: 10px;
}
h2 {
  color: #2563eb;
  margin-top: 30px;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
th, td {
  border-bottom: 1px solid #ddd;
  padding: 8px;
  text-align: left;
}
th {
  background: #2563eb;
  color: white;
}
tfoot td {
  font-weight: bold;
  background: #f1f5f9;
}
.summary {
  margin-top: 20px;
}
a.button {
  display: inline-block;
  margin-top: 25px;
  text-decoration: none;
  background: #2563eb;
  color: #fff;
  padding: 10px 20px;
  border-radius: 8px;
  transition: background 0.2s ease;
}
a.button:hover {
  background: #1e40af;
}
</style>
</head>
<body>
<div class="container">
  <h1>✅ Payment Successful</h1>
  <p style="text-align:center;">Thank you for your purchase! Your order has been confirmed.</p>

  <div class="summary">
    <p><strong>Order Ref:</strong> <?= htmlspecialchars($order['order_ref']) ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars(strtoupper($order['payment_status'])) ?></p>
  </div>

  <h2>Order Summary</h2>
  <table>
    <thead>
      <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['product_name'] ?? '') ?></td>
        <td><?= (int)($it['quantity'] ?? 1) ?></td>
        <td>$<?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
        <td>$<?= number_format(((float)($it['unit_price'] ?? 0)) * ((int)($it['quantity'] ?? 1)), 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3" style="text-align:right;">Total:</td>
      <td>$<?= number_format((float)$order['total'], 2) ?></td></tr>
    </tfoot>
  </table>

  <p style="text-align:center;">
    <a href="catalog.php" class="button">← Continue Shopping</a>
  </p>
</div>
</body>
</html>
