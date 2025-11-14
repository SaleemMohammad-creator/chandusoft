<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';

$order_ref = $_GET['order_ref'] ?? '';
if (!$order_ref) {
    die('Invalid order reference.');
}

// ✅ Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = :ref LIMIT 1");
$stmt->execute(['ref' => $order_ref]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('Order not found.');

// ✅ Clear cart only if payment is successful
if (strtolower($order['payment_status']) === 'paid') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['cart']);
}

// ✅ Extract fields
$gateway = strtoupper($order['payment_gateway'] ?? 'STRIPE');
$payment_status = strtolower($order['payment_status']);
$txn_id = $order['transaction_id'] ?: "Awaiting confirmation..."; // ✅ Fix

// ✅ Status UI
switch ($payment_status) {
    case 'paid':
        $status_color = '#16a34a';
        $status_title = '✅ Payment Successful';
        break;
    case 'failed':
        $status_color = '#dc2626';
        $status_title = '❌ Payment Failed';
        break;
    default:
        $status_color = '#f59e0b';
        $status_title = '⏳ Awaiting Payment Confirmation';
        break;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Order Receipt - <?= htmlspecialchars($order_ref) ?></title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f8fafc;
  margin: 0;
  padding: 20px;
}
.container {
  max-width: 700px;
  background: #fff;
  margin: 40px auto;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
h1 { color: <?= $status_color ?>; text-align:center; }
p { font-size: 18px; }
</style>
</head>
<body>
<div class="container">
  <h1><?= $status_title ?></h1>

  <p><strong>Order Ref:</strong> <?= htmlspecialchars($order['order_ref']) ?></p>
  <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
  <p><strong>Status:</strong> <?= strtoupper($payment_status) ?></p>
  <p><strong>Payment Method:</strong> <?= $gateway ?></p>

  <!-- ✅ Shows actual pi_xxxxxx once webhook updates DB -->
  <p><strong>Transaction ID:</strong> <?= htmlspecialchars($txn_id) ?></p>

  <a href="catalog.php">← Continue Shopping</a>
</div>

<?php if ($payment_status !== 'paid'): ?>
<script>
    // ⏳ Keep refreshing until webhook updates the DB with transaction ID
    setTimeout(() => { location.reload(); }, 5000);
</script>
<?php endif; ?>
</body>
</html>
