<?php
// ===============================================
// payment-webhook.php  (Stripe → Database Sync)
// ===============================================

// No whitespace before <?php !!
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

$endpoint_secret = STRIPE_WEBHOOK_SECRET;

// ✅ Log file
$log_file = __DIR__ . '/../storage/stripe-webhook.log';
function log_msg($msg) {
    global $log_file;
    file_put_contents($log_file, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

// ✅ Stripe requires raw payload
$payload = file_get_contents("php://input");
$sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"] ?? "";

if (!$payload) {
    http_response_code(400);
    exit("No payload received");
}

// ✅ Validate signature
try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (SignatureVerificationException $e) {
    log_msg("❌ Invalid Signature: " . $e->getMessage());
    http_response_code(400);
    exit("Invalid signature");
}

// ✅ Extract PaymentIntent and order_ref
$pi = $event->data->object;
$order_ref = $pi->metadata->order_ref ?? null;

if ($order_ref) {
    switch ($event->type) {

        // ------------------------------------------------
        // ✅ SUCCESS → Save transaction_id = PI_xxx
        // ------------------------------------------------
        case "payment_intent.succeeded":
            $transaction_id = $pi->id;
            $stmt = $pdo->prepare("
                UPDATE orders
                SET payment_status = 'paid', transaction_id = ?
                WHERE order_ref = ?
            ");
            $stmt->execute([$transaction_id, $order_ref]);
            log_msg("✅ PAID: ORDER=$order_ref, TXN=$transaction_id");
            break;

        // ------------------------------------------------
        // ❌ CANCEL OR FAILED → transaction_id = NULL
        // ------------------------------------------------
        case "payment_intent.canceled":
        case "payment_intent.payment_failed":
            $stmt = $pdo->prepare("
                UPDATE orders
                SET payment_status = 'failed', transaction_id = NULL
                WHERE order_ref = ?
            ");
            $stmt->execute([$order_ref]);
            log_msg("⚠️ FAILED/CANCELLED: ORDER=$order_ref");
            break;
    }
}

http_response_code(200);
echo "OK";
