<?php
// admin/order_view.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$csrf = $_SESSION['csrf_token'] ?? '';

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit('Access denied');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid order ID');

$stmt = $pdo->prepare("SELECT id, order_ref, customer_name, customer_email, total, payment_gateway, payment_status, created_at 
                       FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('Order not found');

$itemsStmt = $pdo->prepare("SELECT product_name, quantity, unit_price, total_price FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusRaw = strtolower($order['payment_status']);
$watermarkText = strtoupper($order['payment_status']);

// QR Code
$qrData = "Order: {$order['order_ref']} | Email: {$order['customer_email']} | Total: {$order['total']}";
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=" . urlencode($qrData);

// Barcode
$barcodeUrl = "https://barcode.tec-it.com/barcode.ashx?data=" . urlencode($order['order_ref']) . "&code=Code128&dpi=96";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice #<?= htmlspecialchars($order['order_ref']) ?></title>

<style>
/* RESET */
* { box-sizing: border-box; }

/* GLOBAL */
body {
  font-family: "Inter", Arial, sans-serif;
  margin: 0;
  padding: 24px 12px;
  background: #e5e7eb;
  color: #111827;
}

/* OUTER */
.invoice-outer { max-width: 960px; margin: 0 auto; }

/* CONTAINER */
.invoice-container {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  padding: 32px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  position: relative;
  overflow: hidden;
}

/* WATERMARK */
.watermark {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%) rotate(-20deg);
  font-size: 70px;
  font-weight: 800;
  opacity: 0.08;
  color: #2563eb;
  pointer-events: none;
}

/* HEADER */
.header { 
  display: flex; 
  justify-content: space-between; 
  gap: 20px; 
  border-bottom: 2px solid #1e3a8a; 
  padding-bottom: 16px; 
  margin-bottom: 24px; 
}

.header-left { display: flex; gap: 12px; }
.logo-box img { width: 150px; border-radius: 4px; }

.company-title h1 { margin: 0; font-size: 22px; font-weight: 700; }
.company-title span { font-size: 13px; color: #6b7280; }

.company-address {
  font-size: 13px;
  color: #4b5563;
  white-space: pre-line;
  margin-top: 6px;
}

.header-right { text-align: right; }
.header-right small { display: block; margin-bottom: 6px; color: #6b7280; }
.qr-box img { width: 110px; border-radius: 6px; }

/* BARCODE */
.barcode-box img { width: 200px; }

/* SECTION */
.section { margin-bottom: 22px; }
.section h3 {
  margin: 0 0 8px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 6px;
  font-size: 16px;
}
.details p { margin: 4px 0; font-size: 14px; }

/* STATUS */
.status-badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}
.status-paid { background:#dcfce7; color:#166534; }
.status-pending { background:#fef3c7; color:#92400e; }
.status-failed { background:#fee2e2; color:#991b1b; }
.status-refunded { background:#e0f2fe; color:#075985; }
.status-cancelled { background:#e5e7eb; color:#374151; }

.gateway { font-weight:600; color:#1e3a8a; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse: collapse; font-size:14px; margin-top:10px; }
th, td { padding:10px 12px; text-align:left; }
thead { background:#1e3a8a; color:#fff; }
tbody tr:nth-child(even) { background:#f9fafb; }
tbody tr:hover { background:#eef2ff; }

/* TOTAL */
.total-box { text-align:right; margin-top: 18px; }
.total-box strong { font-size:16px; }

/* ACTIONS */
.actions-row {
  margin-top: 26px;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.btn {
  padding: 8px 14px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  border: none;
  text-decoration:none;
}

.btn-secondary { background:#4b5563; color:#fff; }
.btn-secondary:hover { background:#374151; }

.btn-danger { background:#dc2626; color:#fff; }
.btn-danger:hover { background:#b91c1c; }

.btn-ghost { background:transparent; color:#2563eb; }
.btn-ghost:hover { background:#e0e7ff; }

/* SIGNATURE */
.signature-block {
  margin-top: 30px;
}
.signature-block img {
  width: 180px;
  opacity: 0.9;
}
.signature-block p {
  margin: 4px 0;
  font-size: 14px;
  font-weight: 600;
}

/* PRINT */
@media print {
  body { background:#fff; }
  .btn, .actions-row { display:none !important; }
  .invoice-container { 
      box-shadow:none; 
      margin:0; 
      border:none; 
      border-radius:0;
  }
}
</style>
</head>
<body>

<div class="invoice-outer">
<div class="invoice-container">

    <!-- WATERMARK -->
    <div class="watermark"><?= htmlspecialchars($watermarkText) ?></div>

    <!-- HEADER -->
    <div class="header">

      <div class="header-left">
        <div class="logo-box">
            <img src="/admin/images/logo.jpg" alt="Logo">
        </div>

        <div>
            <div class="company-title">
                <h1>Chandusoft Technologies Pvt Ltd</h1>
                <span>Order Invoice • <?= htmlspecialchars($order['order_ref']) ?></span>
            </div>

            <div class="company-address">
Module No.6, First Floor, IT Tower Medha
Survey No. 52 & 53, Kesarapalli Village
Krishna District, Andhra Pradesh - 521102
Email: chandusoft.com
Phone: +91 8025 266 524
            </div>
        </div>
      </div>

      <div class="header-right">
        <small>Date: <?= date('d M Y', strtotime($order['created_at'])) ?></small>

        <div class="qr-box">
            <img src="<?= $qrUrl ?>" alt="QR Code">
        </div>

        <div class="barcode-box">
            <img src="<?= $barcodeUrl ?>" alt="Barcode">
        </div>
      </div>

    </div>

    <!-- ORDER SUMMARY -->
    <div class="section">
      <h3>Order Summary</h3>
      <div class="details">
        <p><strong>Order Ref:</strong> <?= htmlspecialchars($order['order_ref']) ?></p>
        <p><strong>Payment Gateway:</strong> <span class="gateway"><?= htmlspecialchars($order['payment_gateway']) ?></span></p>
        <p><strong>Status:</strong>
            <span class="status-badge status-<?= $statusRaw ?>">
                <?= htmlspecialchars($order['payment_status']) ?>
            </span>
        </p>
        <p><strong>Total Amount:</strong> $<?= number_format($order['total'],2) ?></p>
      </div>
    </div>

    <!-- CUSTOMER DETAILS -->
    <div class="section">
      <h3>Customer Details</h3>
      <div class="details">
        <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
      </div>
    </div>

    <!-- CUSTOMER ADDRESS BLOCK (STATIC) -->
    <div class="section">
      <h3>Billing Address</h3>
      <div class="details">
        <p><?= nl2br(htmlspecialchars("Customer Address Not Stored")) ?></p>
      </div>
    </div>

    <!-- ITEMS -->
    <div class="section">
      <h3>Items</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['product_name']) ?></td>
              <td><?= $it['quantity'] ?></td>
              <td>$<?= number_format($it['unit_price'],2) ?></td>
              <td>$<?= number_format($it['total_price'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="total-box">
         <p><strong>Grand Total: $<?= number_format($order['total'],2) ?></strong></p>
      </div>
    </div>

    <!-- SIGNATURE SECTION -->
    <div class="signature-block">
        <img src="/admin/images/signature.png" alt="Signature">
        <p>Authorized Signatory</p>
        <p>Chandusoft Technologies Pvt Ltd</p>
    </div>

    <!-- ACTIONS -->
    <div class="actions-row">

      <button type="button" onclick="window.print()" class="btn btn-secondary">
        📄 Download PDF
      </button>

      <?php if ($statusRaw === 'pending'): ?>
      <form method="post" action="/admin/cancel_order" style="margin:0;">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit" class="btn btn-danger">❌ Cancel Order</button>
      </form>
      <?php endif; ?>

      <a href="/admin/orders" class="btn btn-ghost">← Back to Orders</a>

    </div>

</div>
</div>

</body>
</html>
