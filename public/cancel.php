<?php
// public/cancel.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';

$order_ref = $_GET['order_ref'] ?? ''; // ✅ corrected key name

if ($order_ref) {
    // Mark order as cancelled
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'cancelled' WHERE order_ref = :ref");
    $stmt->execute(['ref' => $order_ref]);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payment Cancelled</title>
<style>
  body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #fef2f2;
    color: #333;
    margin: 0;
    padding: 0;
  }
  .container {
    max-width: 600px;
    margin: 60px auto;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
  }
  h1 {
    color: #dc2626;
    font-size: 1.8em;
    margin-bottom: 10px;
  }
  p {
    font-size: 1.05em;
    line-height: 1.6;
    margin: 10px 0;
  }
  strong { color: #b91c1c; }
  a {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: #b91c1c;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    transition: background 0.2s ease;
  }
  a:hover { background: #7f1d1d; }
</style>
</head>
<body>
  <div class="container">
    <h1>❌ Payment Cancelled</h1>
    <p>Your order <strong><?= htmlspecialchars($order_ref) ?></strong> was cancelled.</p>
    <p>You can try again or contact support.</p>

    <!-- ✅ Updated redirect link -->
    <p><a href="<?= BASE_URL ?>/public/cart.php">Return to cart</a></p>
  </div>
</body>
</html>
