<?php
// ===============================================
// payment_webhook.php (Stripe + PayPal Webhooks)
// ===============================================

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

// Log file
$log_file = __DIR__ . '/../storage/payment-webhook.log';
function log_msg($msg) {
    global $log_file;
    file_put_contents($log_file, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

// Read raw payload
$payload = file_get_contents("php://input");
$headers = getallheaders();

if (!$payload) {
    http_response_code(400);
    exit("No payload received");
}

// =====================================================================
// 1) DETECT PAYMENT PROVIDER (Stripe or PayPal)
// =====================================================================

$isStripe = isset($headers["Stripe-Signature"]) || isset($headers["STRIPE-SIGNATURE"]);
$isPayPal = isset($headers["Paypal-Transmission-Id"]) ||
            isset($headers["PAYPAL-TRANSMISSION-ID"]) ||
            isset($headers["Paypal-Auth-Algo"]);

// =====================================================================
// ===================== STRIPE WEBHOOK ================================
// =====================================================================
if ($isStripe) {

    $sig_header = $headers["Stripe-Signature"] ?? $headers["STRIPE-SIGNATURE"] ?? "";
    $endpoint_secret = STRIPE_WEBHOOK_SECRET;

    try {
        $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (SignatureVerificationException $e) {
        log_msg("❌ STRIPE Invalid Signature: " . $e->getMessage());
        http_response_code(400);
        exit("Invalid Stripe signature");
    }

    $pi = $event->data->object;
    $order_ref = $pi->metadata->order_ref ?? null;

    if ($order_ref) {
        switch ($event->type) {

            case "payment_intent.succeeded":
                $transaction_id = $pi->id;
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET payment_status = 'paid', transaction_id = ?
                    WHERE order_ref = ?
                ");
                $stmt->execute([$transaction_id, $order_ref]);
                log_msg("✅ STRIPE PAID: ORDER=$order_ref, TXN=$transaction_id");
                break;

            case "payment_intent.canceled":
            case "payment_intent.payment_failed":
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET payment_status = 'failed', transaction_id = NULL
                    WHERE order_ref = ?
                ");
                $stmt->execute([$order_ref]);
                log_msg("⚠ STRIPE FAILED: ORDER=$order_ref");
                break;
        }
    }

    http_response_code(200);
    exit("OK-Stripe");
}



// =====================================================================
// ======================= PAYPAL WEBHOOK ===============================
// =====================================================================

if ($isPayPal) {

    $data = json_decode($payload, true);

    if (!$data) {
        log_msg("❌ PAYPAL Invalid JSON payload");
        http_response_code(400);
        exit("Invalid JSON");
    }

    // PayPal signature headers
    $transmission_id   = $headers['PayPal-Transmission-Id'] ?? $headers['PAYPAL-TRANSMISSION-ID'] ?? '';
    $transmission_time = $headers['PayPal-Transmission-Time'] ?? $headers['PAYPAL-TRANSMISSION-TIME'] ?? '';
    $cert_url          = $headers['PayPal-Cert-Url'] ?? $headers['PAYPAL-CERT-URL'] ?? '';
    $auth_algo         = $headers['PayPal-Auth-Algo'] ?? $headers['PAYPAL-AUTH-ALGO'] ?? '';
    $transmission_sig  = $headers['PayPal-Transmission-Sig'] ?? $headers['PAYPAL-TRANSMISSION-SIG'] ?? '';
    $webhook_id        = PAYPAL_WEBHOOK_ID;

    // Validate PayPal signature
    $verify = [
        "transmission_id"    => $transmission_id,
        "transmission_time"  => $transmission_time,
        "cert_url"           => $cert_url,
        "auth_algo"          => $auth_algo,
        "transmission_sig"   => $transmission_sig,
        "webhook_id"         => $webhook_id,
        "webhook_event"      => $data
    ];

    $ch = curl_init("https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Basic " . base64_encode(PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET)
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verify));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $result = json_decode($response, true);

    if (($result['verification_status'] ?? '') !== "SUCCESS") {
        log_msg("❌ PAYPAL Invalid Signature");
        http_response_code(400);
        exit("Invalid PayPal signature");
    }

    // Extract event data
    $event_type = $data["event_type"];
    $resource   = $data["resource"];

    $order_ref = $resource['purchase_units'][0]['custom_id'] ?? null;
    $transaction_id = $resource['id'] ?? null;

    if ($order_ref) {

        switch ($event_type) {

            case "CHECKOUT.ORDER.APPROVED":
            case "PAYMENT.CAPTURE.COMPLETED":
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET payment_status = 'paid', transaction_id = ?
                    WHERE order_ref = ?
                ");
                $stmt->execute([$transaction_id, $order_ref]);
                log_msg("✅ PAYPAL PAID: ORDER=$order_ref, TXN=$transaction_id");
                break;

            case "PAYMENT.CAPTURE.DENIED":
            case "PAYMENT.CAPTURE.REFUNDED":
            case "PAYMENT.CAPTURE.REVERSED":
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET payment_status = 'failed', transaction_id = NULL
                    WHERE order_ref = ?
                ");
                $stmt->execute([$order_ref]);
                log_msg("⚠ PAYPAL FAILED: ORDER=$order_ref");
                break;
        }
    }

    http_response_code(200);
    exit("OK-PayPal");
}


// =====================================================================
// =============== If none matched (unknown webhook) ====================
// =====================================================================
log_msg("❓ Unknown webhook source");
http_response_code(400);
echo "Unknown webhook";
