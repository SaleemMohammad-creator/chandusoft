<?php

// Disable browser back/forward cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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

$errors = [];

// ===================================================================
//  Handle payment submit
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $method = $_POST['method'] ?? '';

    // ------------------------------------------------------------
    // 🅿️ PayPal Redirect
    // ------------------------------------------------------------
    if ($method === 'paypal') {

        // Step 1 — Get OAuth2 token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Accept-Language: en_US",
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $tokenResponse = curl_exec($ch);
        $tokenData = json_decode($tokenResponse, true);
        curl_close($ch);

        if (empty($tokenData['access_token'])) {
            $errors[] = "PayPal token generation failed.";
        } else {

            $accessToken = $tokenData['access_token'];

            // Step 2 — Create PayPal Order
            $orderData = [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "amount" => [
                        "currency_code" => PAYPAL_CURRENCY,
                        "value" => number_format($order['total'], 2, '.', '')
                    ],
                    "custom_id" => $order_ref
                ]],
                "application_context" => [
                    "return_url" => PAYPAL_RETURN_URL . "?order_ref=" . urlencode($order_ref),
                    "cancel_url" => PAYPAL_CANCEL_URL . "?order_ref=" . urlencode($order_ref)
                ]
            ];

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, PAYPAL_BASE_URL . "/v2/checkout/orders");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $accessToken
            ]);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($orderData));

            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch2);
            $paypalOrder = json_decode($response, true);
            curl_close($ch2);

            if (!empty($paypalOrder['links'])) {
                foreach ($paypalOrder['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        header("Location: " . $link['href']);
                        exit;
                    }
                }
            }

            $errors[] = "PayPal order creation failed.";
        }
    }

    // ------------------------------------------------------------
    // 💠 Stripe Redirect
    // ------------------------------------------------------------
    elseif ($method === 'stripe') {

        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        $amount = floatval($order['total']) * 100;

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
            'payment_intent_data' => [
                'metadata' => [
                    'order_ref' => $order_ref
                ]
            ],
            'success_url' => BASE_URL . "/public/success.php?order_ref={$order_ref}",
            'cancel_url'  => BASE_URL . "/public/cancel.php?order_ref={$order_ref}",
        ]);

        header("Location: " . $checkout_session->url);
        exit;
    }

    else {
        $errors[] = "Please choose a payment method.";
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
/* No changes - original styling */
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
.payment-options {
  display: flex;
  justify-content: space-around;
  margin: 20px 0;
}
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
a.button { text-decoration: none; background: #6c757d; color: #fff; padding: 10px 16px; border-radius: 6px; }
.back-link { text-align: center; margin-top: 20px; }
</style>

<script>
function selectMethod(method) {
  document.querySelectorAll('.option').forEach(el => el.classList.remove('active'));
  document.getElementById(method).classList.add('active');
  document.getElementById('method').value = method;
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

      <div class="option" id="paypal" onclick="selectMethod('paypal')">
        🅿️ PayPal
      </div>

      <div class="option" id="stripe" onclick="selectMethod('stripe')">
        💠 Stripe
      </div>

    </div>

    <button type="submit">Pay Securely</button>
  </form>

  <div class="back-link">
    <a href="checkout.php" class="button">← Back to Checkout</a>
  </div>

</div>

</body>
</html>
