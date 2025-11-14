<?php
// public/stripe_checkout.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$stripeSecretKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : ($_ENV['STRIPE_SECRET_KEY'] ?? null);
if (!$stripeSecretKey) {
    die("<h3 style='color:red;'>❌ Stripe secret key not found. Please check your .env or config.php.</h3>");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

// ✅ Validate order_ref
$order_ref = $_GET['order_ref'] ?? null;
if (!$order_ref) {
    die("<h3 style='color:red;'>Missing order reference.</h3>");
}

// ✅ Fetch order from database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = :ref LIMIT 1");
$stmt->execute(['ref' => $order_ref]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h3 style='color:red;'>Order not found.</h3>");
}

try {
    // ✅ Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],

        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Order ' . $order['order_ref']],
                'unit_amount' => (int)($order['total'] * 100),
            ],
            'quantity' => 1,
        ]],

        'mode' => 'payment',

        'success_url' => BASE_URL . '/public/success.php?order_ref=' . urlencode($order_ref),
        'cancel_url'  => BASE_URL . '/public/cancel.php?order_ref=' . urlencode($order_ref),

        // ✅ THIS FIXES IT — metadata must be on payment_intent_data
        'payment_intent_data' => [
            'metadata' => [
                'order_ref' => $order['order_ref'],
            ]
        ],
    ]);

    // ✅ Save temporary checkout session ID
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET transaction_id = :txn_id 
        WHERE order_ref = :ref
    ");
    $stmt->execute([
        ':txn_id' => $session->id, // temporary (cs_xxx)
        ':ref' => $order_ref
    ]);

    // ✅ Redirect user to Stripe Checkout
    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Stripe Error:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
