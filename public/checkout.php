<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// ✅ Support "Buy Now" direct product load
if (isset($_GET['action']) && $_GET['action'] === 'buy' && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);

    // Fetch product by slug
    $stmt = $pdo->prepare("SELECT id, title, price FROM catalog WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $_SESSION['cart'] = [[
            'product_id'   => $product['id'],
            'product_name' => $product['title'],
            'unit_price'   => (float)$product['price'],
            'quantity'     => 1
        ]];
    } else {
        die('Product not found.');
    }
}

// ✅ Ensure cart exists
$cart_session = $_SESSION['cart'] ?? [];
if (empty($cart_session)) {
    header('Location: cart.php');
    exit;
}

// ✅ Normalize cart data structure (support both cart.php & buy-now)
$cart = [];
foreach ($cart_session as $key => $item) {
    if (isset($item['product_id'])) {
        // Already in buy-now format
        $cart[] = $item;
    } else {
        // Convert from cart.php format
        $cart[] = [
            'product_id'   => $item['id'] ?? $key,
            'product_name' => $item['title'] ?? '',
            'unit_price'   => (float)($item['price'] ?? 0),
            'quantity'     => (int)($item['quantity'] ?? 1)
        ];
    }
}

// ✅ Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += $item['unit_price'] * $item['quantity'];
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_gateway = $_POST['payment_gateway'] ?? 'stripe';

    if ($name === '') $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    if (empty($errors)) {
        try {
            $orderRef = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

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
                ':gateway' => $payment_gateway,
                ':meta'  => json_encode($cart, JSON_UNESCAPED_SLASHES)
            ]);

            $orderId = $pdo->lastInsertId();

            $stmtItems = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, unit_price, total_price
                ) VALUES (:oid, :pid, :name, :qty, :price, :line)
            ");

            foreach ($cart as $c) {
                $price = $c['unit_price'];
                $qty   = $c['quantity'];
                $stmtItems->execute([
                    ':oid'  => $orderId,
                    ':pid'  => $c['product_id'],
                    ':name' => $c['product_name'],
                    ':qty'  => $qty,
                    ':price'=> $price,
                    ':line' => $price * $qty
                ]);
            }

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
          <?php $price = $item['unit_price']; $qty = $item['quantity']; ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
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
      <a href="cart.php" class="button">← Back to Cart</a>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
