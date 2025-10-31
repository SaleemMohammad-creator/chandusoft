<?php
// admin/order_view.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/csrf.php';

if (($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Access denied');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid order id');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) die('Order not found');

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$csrf = csrf_token();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order #<?= $order['id'] ?></title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f9fafb;
      color: #1f2937;
      margin: 0;
      padding: 30px;
    }
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #1e3a8a;
      border-bottom: 2px solid #1e3a8a;
      padding-bottom: 8px;
      margin-bottom: 20px;
    }
    p {
      font-size: 1.05em;
      line-height: 1.6;
    }
    strong {
      color: #111827;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
      border-radius: 6px;
      overflow: hidden;
      background: #f9fafb;
    }
    thead {
      background: #1e3a8a;
      color: #fff;
    }
    th, td {
      padding: 10px 14px;
      text-align: left;
    }
    tr:nth-child(even) {
      background: #f1f5f9;
    }
    h3 {
      margin-top: 30px;
      color: #334155;
    }
    form {
      margin-top: 10px;
    }
    select {
      padding: 8px;
      font-size: 1em;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      outline: none;
    }
    button {
      background: #2563eb;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 1em;
      cursor: pointer;
      margin-left: 10px;
    }
    button:hover {
      background: #1e40af;
    }
    a {
      display: inline-block;
      margin-top: 25px;
      text-decoration: none;
      color: #2563eb;
      font-weight: 500;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Order <?= htmlspecialchars($order['order_ref']) ?></h1>

    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_email']) ?>)</p>
    <p><strong>Total:</strong> $<?= number_format($order['total'], 2) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['payment_status']) ?></p>

    <h2>Items</h2>
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Line</th>
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

    <h3>Admin Actions</h3>
    <form method="post" action="order_actions.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <select name="new_status">
        <option value="pending" <?= $order['payment_status']==='pending'?'selected':'' ?>>pending</option>
        <option value="paid" <?= $order['payment_status']==='paid'?'selected':'' ?>>paid</option>
        <option value="failed" <?= $order['payment_status']==='failed'?'selected':'' ?>>failed</option>
        <option value="refunded" <?= $order['payment_status']==='refunded'?'selected':'' ?>>refunded</option>
        <option value="cancelled" <?= $order['payment_status']==='cancelled'?'selected':'' ?>>cancelled</option>
      </select>
      <button type="submit">Update Status</button>
    </form>

    <p><a href="orders.php">← Back to orders</a></p>
  </div>
</body>
</html>
