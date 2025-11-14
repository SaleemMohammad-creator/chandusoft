<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// ✅ Get order reference from URL
$order_ref = $_GET['order_ref'] ?? '';

if (!$order_ref) {
    header('Location: checkout.php');
    exit;
}

// ✅ Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = :ref");
$stmt->execute([':ref' => $order_ref]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Invalid order reference.");
}

$paid = false;
$errors = [];

// ✅ Handle payment form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';

    // ------------------------------------------------------------
    // 1️⃣ Manual Card Entry
    // ------------------------------------------------------------
    if ($method === 'card') {
        $cardName = trim($_POST['card_name'] ?? '');
        $cardNumber = trim($_POST['card_number'] ?? '');
        $expiry = trim($_POST['expiry'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');

        if ($cardName === '' || $cardNumber === '' || $expiry === '' || $cvv === '') {
            $errors[] = "Please fill in all card details.";
        } elseif (strlen($cardNumber) < 12) {
            $errors[] = "Invalid card number.";
        } else {
            $paid = true;
        }
    }

    // ------------------------------------------------------------
    // 2️⃣ PayPal Redirect (sandbox API)
    // ------------------------------------------------------------
    elseif ($method === 'paypal') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . "/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode(PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET)
        ]);

        $data = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => PAYPAL_CURRENCY,
                    "value" => number_format($order['total'], 2, '.', '')
                ]
            ]],
            "application_context" => [
                "return_url" => PAYPAL_RETURN_URL . "?order_ref=" . urlencode($order_ref),
                "cancel_url" => PAYPAL_CANCEL_URL . "?order_ref=" . urlencode($order_ref)
            ]
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $paypalOrder = json_decode($response, true);
        curl_close($ch);

        if (!empty($paypalOrder['links'])) {
            foreach ($paypalOrder['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    header("Location: " . $link['href']);
                    exit;
                }
            }
        }
        $errors[] = "PayPal order creation failed. Response: " . htmlspecialchars($response);
    }

    // ------------------------------------------------------------
    // ✅ 3️⃣ Stripe Redirect (UPDATED!)
    // ------------------------------------------------------------
    elseif ($method === 'stripe') {

        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        $amount = floatval($order['total']) * 100; // cents

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name' => 'Order #' . $order_ref],
                    'unit_amount'  => intval($amount),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',

            // ✅ Attach to payment intent metadata so webhook can update DB
            'payment_intent_data' => [
                'metadata' => [
                    'order_ref' => $order_ref
                ]
            ],

            // ✅ Success → we redirect to success page
            'success_url' => BASE_URL . "/public/success.php?order_ref={$order_ref}",

            // ✅ Cancel → DO NOT store transaction id, just mark as failed
            'cancel_url' => BASE_URL . "/public/cancel.php?order_ref={$order_ref}&session_id={CHECKOUT_SESSION_ID}",
        ]);

        header("Location: " . $checkout_session->url);
        exit;
    }

    // ✅ If manual card payment succeeds
    if ($paid) {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_ref = :ref");
        $stmt->execute([':ref' => $order_ref]);
        header("Location: success.php?order_ref=" . urlencode($order_ref));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Complete Payment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ---- your original styling untouched ----- */
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  background: #f7f9fc;
  margin: 0;
  padding: 0;
}
.container {
  max-width: 600px;
  background: #fff;
  margin: 60px auto;
  padding: 30px 40px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
h1 {
  text-align: center;
  color: #007bff;
  font-size: 1.8em;
}
p {
  text-align: center;
  color: #444;
  margin-bottom: 20px;
}
label {
  display: block;
  margin-top: 10px;
  font-weight: 600;
}
input {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 1em;
}
button {
  display: block;
  width: 100%;
  margin-top: 25px;
  background: #007bff;
  color: #fff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 1.1em;
  cursor: pointer;
}
button:hover { background: #0056b3; }
.error { background: #f8d7da; padding: 10px; border-radius: 8px; }
.payment-options { display: flex; justify-content: space-around; margin: 20px 0; }
.option {
  border: 2px solid #ccc;
  border-radius: 8px;
  padding: 12px 20px;
  cursor: pointer;
}
.option:hover {
  border-color: #007bff;
  background: #f0f7ff;
}
.option.active {
  border-color: #007bff;
  background: #e9f2ff;
}
.card-form { margin-top: 20px; }
.back-link { text-align: center; margin-top: 20px; }
a.button { text-decoration: none; background: #6c757d; color: #fff; padding: 10px 16px; border-radius: 6px; }
a.button:hover { background: #5a6268; }
</style>
<script>
function selectMethod(method) {
  document.querySelectorAll('.option').forEach(el => el.classList.remove('active'));
  document.getElementById(method).classList.add('active');
  document.getElementById('method').value = method;
  document.getElementById('cardDetails').style.display = (method === 'card') ? 'block' : 'none';
}
</script>
</head>
<body>
<div class="container">
  <h1>Complete Your Payment</h1>
  <p>Order Reference: <strong><?= htmlspecialchars($order_ref) ?></strong></p>
  <p>Amount Due: <strong>$<?= number_format($order['total'], 2) ?></strong></p>

  <?php if ($errors): ?>
    <div class="error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="method" id="method" value="">

    <div class="payment-options">
      <div class="option" id="card" onclick="selectMethod('card')">💳 Credit / Debit Card</div>
      <div class="option" id="paypal" onclick="selectMethod('paypal')">🅿️ PayPal</div>
      <div class="option" id="stripe" onclick="selectMethod('stripe')">💠 Stripe</div>
    </div>

    <div id="cardDetails" class="card-form" style="display:none;">
      <label>Cardholder Name</label>
      <input type="text" name="card_name" placeholder="John Doe">

      <label>Card Number</label>
      <input type="text" name="card_number" placeholder="1234 5678 9012 3456">

      <label>Expiry Date (MM/YY)</label>
      <input type="text" name="expiry" placeholder="12/27">

      <label>CVV</label>
      <input type="text" name="cvv" placeholder="123">
    </div>

    <button type="submit">Pay Securely</button>
  </form>

  <div class="back-link">
    <a href="checkout.php" class="button">← Back to Checkout</a>
  </div>
</div>
</body>
</html>
