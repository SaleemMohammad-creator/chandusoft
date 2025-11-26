<?php
// admin/cancel_order.php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // optional, not used anymore

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("Invalid CSRF Token");
}

// Admin check
if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    strtolower($_SESSION['user_role']) !== 'admin'
) {
    http_response_code(403);
    exit('Access denied');
}

$order_id = intval($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
    die("Invalid order ID");
}

// Fetch order
$stmt = $pdo->prepare("SELECT id, order_ref, customer_name, customer_email, payment_status 
                       FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found");
}

// Only pending orders can be cancelled
if (strtolower($order['payment_status']) !== 'pending') {
    die("This order cannot be cancelled");
}

// Update status to cancelled
$update = $pdo->prepare("UPDATE orders SET payment_status='cancelled' WHERE id=?");
$update->execute([$order_id]);


// ============================================
//           HTML EMAIL TEMPLATE
// ============================================

$htmlEmail = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>Order Cancelled</title>
<style>
    body {
        background: #f5f5f5;
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }
    .email-wrapper {
        max-width: 600px;
        margin: auto;
        background: #ffffff;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }
    .header {
        text-align: center;
        padding-bottom: 15px;
        border-bottom: 2px solid #eeeeee;
    }
    .header h2 {
        color: #e11d48;
        margin: 0;
    }
    .content {
        padding: 20px 5px;
        line-height: 28px;
        color: #333;
        font-size: 15px;
    }
    .order-box {
        background: #fef2f2;
        border-left: 4px solid #dc2626;
        padding: 12px 15px;
        border-radius: 5px;
        margin: 15px 0;
    }
    .footer {
        text-align: center;
        font-size: 13px;
        color: #777;
        margin-top: 20px;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
</style>
</head>
<body>

<div class='email-wrapper'>

    <div class='header'>
        <h2>Order Cancelled</h2>
    </div>

    <div class='content'>
        Hi " . htmlspecialchars($order['customer_name']) . ",<br><br>

        Your order has been <strong>successfully cancelled by our admin team</strong>.

        <div class='order-box'>
            <strong>Order Reference:</strong> " . htmlspecialchars($order['order_ref']) . "<br>
            <strong>Status:</strong> Cancelled
        </div>

        If this was not requested by you or you believe this action was a mistake, 
        please contact our support team immediately.<br><br>

        Regards,<br>
        <strong>Chandusoft Pvt. Ltd.</strong>
    </div>

    <div class='footer'>
        This is an automated email. Please do not reply.
    </div>

</div>

</body>
</html>
";


// ===============================
//      SEND EMAIL USING MAIL()
// ===============================

$to = $order['customer_email'];
$subject = "Your Order Has Been Cancelled - " . $order['order_ref'];

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Chandusoft Store <system@chandusoft.test>\r\n";

// Send to Mailpit Inbox
mail($to, $subject, $htmlEmail, $headers);


// Redirect back to order page
header("Location: /admin/order_view.php?id=" . $order_id . "&cancelled=1");
exit;

?>
