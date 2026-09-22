<?php
require_once __DIR__ . '/config/config.php';
$pdo = getDbConnection();

$bookingId = trim($_GET['booking_id'] ?? '');
if (empty($bookingId)) {
    die("Booking ID is required to generate invoice.");
}

$stmt = $pdo->prepare("
  SELECT o.*, s.name as service_name, s.category as service_category,
         f.name as fabric_name, f.color as fabric_color,
         u.name as customer_name, u.mobile as customer_mobile, u.email as customer_email,
         a.house_no, a.building, a.street, a.area, a.city, a.state, a.pincode
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  LEFT JOIN `fabrics` f ON o.fabric_id = f.id
  JOIN `addresses` a ON o.delivery_address_id = a.id
  WHERE o.booking_id = ?
");
$stmt->execute([$bookingId]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tax Invoice — <?= e($order['booking_id']) ?> | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; background: #F8FAFC; color: #0F172A; padding: 40px; margin: 0; }
    .invoice-card { max-width: 800px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0B132B; padding-bottom: 24px; margin-bottom: 30px; }
    .logo-area { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
    .logo-area img { height: 48px; width: auto; max-width: 220px; object-fit: contain; }
    .inv-title { font-size: 22px; font-weight: 800; color: #0B132B; margin: 0; }
    .table { width: 100%; border-collapse: collapse; margin: 24px 0; }
    .table th { background: #0B132B; color: #FFFFFF; text-align: left; padding: 12px; font-size: 13px; text-transform: uppercase; }
    .table td { border-bottom: 1px solid #E2E8F0; padding: 14px 12px; font-size: 14px; }
    .total-box { margin-left: auto; width: 300px; background: #F8FAFC; border-radius: 8px; padding: 16px; margin-top: 20px; border: 1px solid #E2E8F0; }
    .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .total-row.grand { border-top: 2px solid #0B132B; padding-top: 8px; font-weight: 800; font-size: 17px; color: #0B132B; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #D1FAE5; color: #065F46; }
    .btn-print { background: #0B132B; color: #D4AF37; border: none; padding: 10px 20px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    @media print {
      body { background: #fff; padding: 0; }
      .invoice-card { box-shadow: none; border: none; padding: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="invoice-card">
  <div class="no-print" style="text-align:right; margin-bottom:20px;">
    <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print Official Tax Invoice</button>
  </div>

  <div class="header">
    <div class="logo-area">
      <img src="<?= APP_URL ?>/logo-tight.png" alt="MY TAYLOR Logo" onerror="this.src='<?= APP_URL ?>/logo.jpeg'">
      <span style="color:#D4AF37; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Tailored for You. Delivered in 24 Hours.</span>
    </div>
    <div style="text-align:right;">
      <h2 style="font-size:20px; color:#0B132B; margin:0 0 4px;">TAX INVOICE</h2>
      <p style="margin:0; font-size:13px; color:#64748B;">
        Booking ID: <strong><?= e($order['booking_id']) ?></strong><br>
        Date: <?= date('d M Y', strtotime($order['created_at'])) ?><br>
        Payment Status: <strong style="color:#059669;"><?= e($order['payment_status']) ?> (<?= e($order['payment_method']) ?>)</strong>
      </p>
    </div>
  </div>

  <div class="grid">
    <div>
      <h4 style="font-size:13px; text-transform:uppercase; color:#64748B; margin-bottom:6px;">Billed & Delivered To:</h4>
      <p style="font-size:14px; margin:0; line-height:1.5;">
        <strong><?= e($order['customer_name']) ?></strong><br>
        Phone: <?= e($order['customer_mobile']) ?><br>
        <?= e($order['house_no']) ?>, <?= e($order['building']) ?><br>
        <?= e($order['street']) ?>, <?= e($order['area']) ?><br>
        <?= e($order['city']) ?>, <?= e($order['state']) ?> - <strong><?= e($order['pincode']) ?></strong>
      </p>
    </div>
    <div style="text-align:right;">
      <h4 style="font-size:13px; text-transform:uppercase; color:#64748B; margin-bottom:6px;">Origin Hub:</h4>
      <p style="font-size:14px; margin:0; line-height:1.5;">
        <strong>MY TAYLOR Master Atelier #1</strong><br>
        Bespoke Production & Express Dispatch Facility<br>
        Pali Hill, Bandra West, Mumbai - 400050<br>
        GSTIN: 27AABCM9024Q1ZS<br>
        Support: +91 98000 00000
      </p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th>Category</th>
        <th>SLA Mode</th>
        <th style="text-align:right;">Amount (INR)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <strong><?= e($order['service_name']) ?></strong>
          <div style="font-size:12px; color:#64748B;">Custom handcrafted tailoring to individual body contours.</div>
        </td>
        <td><?= strtoupper($order['service_category']) ?></td>
        <td><?= $order['priority'] === 'EXPRESS' ? '⚡ 24H Express' : 'Standard' ?></td>
        <td style="text-align:right; font-weight:700;"><?= formatPrice($order['tailoring_charge']) ?></td>
      </tr>
      <?php if ($order['fabric_charge'] > 0): ?>
        <tr>
          <td>
            <strong>Fabric: <?= e($order['fabric_name']) ?></strong>
            <div style="font-size:12px; color:#64748B;">Color: <?= e($order['fabric_color']) ?></div>
          </td>
          <td>Fabric Mill</td>
          <td>Included</td>
          <td style="text-align:right; font-weight:700;"><?= formatPrice($order['fabric_charge']) ?></td>
        </tr>
      <?php endif; ?>
      <tr>
        <td>Doorstep Measurement Concierge Service</td>
        <td>Measurement</td>
        <td>Home Visit</td>
        <td style="text-align:right; font-weight:700;"><?= formatPrice($order['measurement_fee']) ?></td>
      </tr>
      <tr>
        <td>24-Hour Express Guaranteed Dispatch Fee</td>
        <td>Logistics SLA</td>
        <td>Priority</td>
        <td style="text-align:right; font-weight:700;"><?= formatPrice($order['express_fee']) ?></td>
      </tr>
    </tbody>
  </table>

  <table class="total-table">
    <tr>
      <td>Subtotal:</td>
      <td style="text-align:right;"><?= formatPrice($order['tailoring_charge'] + $order['fabric_charge'] + $order['measurement_fee'] + $order['express_fee']) ?></td>
    </tr>
    <?php if ($order['discount_amount'] > 0): ?>
      <tr style="color:#059669;">
        <td>Promotional Discount:</td>
        <td style="text-align:right;">-<?= formatPrice($order['discount_amount']) ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td>Taxes (Included):</td>
      <td style="text-align:right;">₹0.00</td>
    </tr>
    <tr class="total-row">
      <td>Final Total:</td>
      <td style="text-align:right; color:#D4AF37;"><?= formatPrice($order['total_amount']) ?></td>
    </tr>
  </table>

  <div style="margin-top:40px; padding-top:20px; border-top:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#64748B;">
    <div>
      <p style="margin:0;">This is a computer-generated tax invoice and requires no physical signature.</p>
      <p style="margin:2px 0 0;">Thank you for choosing <strong>MY TAYLOR</strong> — Tailored for You. Delivered in 24 Hours.</p>
    </div>
    <div style="text-align:center; padding:10px; border:1px solid #CBD5E1; border-radius:6px; background:#F8FAFC;">
      <i class="fa-solid fa-qrcode" style="font-size:36px; color:#0B132B;"></i>
      <div style="font-size:10px; font-weight:700; margin-top:4px;">VERIFIED DOCKET</div>
    </div>
  </div>
</div>

</body>
</html>
