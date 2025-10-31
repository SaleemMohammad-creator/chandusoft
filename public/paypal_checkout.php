<?php
// public/paypal_checkout.php
require_once __DIR__ . '/../app/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Get order reference
$order_ref = $_GET['order_ref'] ?? null;
if (!$order_ref) die("Missing order reference.");

// Fetch order from DB
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = :ref LIMIT 1");
$stmt->execute(['ref' => $order_ref]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die("Order not found.");

// PayPal API credentials (Sandbox)
$paypalClientId = 'YOUR_SANDBOX_CLIENT_ID';
$paypalSecret   = 'YOUR_SANDBOX_SECRET';

// Create PayPal order using API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$paypalClientId:$paypalSecret");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "reference_id" => $order_ref,
        "description" => "Order #$order_ref",
        "amount" => [
            "currency_code" => "USD",
            "value" => number_format($order['total'], 2, '.', '')
        ]
    ]],
    "application_context" => [
        "return_url" => BASE_URL . "/public/success.php?order_ref=" . urlencode($order_ref),
        "cancel_url" => BASE_URL . "/public/cancel.php?order_ref=" . urlencode($order_ref)
    ]
]));
$response = curl_exec($ch);
if (curl_errno($ch)) die("CURL Error: " . curl_error($ch));
$result = json_decode($response, true);
curl_close($ch);

// Extract PayPal order link
$approveLink = null;
if (!empty($result['links'])) {
    foreach ($result['links'] as $link) {
        if ($link['rel'] === 'approve') {
            $approveLink = $link['href'];
            break;
        }
    }
}

if (!$approveLink) {
    echo "<pre>PayPal order creation failed:</pre>";
    print_r($result);
    exit;
}

// Redirect user to PayPal checkout
header("Location: $approveLink");
exit;
