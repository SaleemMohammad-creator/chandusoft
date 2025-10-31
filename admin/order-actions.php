<?php
// admin/order_actions.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/csrf.php';

if (($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
    http_response_code(400);
    exit('Invalid CSRF token');
}

$order_id = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['new_status'] ?? 'pending';
$allowed = ['pending','paid','failed','refunded','cancelled'];
if (!in_array($new_status, $allowed, true)) $new_status = 'pending';

$pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?")->execute([$new_status, $order_id]);

header("Location: order_view.php?id={$order_id}");
exit;
