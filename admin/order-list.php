<?php
// admin/orders.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';

// Basic role check (adapt to your auth)
if (($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Access denied');
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Orders</title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f8fafc;
      margin: 0;
      padding: 20px;
    }
    h1 {
      text-align: center;
      color: #334155;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    thead {
      background: #1e3a8a;
      color: #fff;
    }
    th, td {
      padding: 12px 16px;
      text-align: left;
    }
    tr:nth-child(even) {
      background: #f1f5f9;
    }
    a {
      text-decoration: none;
      color: #2563eb;
      font-weight: 500;
    }
    a:hover {
      text-decoration: underline;
    }
    td:last-child a {
      background: #2563eb;
      color: #fff;
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
    }
    td:last-child a:hover {
      background: #1e40af;
    }
  </style>
</head>
<body>
  <h1>Orders</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Ref</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Gateway</th>
        <th>Status</th>
        <th>Created</th>
        <th>View</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['order_ref']) ?></td>
          <td><?= htmlspecialchars($o['customer_name'] . ' / ' . $o['customer_email']) ?></td>
          <td>$<?= number_format($o['total'],2) ?></td>
          <td><?= htmlspecialchars($o['payment_gateway']) ?></td>
          <td><?= htmlspecialchars($o['payment_status']) ?></td>
          <td><?= $o['created_at'] ?></td>
          <td><a href="order_view.php?id=<?= $o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
