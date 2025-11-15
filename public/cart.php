<?php
// ===============================================
// 🛒 Cart Page (Display Cart, Update Quantity, Remove Items)
// ===============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// debug log file
$debugLog = __DIR__ . '/../storage/logs/cart_debug.log';
if (!file_exists(dirname($debugLog))) mkdir(dirname($debugLog), 0755, true);

// helper: write debug
function cart_debug($msg) {
    global $debugLog;
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Helper function to read parameters
function readParam($name) {
    return $_POST[$name] ?? $_GET[$name] ?? null;
}

// Action: Add/Update/Remove Cart Item
$action = readParam('action');
$slug = readParam('slug');
$qty = readParam('qty');
$pid_in = readParam('product_id');

if ($action === 'add' && $slug) {
    $stmt = $pdo->prepare("SELECT id, title, price, image FROM catalog WHERE slug = ? AND status='published'");
    $stmt->execute([$slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $pid = (int)$product['id'];
        $_SESSION['cart'][$pid] = [
            'id' => $pid,
            'title' => $product['title'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => isset($_SESSION['cart'][$pid]) ? $_SESSION['cart'][$pid]['quantity'] + $qty : $qty
        ];
        cart_debug("Added to cart: $pid with qty: $qty");
    } else {
        cart_debug("Product not found for slug: $slug");
    }

} elseif ($action === 'remove' && $pid_in) {
    unset($_SESSION['cart'][$pid_in]);
    cart_debug("Removed from cart: $pid_in");

} elseif ($action === 'clear') {  // ✅ ADDED — CLEAR ENTIRE CART
    $_SESSION['cart'] = [];
    cart_debug("Cart cleared completely");

} elseif ($action === 'update' && !empty($_GET['qty'])) {
    foreach ($_GET['qty'] as $pid => $quantity) {
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] = max(1, (int)$quantity);
        }
    }
    cart_debug("Cart quantities updated: " . json_encode($_GET['qty']));
    header("Location: /public/cart.php");
    exit;
}

// Check cart status
$cart_items = $_SESSION['cart'];
$cart_total = 0;
$cart_qty = 0;

if ($cart_items) {
    foreach ($cart_items as $pid => $item) {
        if (isset($item['id'], $item['title'], $item['price'], $item['quantity']) && is_numeric($item['price']) && is_numeric($item['quantity'])) {
            $cart_total += $item['price'] * $item['quantity'];
            $cart_qty += $item['quantity'];
        } else {
            cart_debug("Missing or invalid data for cart item with ID: $pid. Data: " . json_encode($item));
            unset($_SESSION['cart'][$pid]);
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      /* ===========================
   Global Styles
=========================== */
body {
    font-family: Arial, sans-serif;
    background-color: #f1f3f6;
    margin: 0;
    padding: 40px;
}

/* ===========================
   Container
=========================== */
.container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

/* ===========================
   Headings
=========================== */
h1 {
    text-align: center;
    color: #007bff;
    font-size: 28px;
    margin-bottom: 25px;
}

/* ===========================
   Table
=========================== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

th,
td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f8f9fa;
    color: #333;
    font-weight: bold;
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    vertical-align: middle;
}

/* ===========================
   Buttons
=========================== */
button,
.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    text-align: center;
    display: inline-block;
}

.update-btn {
    background-color: #007bff;
    color: #fff;
}

.update-btn:hover {
    background-color: #0069d9;
}

.clear-btn {
    background-color: #dc3545;
    color: #fff;
}

a.btn {
    text-decoration: none;
    color: #fff;
}

a.btn:hover {
    opacity: 0.9;
}

/* ===========================
   Quantity Controls
=========================== */
.quantity-controls {
    display: inline-flex;
    align-items: center;
    border: 1px solid #ccc;
    border-radius: 6px;
    overflow: hidden;
    margin: 10px 0;
}

.quantity-controls button {
    background: #007bff;
    color: #fff;
    border: none;
    width: 35px;
    height: 35px;
    font-size: 18px;
    cursor: pointer;
}

.quantity-controls input {
    width: 50px;
    text-align: center;
    border: none;
    font-size: 16px;
}

/* ===========================
   Cart Footer
=========================== */
.cart-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-top: 20px;
}

.cart-buttons {
    display: flex;
    gap: 15px;
}

.cart-info {
    text-align: right;
}

.cart-info p {
    margin: 5px 0;
}

/* ===========================
   Responsive
=========================== */
@media (max-width: 600px) {
    .cart-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .cart-buttons {
        justify-content: center;
        margin-bottom: 10px;
    }

    .cart-info {
        text-align: center;
    }
}

    </style>
</head>
<body>
<div class="container">
    <h1>Your Cart</h1>

<?php if (empty($cart_items)): ?>
    <p>Your cart is empty.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cart_items as $item): ?>
            <?php if (isset($item['id'], $item['title'], $item['price'], $item['quantity'])): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <form method="GET" action="/public/cart.php">
                            <div class="quantity-controls">
                                <button type="button" onclick="changeQty(this, -1)">−</button>
                                <input type="text" name="qty[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" readonly>
                                <button type="button" onclick="changeQty(this, 1)">+</button>
                            </div>
                            <input type="hidden" name="action" value="update">
                            <button type="submit" class="btn update-btn">Update</button>
                        </form>
                    </td>
                    <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    <td>
                        <a href="/public/cart.php?action=remove&product_id=<?= $item['id'] ?>" class="btn clear-btn">Remove</a>
                    </td>
                </tr>
            <?php else: ?>
                
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="cart-footer">
    <div class="cart-buttons">
        <a href="/public/catalog.php" class="btn update-btn">
            <?= empty($cart_items) ? 'Continue Shopping' : 'Back to Catalog' ?>
        </a>

        <?php if (!empty($cart_items)): ?>
            <!-- ✅ ADDED CLEAR CART BUTTON -->
            <a href="/public/cart.php?action=clear" class="btn clear-btn">Clear Cart</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($cart_items)): ?>
        <div class="cart-info">
           <p><strong>Items in Cart: </strong><?= $cart_qty ?></p>
            <p><strong>Total: </strong>$<?= number_format($cart_total, 2) ?></p>
            <a href="/public/checkout.php" class="btn update-btn">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

</div>

<script>
function changeQty(button, delta) {
    const input = button.parentElement.querySelector('input[type="text"]');
    let qty = parseInt(input.value);
    qty += delta;
    if (qty < 1) qty = 1;
    input.value = qty;
}
</script>

</body>
</html>
