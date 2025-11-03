<?php
// public/cart.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ✅ Add item to cart (from catalog)
if (($_GET['action'] ?? '') === 'add' && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    $qty = max(1, intval($_GET['qty'] ?? 1));

    $stmt = $pdo->prepare("SELECT id, title, price, image FROM catalog WHERE slug = ? AND status='published' LIMIT 1");
    $stmt->execute([$slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $pid = $product['id'];
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$pid] = [
                'id' => $pid,
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $qty
            ];
        }
    }

    header("Location: /public/cart.php");
    exit;
}

// ✅ Update item quantity (NEW)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $pid = intval($_POST['product_id']);
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['quantity'] = $qty; // Replace, not increase
    }

    header("Location: /public/cart.php");
    exit;
}

// ✅ Remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $pid = intval($_POST['product_id']);
    unset($_SESSION['cart'][$pid]);
    header("Location: /public/cart.php");
    exit;
}

// ✅ Empty cart via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'empty') {
    $_SESSION['cart'] = [];
    header("Location: /public/cart.php");
    exit;
}

// ✅ Empty cart via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: /public/cart.php");
    exit;
}

// Fetch cart products
$cart_items = [];
$total = 0.0;

if ($_SESSION['cart']) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT * FROM catalog WHERE id IN ($ids) AND status='published'");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']]['quantity'];
        $p['quantity'] = $qty;
        $p['subtotal'] = $p['price'] * $qty;
        $total += $p['subtotal'];
        $cart_items[] = $p;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Shopping Cart</title>
<style>
/* ============================
   CART PAGE FULL CSS STYLES
   ============================ */

body {
    font-family: Arial, sans-serif;
    background-color: #f1f3f6;
    margin: 0;
    padding: 40px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}

/* ============================
   TABLE STYLES
   ============================ */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f8f9fa;
    color: #333;
    font-weight: bold;
}

tr:hover {
    background-color: #f1f1f1;
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    vertical-align: middle;
}

/* ============================
   BUTTONS
   ============================ */
button, .btn {
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

/* Blue - Update */
.update-btn, .btn-update {
    background-color: #007bff;
    color: #fff;
}
.update-btn:hover, .btn-update:hover {
    background-color: #0069d9;
}

/* Green - Checkout */
.checkout-btn, .btn-checkout {
    background-color: #28a745;
    color: #fff;
    text-decoration: none;
    display: inline-block;
}
.checkout-btn:hover, .btn-checkout:hover {
    background-color: #218838;
}

/* Red - Clear/Remove */
.clear-btn, .btn-clear {
    background-color: #dc3545;
    color: #fff;
    text-decoration: none;
    display: inline-block;
}
.clear-btn:hover, .btn-clear:hover {
    background-color: #c82333;
}

/* ============================
   TOTAL ROW
   ============================ */
.total-row td {
    font-weight: bold;
    text-align: right;
    background: #f8f9fa;
    border-top: 2px solid #ddd;
}

/* ============================
   BUTTON GROUP AREA
   ============================ */
.btn-group {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}

a {
    text-decoration: none;
}

/* ============================
   RESPONSIVE DESIGN
   ============================ */
@media (max-width: 600px) {
    body {
        padding: 20px;
    }

    .container {
        padding: 20px;
    }

    table, th, td {
        font-size: 14px;
    }

    .btn-group {
        flex-direction: column;
        align-items: center;
    }

    .btn-group .btn,
    .btn-group button {
        width: 80%;
    }
}
</style>
</head>
<body>

<div class="container">
    <h2>Your Shopping Cart</h2>

    <?php if ($cart_items): ?>
    <table>
        <tr>
            <th>Product</th>
            <th>Unit Price</th>
            <th>Quantity</th>
            <th>Line Total</th>
            <th>Action</th>
        </tr>

        <?php foreach ($cart_items as $item): ?>
        <tr>
            <td>
                <img src="<?= strpos($item['image'], '/uploads/') !== false ? $item['image'] : '/uploads/' . htmlspecialchars($item['image']) ?>" 
                class="product-image" 
                alt="<?= htmlspecialchars($item['title']) ?>">
                <?= htmlspecialchars($item['title']) ?>
            </td>
            <td>$<?= number_format($item['price'], 2) ?></td>
            <td>
                <form method="post" action="?action=update" style="display:inline;">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" style="width:60px; text-align:center;">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="update-btn">Update</button>
                </form>
            </td>
            <td>$<?= number_format($item['subtotal'], 2) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="action" value="remove">
                    <button type="submit" class="clear-btn">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="3">Total:</td>
            <td colspan="2">$<?= number_format($total, 2) ?></td>
        </tr>
    </table>

    <div class="btn-group" style="justify-content: space-between;">
    <div>
        <a href="/public/checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
        <a href="/public/cart.php?action=clear" class="btn btn-clear">Clear Cart</a>
    </div>
    <div>
        <a href="/public/catalog.php" class="btn btn-update">← Back to Catalog</a>
    </div>
</div>

    <?php else: ?>
    <div style="text-align:center;">
        <p>Your cart is empty.</p>
        <a href="catalog.php" class="btn btn-checkout">Go Shopping</a>
    </div>
<?php endif; ?>
</div>

</body>
</html>
