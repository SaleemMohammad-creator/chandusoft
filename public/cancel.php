<?php
// =======================================
// public/cancel.php
// Triggered when the customer clicks
// "Cancel" on the Stripe Checkout page
// =======================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

// ✅ Fallback if clean() doesn't exist
if (!function_exists('clean')) {
    function clean($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// ✅ Read data from cancel URL
$order_ref  = clean($_GET['order_ref'] ?? '');
$session_id = clean($_GET['session_id'] ?? '');  // Checkout Session ID

if ($order_ref) {

    // ✅ Mark order as FAILED (only when not paid)
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'failed', updated_at = NOW()
        WHERE order_ref = :ref AND payment_status != 'paid'
    ");
    $stmt->execute(['ref' => $order_ref]);

    // ✅ Cancel PaymentIntent to show "Canceled" in Stripe dashboard
    if (!empty($session_id)) {

        Stripe::setApiKey(STRIPE_SECRET_KEY);

        try {
            $session = Session::retrieve($session_id);
            if (!empty($session->payment_intent)) {

                // --- FIX: retrieve the PaymentIntent instance, then call cancel() on it ---
                $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
                $paymentIntent->cancel();  // instance method call (works for current SDK)
                // -----------------------------------------------------------------------

            }
        } catch (Exception $e) {
            $log_dir = __DIR__ . '/../storage';
            if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);

            file_put_contents(
                $log_dir . '/stripe-error.log',
                date('Y-m-d H:i:s') . " ❌ Stripe cancel failed for {$order_ref}: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payment Cancelled</title>
<style>
  /* ===========================
   Global Styles
=========================== */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #fef2f2;
    margin: 0;
}

/* ===========================
   Container
=========================== */
.container {
    max-width: 600px;
    margin: 60px auto;
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

/* ===========================
   Headings
=========================== */
h1 {
    color: #dc2626;
    font-size: 26px;
}

/* ===========================
   Order Label
=========================== */
.order {
    font-weight: bold;
    color: #b91c1c;
}

/* ===========================
   Link Button
=========================== */
a {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background: #b91c1c;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
}

</style>
</head>
<body>
  <div class="container">
    <h1>❌ Payment Cancelled</h1>
    <p>Your order <span class="order"><?= htmlspecialchars($order_ref) ?></span> was not completed.</p>
    <p><a href="<?= BASE_URL ?>/public/cart.php">Return to cart</a></p>
  </div>
</body>
</html>
