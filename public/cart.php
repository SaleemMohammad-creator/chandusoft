<?php
// public/cart.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// CSRF Fallback
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_verify')) {
    function csrf_verify($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

$_SESSION['cart'] = $_SESSION['cart'] ?? [];

// Helper to recalc cart totals (expects variable passed by reference)
function recalc_total(array &$cart): float {
    $total = 0.0;
    foreach ($cart as $k => $item) {
        // ensure unit_price and quantity exist and are numeric
        $unit = isset($item['unit_price']) ? (float)$item['unit_price'] : 0.0;
        $qty  = isset($item['quantity'])   ? (int)$item['quantity'] : 0;
        $cart[$k]['quantity'] = max(0, $qty);
        $cart[$k]['total_price'] = round($unit * $cart[$k]['quantity'], 2);
        $total += $cart[$k]['total_price'];
    }
    return round($total, 2);
}

// Make a local reference so we can safely pass by reference
$cart =& $_SESSION['cart'];

$action = $_REQUEST['action'] ?? null;

// =========================
// ADD ITEM (supports GET + POST)
// =========================
if ($action === 'add') {
    // Case 1: Adding via catalog.php (GET /cart.php?action=add&slug=...)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['slug'])) {
        $slug = trim($_GET['slug']);

        // Fetch product by slug
        $stmt = $pdo->prepare("SELECT id, title, price FROM catalog WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $pid = (int)$product['id'];
            $pname = $product['title'];
            $pprice = (float)$product['price'];

            if (isset($cart[$pid])) {
                $cart[$pid]['quantity'] += 1;
            } else {
                $cart[$pid] = [
                    'product_id' => $pid,
                    'product_name' => $pname,
                    'unit_price' => $pprice,
                    'quantity' => 1,
                    'total_price' => round($pprice, 2)
                ];
            }
            $_SESSION['cart'] = $cart;
            recalc_total($cart);
        }

        header('Location: /public/cart.php');
        exit;
    }

    // Case 2: Add via POST form (if used somewhere else)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $price = (float)($_POST['unit_price'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        if ($id > 0 && $name && $price > 0) {
            if (isset($cart[$id])) {
                $cart[$id]['quantity'] += $qty;
            } else {
                $cart[$id] = [
                    'product_id' => $id,
                    'product_name' => $name,
                    'unit_price' => $price,
                    'quantity' => $qty,
                    'total_price' => round($price * $qty, 2)
                ];
            }
            $_SESSION['cart'] = $cart;
            recalc_total($cart);
        }

        header('Location: /public/cart.php');
        exit;
    }
}


// UPDATE CART
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        die('Invalid CSRF token');
    }

    $quantities = $_POST['quantities'] ?? [];

    // iterate and update quantities + line totals
    foreach ($quantities as $pid => $q) {
        $pid = (int)$pid;
        $q = max(0, (int)$q);
        if ($q === 0) {
            // remove item
            if (isset($cart[$pid])) {
                unset($cart[$pid]);
            }
        } else {
            if (!isset($cart[$pid])) continue;
            $cart[$pid]['quantity'] = $q;
            // update line total here (defensive)
            $cart[$pid]['total_price'] = round(($cart[$pid]['unit_price'] ?? 0) * $q, 2);
        }
    }

    // Recalculate grand total and persist to session
    $grand = recalc_total($cart);
    $_SESSION['cart'] = $cart;

    // redirect so user sees updated values (and to avoid double form submit)
    header('Location: /public/cart.php?updated=1');
    exit;
}

// CLEAR CART
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: /public/cart.php?cleared=1');
    exit;
}

// Render cart (recalc to ensure values are consistent)
$total = recalc_total($cart);
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
.container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #333; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
th { background-color: #f1f1f1; }
input[type=number] { width: 70px; padding: 5px; text-align: center; }
.total { text-align: right; font-weight: bold; font-size: 18px; margin-top: 15px; }
.actions { margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; }
button, a.btn { padding: 10px 16px; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; font-weight: 500; color: #fff; }
.btn-update { background: #2563eb; }
.btn-update:hover { background: #1d4ed8; }
.btn-clear { background: #dc2626; }
.btn-clear:hover { background: #b91c1c; }
.btn-checkout { background: #059669; }
.btn-checkout:hover { background: #047857; }
.alert { text-align: center; margin-bottom: 10px; padding: 10px; border-radius: 6px; }
.alert-success { background: #d1fae5; color: #065f46; }
.empty { text-align: center; padding: 30px; }
.line-total { white-space: nowrap; }
.unit { white-space: nowrap; }
</style>
</head>
<body>
<div class="container">
<h2>Your Shopping Cart</h2>

<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">✅ Cart updated successfully!</div>
<?php elseif (isset($_GET['cleared'])): ?>
  <div class="alert alert-success">🗑️ Cart cleared.</div>
<?php endif; ?>

<?php if (empty($cart)): ?>
  <div class="empty">
    <p>Your cart is empty.</p>
    <a href="/public/catalog.php" class="btn btn-update">Back to Catalog</a>
  </div>
<?php else: ?>
  <form method="post" action="/public/cart.php?action=update" id="cartForm">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Unit Price</th>
          <th>Quantity</th>
          <th>Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cart as $pid => $item): ?>
        <tr data-pid="<?= (int)$pid ?>">
          <td><?= htmlspecialchars($item['product_name']) ?></td>
          <td class="unit">$<?= number_format((float)$item['unit_price'], 2) ?></td>
          <td>
            <input type="number" name="quantities[<?= (int)$pid ?>]" value="<?= (int)$item['quantity'] ?>" min="0" data-unit="<?= htmlspecialchars(number_format((float)$item['unit_price'], 2, '.', '')) ?>">
          </td>
          <td class="line-total">$<?= number_format((float)$item['total_price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="total">Total: $<span id="grandTotal"><?= number_format($total, 2) ?></span></div>

    <div class="actions">
      <button type="submit" class="btn btn-update">Update Cart</button>
      <a href="/public/checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
      <a href="/public/cart.php?action=clear" class="btn btn-clear">Clear Cart</a>
    </div>
  </form>
<?php endif; ?>
</div>

<!-- Instant client-side update for line totals + grand total (not a substitute for server update) -->
<script>
(function(){
  function toFloat(v){ return parseFloat(v) || 0; }
  function format(n){ return n.toFixed(2); }

  const grandEl = document.getElementById('grandTotal');

  function recalcGrand() {
    let sum = 0;
    document.querySelectorAll('tbody tr').forEach(row => {
      const lt = row.querySelector('.line-total').textContent.replace('$','');
      sum += toFloat(lt);
    });
    grandEl.textContent = format(sum);
  }

  document.querySelectorAll('input[type="number"][name^="quantities"]').forEach(input => {
    input.addEventListener('input', function(){
      const unit = toFloat(this.dataset.unit);
      const qty  = Math.max(0, parseInt(this.value) || 0);
      const row  = this.closest('tr');
      const lineCell = row.querySelector('.line-total');
      const newLine = unit * qty;
      lineCell.textContent = '$' + format(newLine);
      recalcGrand();
    });
  });
})();
</script>
</body>
</html>
