<?php
$pageTitle = "Express Delivery Boy Portal";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['delivery_executive', 'admin'])) {
    header("Location: " . APP_URL . "/portal/login.php");
    exit;
}

$riderId = $currentUser['id'];
$successMsg = null;
$errorMsg = null;

// Handle Delivery Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'out_for_delivery') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'OUT_FOR_DELIVERY', `assigned_delivery_id` = ? WHERE `id` = ?")->execute([$riderId, $orderId]);
        logAudit($orderId, null, $riderId, 'OUT_FOR_DELIVERY', 'READY_FOR_DISPATCH', 'OUT_FOR_DELIVERY', 'Delivery boy started last-mile route');
        $successMsg = "Order #{$orderId} marked: OUT FOR DELIVERY!";
    } elseif ($action === 'complete_delivery') {
        $recipientName = trim($_POST['recipient_name'] ?? 'Customer');
        $sigData = $_POST['customer_signature'] ?? '';
        $relation = $_POST['recipient_relation'] ?? 'Self';

        try {
            $pdo->beginTransaction();

            $insProof = $pdo->prepare("
              INSERT INTO `delivery_proofs` (`order_id`, `delivery_executive_id`, `verification_type`, `customer_signature_svg`, `recipient_name`, `recipient_relation`, `delivered_timestamp`)
              VALUES (?, ?, 'SIGNATURE_AND_PHOTO', ?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE `customer_signature_svg`=VALUES(`customer_signature_svg`), `recipient_name`=VALUES(`recipient_name`), `delivered_timestamp`=NOW()
            ");
            $insProof->execute([$orderId, $riderId, $sigData, $recipientName, $relation]);

            $pdo->prepare("UPDATE `orders` SET `order_status` = 'DELIVERED', `delivered_at` = NOW() WHERE `id` = ?")->execute([$orderId]);

            logAudit($orderId, null, $riderId, 'DELIVERED', 'OUT_FOR_DELIVERY', 'DELIVERED', "Delivered to {$recipientName} ({$relation}) with customer signature.");

            $pdo->commit();
            $successMsg = "Order #{$orderId} DELIVERED SUCCESSFULLY! Verified with Customer Signature.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Delivery error: " . $e->getMessage();
        }
    }
}

// Fetch Active Deliveries
$orders = $pdo->query("
  SELECT o.*, s.name as service_name,
         u.name as customer_name, u.mobile as customer_mobile,
         a.house_no, a.building, a.street, a.area, a.landmark, a.city, a.pincode
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  JOIN `addresses` a ON o.delivery_address_id = a.id
  WHERE o.order_status IN ('READY_FOR_DISPATCH', 'OUT_FOR_DELIVERY', 'DELIVERED')
  ORDER BY o.order_status = 'DELIVERED' ASC, o.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Boy Portal | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#F8FAFC;">

<!-- Portal Header -->
<div class="portal-header">
  <div class="container portal-nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="<?= APP_URL ?>/logo-white.png" alt="MY TAYLOR Logo" style="height:42px; width:auto; max-width:160px; object-fit:contain;" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
      <div>
        <span class="portal-role-tag" style="margin-top:2px;">Express Delivery Fleet</span>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
      <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-dark btn-sm"><i class="fa-solid fa-shield-halved text-gold"></i> Admin Panel</a>
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-gold-outline btn-sm"><i class="fa-solid fa-power-off"></i></a>
    </div>
  </div>
</div>

<div class="container" style="padding: 24px 20px 80px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div>
      <h1 style="font-size:22px; color:#0F172A; margin:0;">Assigned Deliveries</h1>
      <span style="font-size:13px; color:var(--text-muted);">Pick up orders $\rightarrow$ Navigate $\rightarrow$ Collect customer signature on screen $\rightarrow$ Deliver.</span>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-motorcycle"></i> Rider: <?= e($currentUser['name']) ?></span>
  </div>

  <?php if ($successMsg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:18px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:14px; margin-bottom:18px;">
      <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="grid-template-columns: 1.15fr 0.85fr; gap:26px;">
    <!-- Deliveries List -->
    <div>
      <?php foreach ($orders as $o): ?>
        <div class="card" style="margin-bottom:18px; border-left:4px solid <?= $o['order_status'] === 'DELIVERED' ? 'var(--accent-emerald)' : 'var(--gold-primary)' ?>;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div>
              <span class="badge-status badge-gold"><?= e($o['booking_id']) ?></span>
              <h3 style="font-size:18px; color:#0F172A; margin:6px 0 2px;"><?= e($o['customer_name']) ?></h3>
              <p style="font-size:13px; color:var(--text-muted); margin:0;">
                <i class="fa-solid fa-phone text-gold"></i> <?= e($o['customer_mobile']) ?> • <?= e($o['service_name']) ?>
              </p>
            </div>
            <div style="text-align:right;">
              <span class="badge-status <?= $o['order_status'] === 'DELIVERED' ? 'badge-emerald' : 'badge-gold' ?>">
                <?= str_replace('_', ' ', $o['order_status']) ?>
              </span>
            </div>
          </div>

          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:10px 12px; border-radius:6px; font-size:13px; margin-bottom:14px;">
            <strong><i class="fa-solid fa-location-dot text-gold"></i> Delivery Address:</strong><br>
            <?= e($o['house_no']) ?>, <?= e($o['building']) ?><br>
            <?= e($o['street']) ?>, <?= e($o['area']) ?><br>
            <?= e($o['city']) ?> - <strong><?= e($o['pincode']) ?></strong>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="display:flex; gap:6px;">
              <a href="https://maps.google.com/?q=<?= urlencode($o['street'] . ', ' . $o['area'] . ', ' . $o['city']) ?>" target="_blank" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-location-arrow text-gold"></i> Map
              </a>
              <a href="tel:<?= e($o['customer_mobile']) ?>" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-phone text-gold"></i> Call Customer
              </a>
            </div>

            <div style="display:flex; gap:8px;">
              <?php if ($o['order_status'] === 'READY_FOR_DISPATCH'): ?>
                <form action="" method="POST">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <input type="hidden" name="action" value="out_for_delivery">
                  <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-motorcycle"></i> Start Route</button>
                </form>
              <?php elseif ($o['order_status'] === 'OUT_FOR_DELIVERY'): ?>
                <button type="button" class="btn btn-emerald btn-sm" onclick="openSignatureBox(<?= $o['id'] ?>, '<?= e($o['customer_name']) ?>', '<?= e($o['booking_id']) ?>')">
                  <i class="fa-solid fa-signature"></i> Take Signature & Deliver
                </button>
              <?php else: ?>
                <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Delivered & Signed</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Signature Pad -->
    <div>
      <div class="card" style="padding:24px; border-top:4px solid var(--accent-emerald); position:sticky; top:80px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <h3 style="font-size:17px; margin:0; color:#0F172A;"><i class="fa-solid fa-signature text-gold"></i> Customer Signature Pad</h3>
          <span class="badge-status badge-gold" id="sig-order-code">Selected Docket</span>
        </div>
        <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">
          Customer signs directly on the screen with their finger. No SMS OTP needed!
        </p>

        <form action="" method="POST">
          <input type="hidden" name="action" value="complete_delivery">
          <input type="hidden" name="order_id" id="sig-order-id" value="1">
          <input type="hidden" name="customer_signature" id="sig-data-input">

          <div class="form-group">
            <label class="form-label">Recipient Name</label>
            <input type="text" name="recipient_name" id="sig-rec-name" class="form-control" value="Rahul Sharma" required>
          </div>

          <div class="form-group">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label class="form-label" style="margin:0;">Signature Canvas</label>
              <button type="button" id="btn-clear-sig" class="btn btn-dark btn-sm" style="font-size:10px; padding:2px 6px;">Clear</button>
            </div>
            <div class="signature-box">
              <canvas id="sig-canvas"></canvas>
            </div>
          </div>

          <button type="submit" class="btn btn-emerald btn-block btn-lg" style="margin-top:10px;">
            <i class="fa-solid fa-circle-check"></i> Complete & Mark Delivered
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openSignatureBox(orderId, customerName, bookingId) {
  document.getElementById('sig-order-id').value = orderId;
  document.getElementById('sig-rec-name').value = customerName;
  document.getElementById('sig-order-code').innerText = bookingId;
  window.scrollTo({ top: 50, behavior: 'smooth' });
}
</script>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
