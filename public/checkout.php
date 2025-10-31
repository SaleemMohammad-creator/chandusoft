<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// Ensure cart exists
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// ✅ Calculate total
$total = 0;
foreach ($cart as $item) {
    $price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
    $qty   = isset($item['quantity']) ? (int)$item['quantity'] : 0;
    $total += $price * $qty;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_gateway = $_POST['payment_gateway'] ?? 'stripe'; // ✅ Added payment option

    // Validate input
    if ($name === '') $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    if (empty($errors)) {
        try {
            // Create unique order reference
            $orderRef = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

            // ✅ Insert into `orders` table (match your table schema)
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_ref, customer_name, customer_email, total,
                    payment_gateway, payment_status, metadata
                )
                VALUES (:ref, :name, :email, :total, :gateway, 'pending', :meta)
            ");
            $stmt->execute([
                ':ref'   => $orderRef,
                ':name'  => $name,
                ':email' => $email,
                ':total' => $total,
                ':gateway' => $payment_gateway, // ✅ user-selected gateway
                ':meta'  => json_encode($cart, JSON_UNESCAPED_SLASHES)
            ]);

            // ✅ Get last inserted order ID
            $orderId = $pdo->lastInsertId();

            // ✅ Insert order items (including product_id fix)
            $stmtItems = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, unit_price, total_price
                ) VALUES (:oid, :pid, :name, :qty, :price, :line)
            ");

            foreach ($cart as $c) {
                $price = isset($c['unit_price']) ? (float)$c['unit_price'] : 0;
                $qty   = isset($c['quantity']) ? (int)$c['quantity'] : 0;
                $stmtItems->execute([
                    ':oid'  => $orderId,
                    ':pid'  => $c['product_id'] ?? 0,
                    ':name' => $c['product_name'] ?? '',
                    ':qty'  => $qty,
                    ':price'=> $price,
                    ':line' => $price * $qty
                ]);
            }

            // ✅ Always redirect to checkout_process.php (without changing logic)
            header("Location: checkout_process.php?order_ref=" . urlencode($orderRef));
            exit;

        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
  font-family: Arial, sans-serif;
  background: #f7f8fc;
  margin: 0;
  padding: 20px;
}
.container {
  max-width: 900px;
  background: #fff;
  margin: 50px auto;
  border-radius: 10px;
  padding: 20px 30px;
  box-shadow: 0 4px 10px #0001;
}
h1 {
  text-align: center;
  color: #007BFF;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
th, td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}
th { background: #007BFF; color: #fff; }
form {
  margin-top: 25px;
}
input, textarea {
  width: 100%;
  padding: 10px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
button, a.button {
  background: #007BFF;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  font-weight: bold;
  transition: background 0.3s ease;
}
button:hover, a.button:hover {
  background: #0056b3;
}
.success {
  background: #d4edda;
  color: #155724;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 15px;
}
.error {
  background: #f8d7da;
  color: #721c24;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 15px;
}
</style>
</head>
<body>
<div class="container">
  <h1>Checkout</h1>

  <?php if ($success): ?>
    <div class="success">
      ✅ Order placed successfully!  
      Your order reference is <strong><?= htmlspecialchars($orderRef) ?></strong>.
    </div>
    <p style="text-align:center;"><a href="catalog.php" class="button">← Continue Shopping</a></p>

  <?php else: ?>

    <?php if ($errors): ?>
      <div class="error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <h2>Order Summary</h2>
    <table>
      <thead>
        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($cart as $item): ?>
          <?php
            $price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
            $qty   = isset($item['quantity']) ? (int)$item['quantity'] : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
            <td><?= $qty ?></td>
            <td>$<?= number_format($price, 2) ?></td>
            <td>$<?= number_format($price * $qty, 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <h3 style="text-align:right;">Total: $<?= number_format($total, 2) ?></h3>

    <h2>Customer Information</h2>
    <form method="post">
      <input type="text" name="name" placeholder="Your Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      <input type="email" name="email" placeholder="Your Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <textarea name="address" placeholder="Your Address (optional)" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

      <button type="submit">Pay Now</button>
      <!-- ✅ Fixed cart link to public folder -->
      <a href="cart.php" class="button">← Back to Cart</a>
    </form>

  <?php endif; ?>
</div>
</body>
</html>
