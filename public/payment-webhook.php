<?php
// public/payment-webhook.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// Read the raw POST payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// ✅ Use constant fallback instead of only .env
$endpoint_secret = defined('STRIPE_WEBHOOK_SECRET') && !empty(STRIPE_WEBHOOK_SECRET)
    ? STRIPE_WEBHOOK_SECRET
    : ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

if (empty($endpoint_secret)) {
    // If not configured, fail safely
    http_response_code(400);
    exit('Webhook secret not configured.');
}

try {
    // ✅ Construct Stripe event securely
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event type
$type = $event->type;
$data = $event->data->object;

// ✅ Handle checkout completion
if ($type === 'checkout.session.completed') {
    $session = $data;
    $order_id = $session->metadata->order_id ?? null;
    $payment_status = $session->payment_status ?? null;

    if ($order_id) {
        if ($payment_status === 'paid') {
            // Store Stripe session JSON inside metadata
            $meta = json_encode(['stripe_session' => $session], JSON_UNESCAPED_SLASHES);
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid', 
                    metadata = JSON_MERGE_PATCH(COALESCE(metadata, JSON_OBJECT()), ?) 
                WHERE id = ?
            ");
            $stmt->execute([$meta, $order_id]);
        } else {
            $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'failed' 
                WHERE id = ?
            ")->execute([$order_id]);
        }
    }
}

// ✅ Optionally handle other events
if ($type === 'payment_intent.payment_failed') {
    $intent = $data;
    $order_id = $intent->metadata->order_id ?? null;
    if ($order_id) {
        $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'failed' 
            WHERE id = ?
        ")->execute([$order_id]);
    }
}

// ✅ Send Stripe a 200 response
http_response_code(200);
echo json_encode(['received' => true]);
