<?php
// public/paypal_checkout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config.php';

// Ensure cart exists
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += ($item['price'] * $item['quantity']);
}

$paypalClientId = PAYPAL_CLIENT_ID;
$currency = PAYPAL_CURRENCY;
$returnUrl = PAYPAL_RETURN_URL;
$cancelUrl = PAYPAL_CANCEL_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PayPal Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 500px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 10px; color:#007BFF; }
        .total { font-size: 18px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2>PayPal Sandbox Checkout</h2>
    <p class="total">Total Amount: <strong><?= number_format($total, 2) . ' ' . htmlspecialchars($currency) ?></strong></p>

    <!-- ✅ Load PayPal Sandbox SDK -->
    <script src="https://www.sandbox.paypal.com/sdk/js?client-id=<?= htmlspecialchars($paypalClientId) ?>&currency=<?= htmlspecialchars($currency) ?>"></script>

    <!-- ✅ PayPal Button Container -->
    <div id="paypal-button-container"></div>

    <script>
    paypal.Buttons({
        style: {
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            layout: 'vertical'
        },
        // ✅ Create Order
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($total, 2, '.', '') ?>',
                        currency_code: '<?= htmlspecialchars($currency) ?>'
                    },
                    description: 'Purchase from Chandusoft Catalog'
                }]
            });
        },
        // ✅ On Approve
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                window.location.href = 'success.php?status=success&txn_id=' + details.id;
            });
        },
        // ✅ On Cancel
        onCancel: function (data) {
            window.location.href = 'cancel.php';
        },
        // ✅ On Error
        onError: function (err) {
            console.error('PayPal Error:', err);
            alert('PayPal Error: ' + err.message);
        }
    }).render('#paypal-button-container');
    </script>
</div>
</body>
</html>
